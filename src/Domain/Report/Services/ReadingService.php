<?php

namespace Domain\Report\Services;

use Domain\Report\Dtos\SaveReadingDto;
use Domain\Report\Interfaces\AnalystRepositoryInterface;
use Domain\Report\Interfaces\ReportRepositoryInterface;
use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
use Domain\Report\Interfaces\SectionSignatureRepositoryInterface;
use Domain\Report\Models\Analyst;
use Domain\Report\Models\Report;
use Domain\Report\Models\SectionInstance;
use Domain\Report\Models\SectionSignature;
use Domain\Report\Support\LocationConclusion;
use Domain\Report\Support\MicrobialValue;
use Illuminate\Support\Facades\DB;

/**
 * Reading-phase analyst workflow.
 *
 * Lifecycle:
 *  - startReading(): an analyst on the inbox tab "Sedang Dibaca" claims the
 *    report. Sets locked_by, registers an Analyst pivot row of type=reading.
 *  - saveReading(action=draft): persists reading values, recomputes per-row
 *    and per-instance conclusions, releases the lock so a fellow reading
 *    analyst can continue.
 *  - saveReading(action=finalize): same as draft, plus signs each instance
 *    with role=reading and transitions report into pending_review.
 *
 * Validation guard: a reading value (B/F) can only be saved into a column
 * whose monitoring time has already been recorded. This prevents the reading
 * analyst from filling in slots the monitoring analyst left blank.
 */
class ReadingService
{
    public const ACTION_DRAFT = 'draft';

    public const ACTION_RELEASE = 'release';

    public const ACTION_FINALIZE = 'finalize_reading';

    public function __construct(
        protected ReportRepositoryInterface $reports,
        protected AnalystRepositoryInterface $analysts,
        protected SectionInstanceRepositoryInterface $sectionInstanceRepository,
        protected SectionSignatureRepositoryInterface $sectionSignatures,
        protected FieldLockService $fieldLocks,
        protected ReportApprovalService $approvalService,
    ) {}

    /**
     * Lock the report to the analyst as the reading owner.
     *
     * @throws \RuntimeException
     */
    public function startReading(Report $report, string $analystId): Report
    {
        if ($report->status !== Report::STATUS_IN_PROGRESS_READING) {
            throw new \RuntimeException('Laporan ini belum siap untuk tahap pembacaan.');
        }

        if ($report->locked_by !== null && $report->locked_by !== $analystId) {
            throw new \RuntimeException('Laporan ini sedang dibaca oleh analis lain.');
        }

        return DB::transaction(function () use ($report, $analystId) {
            $this->reports->updateMeta($report, [
                'locked_by' => $analystId,
            ]);

            $this->analysts->attach($report->id, $analystId, Analyst::TYPE_READING);

            return $this->reports->refresh($report);
        });
    }

    /**
     * Persist reading values for all section instances and recompute MS/TMS.
     *
     * @throws \RuntimeException
     */
    public function saveReading(
        Report $report,
        string $analystId,
        SaveReadingDto $dto,
        string $action = self::ACTION_DRAFT,
        ?string $supervisorId = null,
    ): void {
        if ($report->status !== Report::STATUS_IN_PROGRESS_READING) {
            throw new \RuntimeException('Laporan ini bukan dalam tahap pembacaan.');
        }
        if ($report->locked_by !== $analystId) {
            throw new \RuntimeException('Anda bukan penanggung jawab pembacaan laporan ini.');
        }
        if (! in_array($action, [self::ACTION_DRAFT, self::ACTION_RELEASE, self::ACTION_FINALIZE], true)) {
            throw new \RuntimeException('Aksi penyimpanan tidak dikenali.');
        }

        DB::transaction(function () use ($report, $dto, $analystId, $action, $supervisorId) {
            foreach ($dto->sections as $instanceId => $payload) {
                /** @var SectionInstance|null $instance */
                $instance = $this->sectionInstanceRepository->findByReportAndKey(
                    $report->id,
                    $instanceId,
                    ['instanceLocations.entries', 'instanceLocations.location.room'],
                );

                if ($instance === null) {
                    continue;
                }

                $sectionChanged = $this->saveReadingForInstance($instance, $payload['rows'] ?? [], $analystId);
                if ($sectionChanged) {
                    $this->invalidateSignaturesForEditedSection($instance);
                }
                $this->recomputeConclusions($instance);
            }

            if ($action === self::ACTION_FINALIZE) {
                $this->finalizeReading($report, $analystId, $supervisorId);
            } elseif ($action === self::ACTION_RELEASE) {
                // Match monitoring handoff behavior:
                // "Simpan Pembacaan" should stamp per-section reading sign-off
                // without moving the phase yet.
                $this->stampReadingSignatures($report, $analystId);
                $this->reports->updateMeta($report, [
                    'locked_by' => null,
                ]);
            }
            // ACTION_DRAFT: keep locked_by on the current reading analyst.
        });
    }

    /**
     * Sign each section_instance as "reading" only when the section has
     * reading data, then transition the report status. Empty sections stay
     * unsigned.
     */
    private function finalizeReading(Report $report, string $analystId, ?string $supervisorId): void
    {
        $this->stampReadingSignatures($report, $analystId);

        $this->reports->updateMeta($report, [
            'status' => Report::STATUS_PENDING_REVIEW,
            'locked_by' => null,
        ]);

        // Hand off to supervisor by creating their approval row.
        $this->approvalService->ensureSupervisorAssignment($report, $supervisorId);
    }

