<?php

namespace Domain\Report\Services;

use Domain\Report\Dtos\SaveMonitoringDto;
use Domain\Report\Interfaces\AnalystRepositoryInterface;
use Domain\Report\Interfaces\MonitoringEntryRepositoryInterface;
use Domain\Report\Interfaces\ReportApprovalRepositoryInterface;
use Domain\Report\Interfaces\ReportRepositoryInterface;
use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
use Domain\Report\Interfaces\SectionSignatureRepositoryInterface;
use Domain\Report\Models\Analyst;
use Domain\Report\Models\FieldLock;
use Domain\Report\Models\Report;
use Domain\Report\Models\SectionInstance;
use Domain\Report\Models\SectionSignature;
use Domain\Report\Support\MicrobialValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Handles the analyst monitoring workflow:
 *  - "Mulai pengerjaan" (lock the report and bootstrap entry rows)
 *  - Saving instrument / medium / incubator entries on the form
 *  - Saving section monitoring data (time_start, time_end, column_label_value)
 *  - Finalizing the monitoring phase — moves the report into "in_progress_reading"
 *
 * Locking model:
 *  Once an analyst calls startMonitoring(), the report is owned by that analyst
 *  via locked_by. Other analysts may only view; only the locking analyst can
 *  continue editing until the report leaves in_progress.
 *
 *  When the analyst chooses "Simpan Monitoring" from the handoff modal,
 *  locked_by is cleared so a fellow analyst can take over. The report status
 *  remains in_progress_monitoring, and per-section monitoring signatures are
 *  stamped for sections that already contain monitoring data.
 *
 *  When the analyst chooses "Selesaikan Monitoring & Mulai Pembacaan", the
 *  monitoring signature is recorded for every section instance and the report
 *  transitions into in_progress_reading; locked_by is cleared so a reading
 *  analyst can pick it up.
 */
class MonitoringService
{
    public const ACTION_DRAFT = 'draft';                  // save & keep lock; analyst stays on the form.

    public const ACTION_RELEASE = 'release';                // save + sign sections with data + release lock.

    public const ACTION_FINALIZE = 'finalize_monitoring';    // sign off + transition to reading phase.

    public const ACTION_TO_READING = 'to_reading';           // revision-only: keep lock and switch to reading stage.

    public const ACTION_FINALIZE_TO_REVIEW = 'finalize_to_review'; // revision-only: send directly to supervisor.

    /**
     * Tool names that always appear in section "Identitas Instrumen".
     * The default first row is "Air Sampler" (cannot be edited by name).
     */
    private const DEFAULT_INSTRUMENTS = ['Air Sampler'];

    public function __construct(
        protected ReportRepositoryInterface $reports,
        protected ReportApprovalRepositoryInterface $approvals,
        protected AnalystRepositoryInterface $analysts,
        protected MonitoringEntryRepositoryInterface $monitoringEntries,
        protected SectionInstanceRepositoryInterface $sectionInstanceRepository,
        protected SectionSignatureRepositoryInterface $sectionSignatures,
        protected FieldLockService $fieldLocks,
        protected ReportApprovalService $approvalService,
    ) {}

    /**
     * Lock the report to the given analyst and bootstrap all the entry rows
     * needed by the monitoring form.
     *
     * Idempotent: if the analyst is already the owner, returns the report
     * untouched. If another analyst owns it, throws.
     *
     * @throws \RuntimeException When another analyst already owns the report.
     */
    public function startMonitoring(Report $report, string $analystId): Report
    {
        $blocked = [
            Report::STATUS_IN_PROGRESS_READING,
            Report::STATUS_PENDING_REVIEW,
            Report::STATUS_PENDING_APPROVAL,
            Report::STATUS_COMPLETED,
            Report::STATUS_ARCHIVED,
        ];
        if (in_array($report->status, $blocked, true)) {
            throw new \RuntimeException('Tahap monitoring untuk laporan ini sudah selesai.');
        }

        if ($report->locked_by !== null && $report->locked_by !== $analystId) {
            throw new \RuntimeException('Laporan ini sedang dikerjakan oleh analis lain.');
        }

        return DB::transaction(function () use ($report, $analystId) {
            $this->reports->updateMeta($report, [
                'locked_by' => $analystId,
                'status' => Report::STATUS_IN_PROGRESS_MONITORING,
                'monitoring_started_at' => $report->monitoring_started_at ?? now(),
            ]);

            $this->analysts->attach($report->id, $analystId, Analyst::TYPE_MONITORING);
            $this->reports->loadRelations($report, [
                'reportTemplate.mediumTemplates',
                'reportTemplate.incubatorTemplates',
                'reportTemplate.sections',
            ]);

            $this->bootstrapInstrumentEntries($report);
            $this->bootstrapMediumEntries($report);
            $this->bootstrapIncubators($report);

            return $this->reports->refresh($report, [
                'reportTemplate',
                'lockedByUser',
                'instrumentEntries',
                'mediumEntries.template',
                'incubators.template',
                'incubators.entries',
            ]);
        });
    }

