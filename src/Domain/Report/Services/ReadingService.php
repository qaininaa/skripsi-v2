<?php

namespace Domain\Report\Services;

use Domain\Report\Dtos\SaveReadingDto;
use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
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
    public const ACTION_DRAFT    = 'draft';
    public const ACTION_RELEASE  = 'release';
    public const ACTION_FINALIZE = 'finalize_reading';

    public function __construct(
        protected SectionInstanceRepositoryInterface $sectionInstanceRepository,
    ) {
    }

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
            $report->locked_by = $analystId;
            $report->save();

            Analyst::firstOrCreate([
                'report_id' => $report->id,
                'user_id'   => $analystId,
                'type'      => Analyst::TYPE_READING,
            ]);

            return $report->fresh();
        });
    }

    /**
     * Persist reading values for all section instances and recompute MS/TMS.
     *
     * @throws \RuntimeException
     */
    public function saveReading(Report $report, string $analystId, SaveReadingDto $dto, string $action = self::ACTION_DRAFT): void
    {
        if ($report->status !== Report::STATUS_IN_PROGRESS_READING) {
            throw new \RuntimeException('Laporan ini bukan dalam tahap pembacaan.');
        }
        if ($report->locked_by !== $analystId) {
            throw new \RuntimeException('Anda bukan penanggung jawab pembacaan laporan ini.');
        }
        if (! in_array($action, [self::ACTION_DRAFT, self::ACTION_RELEASE, self::ACTION_FINALIZE], true)) {
            throw new \RuntimeException('Aksi penyimpanan tidak dikenali.');
        }

        DB::transaction(function () use ($report, $dto, $analystId, $action) {
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

                $this->saveReadingForInstance($instance, $payload['rows'] ?? [], $analystId);
                $this->recomputeConclusions($instance);
            }

            if ($action === self::ACTION_FINALIZE) {
                $this->finalizeReading($report, $analystId);
            } elseif ($action === self::ACTION_RELEASE) {
                $report->locked_by = null;
                $report->save();
            }
            // ACTION_DRAFT: keep locked_by on the current reading analyst.
        });
    }

    /**
     * Sign each section_instance as "reading" and transition the report status.
     */
    private function finalizeReading(Report $report, string $analystId): void
    {
        $report->loadMissing('sectionInstances');
        $now = now();

        foreach ($report->sectionInstances as $instance) {
            SectionSignature::firstOrCreate(
                [
                    'section_instance_id' => $instance->id,
                    'role'                => SectionSignature::ROLE_READING,
                ],
                [
                    'signed_by' => $analystId,
                    'signed_at' => $now,
                ],
            );
        }

        $report->status    = Report::STATUS_PENDING_REVIEW;
        $report->locked_by = null;
        $report->save();
    }

    /**
     * Persist reading values for a section instance.
     *
     * @param  array<string, array{readings: array<int, array<string, mixed>>}>  $rows
     *         keyed by section_instance_location id
     */
    private function saveReadingForInstance(SectionInstance $instance, array $rows, string $analystId): void
    {
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

                $target->fill([
                    'reading_total'     => MicrobialValue::normalise($values['reading_total'] ?? null),
                    'reading_fungi'     => MicrobialValue::normalise($values['reading_fungi'] ?? null),
                    'filled_by_reading' => $target->filled_by_reading ?? $analystId,
                ])->save();
            }
        }
    }

    /**
     * Cache MS/TMS verdicts on each row, then on the instance itself.
     */
    private function recomputeConclusions(SectionInstance $instance): void
    {
        $instance->loadMissing(['instanceLocations.entries', 'instanceLocations.location']);

        foreach ($instance->instanceLocations as $row) {
            $verdict = LocationConclusion::forRow($row->location, $row->entries);
            // Persist the cached verdict on the leaf entries (any one will do
            // for queries) and aggregate at instance level.
            foreach ($row->entries as $entry) {
                if ($entry->location_conclusion !== $verdict) {
                    $entry->location_conclusion = $verdict;
                    $entry->save();
                }
            }
        }

        $instance->final_conclusion = LocationConclusion::forInstance($instance);
        $instance->save();
    }
}