    /**
     * True when at least one entry under the section has cfu_bacteri or
     * cfu_fungi populated.
     */
    private function sectionHasReadingData(SectionInstance $instance): bool
    {
        foreach ($instance->instanceLocations as $loc) {
            foreach ($loc->entries as $entry) {
                if ($entry->cfu_bacteri !== null && $entry->cfu_bacteri !== ''
                    || $entry->cfu_fungi !== null && $entry->cfu_fungi !== ''
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Stamp SectionSignature(role=reading) on sections that already carry
     * reading values. Each analyst signs at most once per section.
     */
    private function stampReadingSignatures(Report $report, string $analystId): void
    {
        // Force-refresh via repository because saveReadingForInstance()
        // touched entries earlier in this transaction.
        $instances = $this->sectionInstanceRepository->getInstancesWithEntriesForReport($report->id);
        $now = now();

        foreach ($instances as $instance) {
            if (! $this->sectionHasReadingData($instance)) {
                continue;
            }

            $this->sectionSignatures->sign(
                $instance->id,
                SectionSignature::ROLE_READING,
                $analystId,
                $now,
            );
        }
    }

    /**
     * When analyst edits a section during revision, downstream sign-offs for
     * that section are no longer valid and must be re-created on finalize.
     */
    private function invalidateSignaturesForEditedSection(SectionInstance $instance): void
    {
        $this->sectionSignatures->deleteBySectionAndRoles(
            $instance->id,
            [
                SectionSignature::ROLE_READING,
                SectionSignature::ROLE_REVIEW,
                SectionSignature::ROLE_APPROVAL,
            ],
        );
    }

    /**
     * Persist reading values for a section instance.
     *
     * @param  array<string, array{readings: array<int, array<string, mixed>>}>  $rows
     *                                                                                  keyed by section_instance_location id
     */
    private function saveReadingForInstance(SectionInstance $instance, array $rows, string $analystId): bool
    {
        $sectionChanged = false;

        // Bulk-fetch field locks for every entry under this instance to
        // avoid an N+1 storm inside the row/reading loops below.
        $entryIds = [];
        foreach ($instance->instanceLocations as $row) {
            foreach ($row->entries as $entry) {
                $entryIds[] = $entry->id;
            }
        }
        $entryLocksMap = $this->fieldLocks->getForRowsKeyed('section_entries', $entryIds);

        foreach ($instance->instanceLocations as $row) {
            $payload = $rows[$row->id] ?? null;
            if ($payload === null) {
                continue;
            }
            $readings = $payload['readings'] ?? [];
            $rowClass = $row->location?->room?->class;

            foreach ($readings as $colIdx => $values) {
                $colIdx = (int) $colIdx;

                // Pick the entry that matches this row's class for ab-sections,
                // otherwise the only entry at that column index.
                $candidates = $row->entries->where('column_index', $colIdx);
                $target = null;
                if ($rowClass !== null && in_array($rowClass, ['A', 'B'], true)) {
                    $target = $candidates->firstWhere('sub_column', $rowClass);
                }
                $target = $target ?? $candidates->first();

                if ($target === null) {
                    continue;
                }
                if (! $target->hasMonitoringTime()) {
                    // Reading values are only allowed where monitoring time exists.
                    continue;
                }

                $newValues = [
                    'cfu_bacteri' => MicrobialValue::normalise($values['cfu_bacteri'] ?? ($values['reading_total'] ?? null)),
                    'cfu_fungi' => MicrobialValue::normalise($values['cfu_fungi'] ?? ($values['reading_fungi'] ?? null)),
                ];

                $entryLocks = $entryLocksMap[$target->id] ?? collect();

                $fillable = [];
                foreach ($newValues as $field => $value) {
                    if ($this->fieldLocks->canEdit($entryLocks, $field, $analystId)) {
                        $fillable[$field] = $value;
                    }
                }

                if (! array_key_exists('cfu_bacteri', $fillable) && ! array_key_exists('cfu_fungi', $fillable)) {
                    continue;
                }

                $effectiveBacteri = array_key_exists('cfu_bacteri', $fillable)
                    ? $fillable['cfu_bacteri']
                    : $target->cfu_bacteri;
                $effectiveFungi = array_key_exists('cfu_fungi', $fillable)
                    ? $fillable['cfu_fungi']
                    : $target->cfu_fungi;

                $fillable['cfu_total'] = MicrobialValue::displayTotal($effectiveBacteri, $effectiveFungi);

                $dirtyFields = $this->sectionInstanceRepository->updateEntryAndGetDirtyFields($target, $fillable);
                if (empty($dirtyFields)) {
                    continue;
                }

                $sectionChanged = true;

                foreach ($newValues as $field => $value) {
                    if (in_array($field, $dirtyFields, true)) {
                        $this->fieldLocks->lockField('section_entries', $target->id, $field, $analystId, $value);
                    }
                }
            }
        }

        return $sectionChanged;
    }

    /**
     * Cache MS/TMS verdicts on each row, then on the instance itself.
     */
    private function recomputeConclusions(SectionInstance $instance): void
    {
        $this->sectionInstanceRepository->loadRelations($instance, ['instanceLocations.entries', 'instanceLocations.location']);

        foreach ($instance->instanceLocations as $row) {
            $verdict = LocationConclusion::forRow($row->location, $row->entries);
            // Persist the cached verdict on the leaf entries (any one will do
            // for queries) and aggregate at instance level.
            foreach ($row->entries as $entry) {
                if ($entry->conclusion !== $verdict) {
                    $this->sectionInstanceRepository->updateEntry($entry, [
                        'conclusion' => $verdict,
                    ]);
                }
            }
        }

        $this->sectionInstanceRepository->updateInstance($instance, [
            'final_conclusion' => LocationConclusion::forInstance($instance),
        ]);
    }
}