    /**
     * Persist analyst form input: instrument, medium, incubator entries,
     * plus per-section monitoring entries.
     *
     * Only the analyst who owns the report (locked_by) may save.
     *
     * @param  string  $action  ACTION_DRAFT | ACTION_FINALIZE
     *
     * @throws \RuntimeException
     */
    public function saveMonitoring(
        Report $report,
        string $analystId,
        SaveMonitoringDto $dto,
        string $action = self::ACTION_DRAFT,
        ?string $supervisorId = null,
    ): void {
        if ($report->locked_by !== $analystId) {
            throw new \RuntimeException('Anda bukan penanggung jawab monitoring laporan ini.');
        }

        if (! in_array($action, [
            self::ACTION_DRAFT,
            self::ACTION_RELEASE,
            self::ACTION_FINALIZE,
            self::ACTION_TO_READING,
            self::ACTION_FINALIZE_TO_REVIEW,
        ], true)) {
            throw new \RuntimeException('Aksi penyimpanan tidak dikenali.');
        }

        if ($this->isReturnedRevisionForAnalyst($report, $analystId)
            && in_array($action, [self::ACTION_RELEASE, self::ACTION_FINALIZE], true)
        ) {
            throw new \RuntimeException('Gunakan tombol revisi yang tersedia untuk mengirim laporan ini.');
        }

        DB::transaction(function () use ($report, $dto, $analystId, $action, $supervisorId) {
            $this->saveInstrumentRows($report, $dto, $analystId);
            $this->saveMediumRows($report, $dto, $analystId);
            $this->saveIncubatorRows($report, $dto, $analystId);
            $this->saveSectionMonitoringRows($report, $dto, $analystId);

            if ($action === self::ACTION_FINALIZE) {
                $this->finalizeMonitoring($report, $analystId);
            } elseif ($action === self::ACTION_TO_READING) {
                $this->moveToReadingForRevision($report, $analystId);
            } elseif ($action === self::ACTION_FINALIZE_TO_REVIEW) {
                $this->finalizeRevisionToReview($report, $analystId, $supervisorId);
            } elseif ($action === self::ACTION_RELEASE) {
                // "Simpan Monitoring" from the handoff modal should still
                // stamp per-section monitoring sign-off (without phase move).
                $this->stampMonitoringSignatures($report, $analystId);
                // Release the lock so a fellow analyst may take over.
                $this->reports->updateMeta($report, [
                    'locked_by' => null,
                ]);
            }
            // ACTION_DRAFT: keep locked_by on the current analyst.
        });
    }

    /**
     * Revision-only transition from monitoring to reading while keeping lock
     * on the same analyst.
     */
    private function moveToReadingForRevision(Report $report, string $analystId): void
    {
        if (! $this->isReturnedRevisionForAnalyst($report, $analystId)) {
            throw new \RuntimeException('Aksi ini hanya tersedia untuk laporan yang dikembalikan kepada Anda.');
        }

        if (! $this->isDualRoleAnalyst($report, $analystId)) {
            throw new \RuntimeException('Lanjut ke pembacaan hanya untuk analis yang mengerjakan monitoring dan pembacaan.');
        }

        $this->stampMonitoringSignatures($report, $analystId);

        $this->reports->updateMeta($report, [
            'status' => Report::STATUS_IN_PROGRESS_READING,
            'locked_by' => $analystId,
        ]);

        $this->analysts->attach($report->id, $analystId, Analyst::TYPE_READING);
    }

    /**
     * Revision-only shortcut: after monitoring fixes, re-sign existing reading
     * data and send report back to supervisor in one step.
     *
     * @throws \RuntimeException
     */
    private function finalizeRevisionToReview(Report $report, string $analystId, ?string $supervisorId): void
    {
        if (! $this->isReturnedRevisionForAnalyst($report, $analystId)) {
            throw new \RuntimeException('Aksi ini hanya tersedia untuk laporan yang dikembalikan kepada Anda.');
        }

        if (! $this->hasAnalystRole($report, $analystId, Analyst::TYPE_MONITORING)) {
            throw new \RuntimeException('Kirim langsung ke supervisor hanya tersedia untuk analis monitoring laporan ini.');
        }

        $this->stampMonitoringSignatures($report, $analystId);

        if (! $this->reportHasReadingData($report)) {
            throw new \RuntimeException('Belum ada data pembacaan. Gunakan tombol "Ke Pembacaan" terlebih dahulu.');
        }

        if ($this->hasAnalystRole($report, $analystId, Analyst::TYPE_READING)) {
            $this->stampReadingSignaturesFromExistingData($report, $analystId);
        }

        $this->reports->updateMeta($report, [
            'status' => Report::STATUS_PENDING_REVIEW,
            'locked_by' => null,
        ]);

        $this->approvalService->ensureSupervisorAssignment($report, $supervisorId);
    }

    /**
     * Whether this report is currently in returned-revision state for analyst.
     */
    private function isReturnedRevisionForAnalyst(Report $report, string $analystId): bool
    {
        return $this->approvals->hasReturnedForAnalyst($report->id, $analystId);
    }

    /**
     * Whether analyst is both monitoring and reading owner on this report.
     */
    private function isDualRoleAnalyst(Report $report, string $analystId): bool
    {
        return $this->hasAnalystRole($report, $analystId, Analyst::TYPE_MONITORING)
            && $this->hasAnalystRole($report, $analystId, Analyst::TYPE_READING);
    }

    /**
     * Whether the analyst owns a specific phase role on this report.
     */
    private function hasAnalystRole(Report $report, string $analystId, string $role): bool
    {
        return $this->analysts->existsForReport($report->id, $analystId, $role);
    }

    /**
     * Approver correction on monitoring fields during pending review/approval.
     *
     * Lock ownership is bypassed so supervisor/manager can adjust monitoring inputs.
     * Special rule for analyst-filled fields:
     *  - time_start/time_end, time_in/time_out, and SP/Shift labels may be
     *    edited only when the current persisted value is already non-empty.
     *
     * @throws \RuntimeException
     */
    public function saveMonitoringByApprover(
        Report $report,
        string $actorId,
        int $approvalStep,
        string $expectedReportStatus,
        SaveMonitoringDto $dto,
    ): void {
        if ($report->status !== $expectedReportStatus) {
            throw new \RuntimeException('Perbaikan monitoring tidak tersedia pada tahap laporan saat ini.');
        }

        $approval = $this->approvals->findByReportAndStep($report->id, $approvalStep);
        if ($approval === null) {
            throw new \RuntimeException('Laporan ini tidak menunggu tindakan Anda.');
        }
        if ((string) $approval->user_id !== $actorId) {
            throw new \RuntimeException('Laporan ini bukan ditugaskan kepada Anda.');
        }
        if ($approval->status !== ReportApprovalService::STATUS_PENDING) {
            throw new \RuntimeException('Laporan ini sudah diproses sebelumnya.');
        }

        DB::transaction(function () use ($report, $dto, $actorId) {
            $this->saveInstrumentRows($report, $dto, $actorId, true);
            $this->saveMediumRows($report, $dto, $actorId, true);
            $this->saveIncubatorRows($report, $dto, $actorId, true, true, true);
            $this->saveSectionMonitoringRows($report, $dto, $actorId, true, true, true);
        });
    }

    /**
     * Finalize the monitoring phase. Records SectionSignature(role=monitoring)
     * for every section that actually has monitoring data (time / SP / note),
     * and transitions report status. Empty sections stay unsigned.
     */
    private function finalizeMonitoring(Report $report, string $analystId): void
    {
        $this->stampMonitoringSignatures($report, $analystId);

        $this->reports->updateMeta($report, [
            'status' => Report::STATUS_IN_PROGRESS_READING,
            'locked_by' => null,
        ]);
    }

    /**
     * Records SectionSignature(role=monitoring) for every section that
     * already has monitoring data (time / SP / note). Empty sections stay
     * unsigned.
     *
     * Signature rows are stored per analyst so handoff history is preserved:
     * each analyst gets at most one monitoring signature per section.
     */
    private function stampMonitoringSignatures(Report $report, string $analystId): void
    {
        // Force-refresh via repository because saveSectionMonitoringRows()
        // touched entries earlier in this transaction.
        $instances = $this->sectionInstanceRepository->getInstancesWithEntriesForReport($report->id);
        $now = now();

        foreach ($instances as $instance) {
            if (! $this->sectionHasMonitoringData($instance)) {
                continue;
            }

            $this->sectionSignatures->sign(
                $instance->id,
                SectionSignature::ROLE_MONITORING,
                $analystId,
                $now,
            );
        }
    }

    /**
     * True when at least one entry under the section has any monitoring
     * value (time_start, time_end, column_label_value) or the section carries a note.
     */
    private function sectionHasMonitoringData(SectionInstance $instance): bool
    {
        if (! empty($instance->note)) {
            return true;
        }

        foreach ($instance->instanceLocations as $loc) {
            foreach ($loc->entries as $entry) {
                if (! empty($entry->time_start) || ! empty($entry->time_end) || ! empty($entry->column_label_value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * True when at least one entry under the section has reading values.
     */
    private function sectionHasReadingData(SectionInstance $instance): bool
    {
        foreach ($instance->instanceLocations as $loc) {
            foreach ($loc->entries as $entry) {
                if (($entry->cfu_bacteri !== null && $entry->cfu_bacteri !== '')
                    || ($entry->cfu_fungi !== null && $entry->cfu_fungi !== '')
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Report-level helper for reading-data presence.
     */
    private function reportHasReadingData(Report $report): bool
    {
        $instances = $this->sectionInstanceRepository->getInstancesWithEntriesForReport($report->id);

        foreach ($instances as $instance) {
            if ($this->sectionHasReadingData($instance)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recreate reading signatures from existing reading values after revision.
     */
    private function stampReadingSignaturesFromExistingData(Report $report, string $analystId): void
    {
        $instances = $this->sectionInstanceRepository->getInstancesWithEntriesForReport($report->id);
        $entryIds = [];

        foreach ($instances as $instance) {
            foreach ($instance->instanceLocations as $loc) {
                foreach ($loc->entries as $entry) {
                    $entryIds[] = $entry->id;
                }
            }
        }

        $entryLocksMap = $this->fieldLocks->getForRowsKeyed('section_entries', $entryIds);
        $now = now();

        foreach ($instances as $instance) {
            if (! $this->sectionHasReadingDataByAnalyst($instance, $analystId, $entryLocksMap)) {
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
     * True when this analyst owns at least one populated reading field in the
     * section. Prevents a monitoring-only revision from re-signing another
     * analyst's reading data.
     *
     * @param  array<string, Collection<int, FieldLock>>  $entryLocksMap
     */
    private function sectionHasReadingDataByAnalyst(
        SectionInstance $instance,
        string $analystId,
        array $entryLocksMap,
    ): bool {
        foreach ($instance->instanceLocations as $loc) {
            foreach ($loc->entries as $entry) {
                $locks = $entryLocksMap[$entry->id] ?? collect();

                foreach (['cfu_bacteri', 'cfu_fungi'] as $field) {
                    if ($entry->{$field} === null || $entry->{$field} === '') {
                        continue;
                    }

                    if ($locks->contains(
                        fn ($lock) => $lock->field_name === $field
                            && (string) $lock->filled_by === $analystId
                    )) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Monitoring edits invalidate this analyst's monitoring sign-off and the
     * downstream review chain for that section.
     * This is called only for analyst edits (not supervisor correction mode).
     */
    private function invalidateSignaturesForEditedMonitoringSection(SectionInstance $instance, string $analystId): void
    {
        $this->sectionSignatures->deleteForEditedMonitoringSection($instance->id, $analystId);
    }

    private function saveInstrumentRows(Report $report, SaveMonitoringDto $dto, string $actorId, bool $overrideLockOwnership = false): void
    {
        foreach ($dto->instruments as $toolName => $row) {
            $instrument = $this->monitoringEntries->findInstrumentEntry($report, $toolName);
            if ($instrument === null) {
                continue;
            }

            $table = $this->fieldLocks->tableOf($instrument);
            $locks = $this->fieldLocks->getForRow($table, $instrument->id);

            $values = [
                'no_id' => $row['no_id'] ?? null,
                'calibration_date' => $row['calibration_date'] ?? null,
                'due_date' => $row['due_date'] ?? null,
            ];

            $payload = [];
            foreach ($values as $field => $value) {
                if ($overrideLockOwnership || $this->fieldLocks->canEdit($locks, $field, $actorId)) {
                    $payload[$field] = $value;
                }
            }

            if (! empty($payload)) {
                $this->monitoringEntries->updateInstrumentEntry($instrument, $payload);
            }

            foreach ($values as $field => $value) {
                if (array_key_exists($field, $payload)) {
                    $this->fieldLocks->lockField($table, $instrument->id, $field, $actorId, $value);
                }
            }
        }
    }

    private function saveMediumRows(Report $report, SaveMonitoringDto $dto, string $actorId, bool $overrideLockOwnership = false): void
    {
        foreach ($dto->mediums as $entryId => $row) {
            $entry = $this->monitoringEntries->findMediumEntry($report, $entryId);
            if ($entry === null) {
                continue;
            }

            $table = $this->fieldLocks->tableOf($entry);
            $locks = $this->fieldLocks->getForRow($table, $entry->id);

            $values = [
                'batch_number' => $row['batch_number'] ?? null,
                'expiration_date' => $row['expiration_date'] ?? null,
            ];
            // Swab Kit must never carry a GPT number.
            if (! $entry->is_swab) {
                $values['gpt_number'] = $row['gpt_number'] ?? null;
            }

            $payload = [];
            foreach ($values as $field => $value) {
                if ($overrideLockOwnership || $this->fieldLocks->canEdit($locks, $field, $actorId)) {
                    $payload[$field] = $value;
                }
            }

            if (! empty($payload)) {
                $this->monitoringEntries->updateMediumEntry($entry, $payload);
            }

            foreach ($values as $field => $value) {
                if (array_key_exists($field, $payload)) {
                    $this->fieldLocks->lockField($table, $entry->id, $field, $actorId, $value);
                }
            }
        }
    }

    private function saveIncubatorRows(
        Report $report,
        SaveMonitoringDto $dto,
        string $actorId,
        bool $overrideLockOwnership = false,
        bool $timeRequiresExistingValue = false,
        bool $requiresExistingInOutActor = false,
    ): void {
        foreach ($dto->incubators as $incubatorId => $row) {
            $incubator = $this->monitoringEntries->findIncubator($report, $incubatorId);
            if ($incubator === null) {
                continue;
            }

            $incubatorTable = $this->fieldLocks->tableOf($incubator);
            $incubatorLocks = $this->fieldLocks->getForRow($incubatorTable, $incubator->id);

            $incubatorValues = [
                'no_id' => $row['no_id'] ?? null,
                'calibration_date' => $row['calibration_date'] ?? null,
                'due_date_calibration' => $row['due_date_calibration'] ?? null,
            ];

            $incubatorPayload = [];
            foreach ($incubatorValues as $field => $value) {
                if ($overrideLockOwnership || $this->fieldLocks->canEdit($incubatorLocks, $field, $actorId)) {
                    $incubatorPayload[$field] = $value;
                }
            }

            if (! empty($incubatorPayload)) {
                $this->monitoringEntries->updateIncubator($incubator, $incubatorPayload);
            }

            foreach ($incubatorValues as $field => $value) {
                if (array_key_exists($field, $incubatorPayload)) {
                    $this->fieldLocks->lockField($incubatorTable, $incubator->id, $field, $actorId, $value);
                }
            }

            foreach (($row['entries'] ?? []) as $mediumType => $entryRow) {
                $entry = $this->monitoringEntries->findIncubatorEntry($incubator, $mediumType);
                if ($entry === null) {
                    continue;
                }

                $entryTable = $this->fieldLocks->tableOf($entry);
                $entryLocks = $this->fieldLocks->getForRow($entryTable, $entry->id);

                $entryValues = [
                    'date_in' => $entryRow['date_in'] ?? null,
                    'time_in' => $entryRow['time_in'] ?? null,
                    'date_out' => $entryRow['date_out'] ?? null,
                    'time_out' => $entryRow['time_out'] ?? null,
                ];

                $hasExistingIncubatedActor = ! empty($entry->incubated_by);
                $hasExistingRemovedActor = ! empty($entry->removed_by);

                $entryPayload = [];
                foreach ($entryValues as $field => $value) {
                    if ($requiresExistingInOutActor) {
                        if (in_array($field, ['date_in', 'time_in'], true) && ! $hasExistingIncubatedActor) {
                            continue;
                        }
                        if (in_array($field, ['date_out', 'time_out'], true) && ! $hasExistingRemovedActor) {
                            continue;
                        }
                    }

                    $isTimeField = in_array($field, ['time_in', 'time_out'], true);
                    $hasExistingTime = ! empty($entry->{$field});

                    if ($timeRequiresExistingValue && $isTimeField && ! $hasExistingTime) {
                        continue;
                    }

                    if ($overrideLockOwnership || $this->fieldLocks->canEdit($entryLocks, $field, $actorId)) {
                        $entryPayload[$field] = $value;
                    }
                }

                $hasIn = (array_key_exists('date_in', $entryPayload) || array_key_exists('time_in', $entryPayload))
                    ? ! empty($entryPayload['date_in'] ?? null) || ! empty($entryPayload['time_in'] ?? null)
                    : false;
                $hasOut = (array_key_exists('date_out', $entryPayload) || array_key_exists('time_out', $entryPayload))
                    ? ! empty($entryPayload['date_out'] ?? null) || ! empty($entryPayload['time_out'] ?? null)
                    : false;

                $entryPayload['incubated_by'] = $hasIn ? ($entry->incubated_by ?? $actorId) : $entry->incubated_by;
                $entryPayload['removed_by'] = $hasOut ? ($entry->removed_by ?? $actorId) : $entry->removed_by;

                $this->monitoringEntries->updateIncubatorEntry($entry, $entryPayload);

                foreach ($entryValues as $field => $value) {
                    if (array_key_exists($field, $entryPayload)) {
                        $this->fieldLocks->lockField($entryTable, $entry->id, $field, $actorId, $value);
                    }
                }
            }
        }
    }

    /**
     * Persist time_start, time_end, column_label_value, plus the catatan text
     * for every section_instance the form posted.
     *
     * Form shape:
     *   sections[{id}][note]
     *   sections[{id}][columns][{idx}][column_label_value]
     *   sections[{id}][columns][{idx}][slots][{label_or_underscore}][time_start|time_end]
     *
     * Same SP / time values are broadcast to every entry of the matching
     * (column_index, sub_column) — i.e. for an A/B section the entry whose
     * sub_column='A' receives the slot 'A' time, sub_column='B' receives
     * slot 'B' time, regardless of how many physical rows there are.
     */
    private function saveSectionMonitoringRows(
        Report $report,
        SaveMonitoringDto $dto,
        string $actorId,
        bool $overrideLockOwnership = false,
        bool $timeRequiresExistingValue = false,
        bool $labelRequiresExistingValue = false,
    ): void {
        foreach ($dto->sections as $instanceId => $row) {
            $instance = $this->sectionInstanceRepository->findByReportAndKey($report->id, $instanceId);
            if ($instance === null) {
                continue;
            }

            $sectionChanged = false;

            if (array_key_exists('note', $row)) {
                $instanceTable = $this->fieldLocks->tableOf($instance);
                $instanceLocks = $this->fieldLocks->getForRow($instanceTable, $instance->id);

                if ($overrideLockOwnership || $this->fieldLocks->canEdit($instanceLocks, 'note', $actorId)) {
                    $dirtyFields = $this->sectionInstanceRepository->updateInstanceAndGetDirtyFields($instance, [
                        'note' => $row['note'],
                    ]);

                    if (in_array('note', $dirtyFields, true)) {
                        $sectionChanged = true;
                        $this->fieldLocks->lockField($instanceTable, $instance->id, 'note', $actorId, $row['note']);
                    }
                }
            }

            // Bulk-fetch field locks for every entry under this instance to
            // avoid an N+1 storm inside the column/slot/row loops below.
            $entryIds = [];
            foreach ($instance->instanceLocations as $loc) {
                foreach ($loc->entries as $entry) {
                    $entryIds[] = $entry->id;
                }
            }
            $entryLocksMap = $this->fieldLocks->getForRowsKeyed('section_entries', $entryIds);

            $columns = $row['columns'] ?? [];
            foreach ($columns as $colIdx => $colData) {
                $colIdx = (int) $colIdx;
                $columnLabelValue = MicrobialValue::normalise(
                    $colData['column_label_value'] ?? ($colData['sp_value'] ?? null)
                );
                $slots = $colData['slots'] ?? [];

                foreach ($instance->instanceLocations as $loc) {
                    foreach ($loc->entries as $entry) {
                        if ($entry->column_index !== $colIdx) {
                            continue;
                        }
                        $slotKey = $entry->sub_column ?? '_';
                        $slot = $slots[$slotKey] ?? null;

                        $rawTimeStart = $slot['time_start'] ?? null;
                        $rawTimeEnd = $slot['time_end'] ?? null;
                        $timeStart = ($rawTimeStart !== null && $rawTimeStart !== '') ? $rawTimeStart : null;
                        $timeEnd = ($rawTimeEnd !== null && $rawTimeEnd !== '') ? $rawTimeEnd : null;

                        $values = [
                            'time_start' => $timeStart,
                            'time_end' => $timeEnd,
                            'column_label_value' => $columnLabelValue,
                        ];

                        $entryLocks = $entryLocksMap[$entry->id] ?? collect();

                        $payload = [];
                        foreach ($values as $field => $value) {
                            $isTimeField = in_array($field, ['time_start', 'time_end'], true);
                            $isLabelField = $field === 'column_label_value';
                            $hasExistingTime = ! empty($entry->{$field});
                            $hasExistingLabel = ! empty($entry->{$field});

                            if ($timeRequiresExistingValue && $isTimeField && ! $hasExistingTime) {
                                continue;
                            }
                            if ($labelRequiresExistingValue && $isLabelField && ! $hasExistingLabel) {
                                continue;
                            }

                            if ($overrideLockOwnership || $this->fieldLocks->canEdit($entryLocks, $field, $actorId)) {
                                $payload[$field] = $value;
                            }
                        }

                        if (empty($payload)) {
                            continue;
                        }

                        $dirtyFields = $this->sectionInstanceRepository->updateEntryAndGetDirtyFields($entry, $payload);
                        if (empty($dirtyFields)) {
                            continue;
                        }

                        $sectionChanged = true;

                        foreach ($dirtyFields as $field) {
                            if (array_key_exists($field, $values)) {
                                $this->fieldLocks->lockField('section_entries', $entry->id, $field, $actorId, $values[$field]);
                            }
                        }
                    }
                }
            }

            if ($sectionChanged && ! $overrideLockOwnership) {
                $this->invalidateSignaturesForEditedMonitoringSection($instance, $actorId);
            }
        }
    }

    /**
     * Create one InstrumentEntry for Air Sampler only.
     * Swab Kit is tracked under Identitas Medium, not Identitas Instrumen.
     */
    private function bootstrapInstrumentEntries(Report $report): void
    {
        foreach (self::DEFAULT_INSTRUMENTS as $tool) {
            $this->monitoringEntries->findOrCreateInstrumentEntry($report->id, $tool);
        }
    }

    /**
     * Create one MediumEntry per MediumTemplate of the report's template.
     *
     * If a MediumTemplate's name matches a swab kit (case-insensitive contains
     * "swab"), the entry is flagged is_swab=true. Swab Kit entries don't carry
     * a GPT number and are always rendered last in the UI.
     */
    private function bootstrapMediumEntries(Report $report): void
    {
        $template = $report->reportTemplate;
        if ($template === null) {
            return;
        }

        foreach ($template->mediumTemplates as $mediumTemplate) {
            $isSwab = str_contains(strtolower($mediumTemplate->name), 'swab');

            $this->monitoringEntries->findOrCreateMediumEntry(
                $report->id,
                $mediumTemplate->id,
                [
                    'name' => $mediumTemplate->name,
                    'is_swab' => $isSwab,
                ],
            );
        }
    }

    /**
     * For each IncubatorTemplate of the report's template, create one Incubator
     * row plus its child IncubatorEntry rows: always 'monitoring', plus 'swab'
     * when the report template has a swab section.
     */
    private function bootstrapIncubators(Report $report): void
    {
        $template = $report->reportTemplate;
        if ($template === null) {
            return;
        }

        $needsSwab = $template->hasSwab();

        foreach ($template->incubatorTemplates as $incubatorTemplate) {
            $incubator = $this->monitoringEntries->findOrCreateIncubator(
                $report->id,
                $incubatorTemplate->id,
            );

            $mediumTypes = ['monitoring'];
            if ($needsSwab) {
                $mediumTypes[] = 'swab';
            }

            foreach ($mediumTypes as $mediumType) {
                $this->monitoringEntries->findOrCreateIncubatorEntry($incubator->id, $mediumType);
            }
        }
    }
}
