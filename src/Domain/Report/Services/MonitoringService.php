<?php

namespace Domain\Report\Services;

use Domain\Report\Dtos\SaveMonitoringDto;
use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
use Domain\Report\Models\Analyst;
use Domain\Report\Models\Incubator;
use Domain\Report\Models\IncubatorEntry;
use Domain\Report\Models\InstrumentEntry;
use Domain\Report\Models\MediumEntry;
use Domain\Report\Models\Report;
use Domain\Report\Models\ReportApproval;
use Domain\Report\Models\SectionInstance;
use Domain\Report\Models\SectionSignature;
use Domain\Report\Services\FieldLockService;
use Domain\Report\Services\ReportApprovalService;
use Domain\Report\Support\MicrobialValue;
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
    public const ACTION_DRAFT    = 'draft';                  // save & keep lock; analyst stays on the form.
    public const ACTION_RELEASE  = 'release';                // save + sign sections with data + release lock.
    public const ACTION_FINALIZE = 'finalize_monitoring';    // sign off + transition to reading phase.
    public const ACTION_TO_READING = 'to_reading';           // revision-only: keep lock and switch to reading stage.
    public const ACTION_FINALIZE_TO_REVIEW = 'finalize_to_review'; // revision-only: send directly to supervisor.

    /**
     * Tool names that always appear in section "Identitas Instrumen".
     * The default first row is "Air Sampler" (cannot be edited by name).
     */
    private const DEFAULT_INSTRUMENTS = ['Air Sampler'];

    public function __construct(
        protected SectionInstanceRepositoryInterface $sectionInstanceRepository,
        protected FieldLockService $fieldLocks,
        protected ReportApprovalService $approvalService,
    ) {
    }

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
            $report->locked_by             = $analystId;
            $report->status                = Report::STATUS_IN_PROGRESS_MONITORING;
            $report->monitoring_started_at = $report->monitoring_started_at ?? now();
            $report->save();

            Analyst::firstOrCreate([
                'report_id' => $report->id,
                'user_id'   => $analystId,
                'type'      => Analyst::TYPE_MONITORING,
            ]);

            $this->bootstrapInstrumentEntries($report);
            $this->bootstrapMediumEntries($report);
            $this->bootstrapIncubators($report);

            return $report->fresh([
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
    ): void
    {
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
                $report->locked_by = null;
                $report->save();
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

        $this->stampMonitoringSignatures($report, $analystId);

        $report->status    = Report::STATUS_IN_PROGRESS_READING;
        $report->locked_by = $analystId;
        $report->save();

        Analyst::firstOrCreate([
            'report_id' => $report->id,
            'user_id'   => $analystId,
            'type'      => Analyst::TYPE_READING,
        ]);
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

        if (! $this->isDualRoleAnalyst($report, $analystId)) {
            throw new \RuntimeException('Kirim langsung ke supervisor hanya untuk analis yang mengerjakan monitoring dan pembacaan.');
        }

        $this->stampMonitoringSignatures($report, $analystId);

        if (! $this->reportHasReadingData($report)) {
            throw new \RuntimeException('Belum ada data pembacaan. Gunakan tombol "Ke Pembacaan" terlebih dahulu.');
        }

        $this->stampReadingSignaturesFromExistingData($report, $analystId);

        $report->status    = Report::STATUS_PENDING_REVIEW;
        $report->locked_by = null;
        $report->save();

        $this->approvalService->ensureSupervisorAssignment($report, $supervisorId);
    }

    /**
     * Whether this report is currently in returned-revision state for analyst.
     */
    private function isReturnedRevisionForAnalyst(Report $report, string $analystId): bool
    {
        $report->loadMissing('approvals');

        return $report->approvals
            ->contains(function ($approval) use ($analystId) {
                return $approval->status === ReportApproval::STATUS_RETURNED
                    && (string) $approval->returned_to_user_id === $analystId;
            });
    }

    /**
     * Whether analyst is both monitoring and reading owner on this report.
     */
    private function isDualRoleAnalyst(Report $report, string $analystId): bool
    {
        $report->loadMissing('analysts');

        $hasMonitoringRole = $report->analysts->contains(
            fn ($analyst) => $analyst->type === Analyst::TYPE_MONITORING
                && (string) $analyst->user_id === $analystId
        );
        $hasReadingRole = $report->analysts->contains(
            fn ($analyst) => $analyst->type === Analyst::TYPE_READING
                && (string) $analyst->user_id === $analystId
        );

        return $hasMonitoringRole && $hasReadingRole;
    }

    /**
     * Supervisor correction on monitoring fields during pending review.
     *
     * Lock ownership is bypassed so supervisor can adjust monitoring inputs.
     * Special rule for analyst-filled fields:
     *  - time_start/time_end, time_in/time_out, and SP/Shift labels may be
     *    edited only when the current persisted value is already non-empty.
     *
     * @throws \RuntimeException
     */
    public function saveMonitoringBySupervisor(Report $report, string $supervisorId, SaveMonitoringDto $dto): void
    {
        if ($report->status !== Report::STATUS_PENDING_REVIEW) {
            throw new \RuntimeException('Perbaikan monitoring hanya tersedia saat tahap review supervisor.');
        }

        DB::transaction(function () use ($report, $dto, $supervisorId) {
            $this->saveInstrumentRows($report, $dto, $supervisorId, true);
            $this->saveMediumRows($report, $dto, $supervisorId, true);
            $this->saveIncubatorRows($report, $dto, $supervisorId, true, true, true);
            $this->saveSectionMonitoringRows($report, $dto, $supervisorId, true, true, true);
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

        $report->status    = Report::STATUS_IN_PROGRESS_READING;
        $report->locked_by = null;
        $report->save();
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
        // Force-refresh relations because saveSectionMonitoringRows() touched
        // entries earlier in this transaction; loadMissing would keep the
        // stale (pre-save) collection and skip the signature.
        $report->load('sectionInstances.instanceLocations.entries');
        $now = now();

        foreach ($report->sectionInstances as $instance) {
            if (! $this->sectionHasMonitoringData($instance)) {
                continue;
            }

            SectionSignature::firstOrCreate(
                [
                    'section_instance_id' => $instance->id,
                    'role'                => SectionSignature::ROLE_MONITORING,
                    'signed_by'           => $analystId,
                ],
                [
                    'signed_at' => $now,
                ],
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
                    || ($entry->cfu_fungsi !== null && $entry->cfu_fungsi !== '')
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
        $report->load('sectionInstances.instanceLocations.entries');

        foreach ($report->sectionInstances as $instance) {
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
        $report->load('sectionInstances.instanceLocations.entries');
        $now = now();

        foreach ($report->sectionInstances as $instance) {
            if (! $this->sectionHasReadingData($instance)) {
                continue;
            }

            SectionSignature::firstOrCreate(
                [
                    'section_instance_id' => $instance->id,
                    'role'                => SectionSignature::ROLE_READING,
                    'signed_by'           => $analystId,
                ],
                [
                    'signed_at' => $now,
                ],
            );
        }
    }

    /**
     * Monitoring edits invalidate the full downstream chain for that section.
     * This is called only for analyst edits (not supervisor correction mode).
     */
    private function invalidateSignaturesForEditedMonitoringSection(SectionInstance $instance): void
    {
        SectionSignature::query()
            ->where('section_instance_id', $instance->id)
            ->whereIn('role', [
                SectionSignature::ROLE_MONITORING,
                SectionSignature::ROLE_READING,
                SectionSignature::ROLE_REVIEW,
                SectionSignature::ROLE_APPROVAL,
            ])
            ->delete();
    }

    private function saveInstrumentRows(Report $report, SaveMonitoringDto $dto, string $actorId, bool $overrideLockOwnership = false): void
    {
        foreach ($dto->instruments as $toolName => $row) {
            $instrument = $report->instrumentEntries()->where('tool_name', $toolName)->first();
            if ($instrument === null) {
                continue;
            }

            $table = $this->fieldLocks->tableOf($instrument);
            $locks = $this->fieldLocks->getForRow($table, $instrument->id);

            $values = [
                'no_id'            => $row['no_id']            ?? null,
                'calibration_date' => $row['calibration_date'] ?? null,
                'due_date'         => $row['due_date']         ?? null,
            ];

            $payload = [];
            foreach ($values as $field => $value) {
                if ($overrideLockOwnership || $this->fieldLocks->canEdit($locks, $field, $actorId)) {
                    $payload[$field] = $value;
                }
            }

            if (! empty($payload)) {
                $instrument->fill($payload)->save();
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
            $entry = $report->mediumEntries()->whereKey($entryId)->first();
            if ($entry === null) {
                continue;
            }

            $table = $this->fieldLocks->tableOf($entry);
            $locks = $this->fieldLocks->getForRow($table, $entry->id);

            $values = [
                'batch_number'    => $row['batch_number']    ?? null,
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
                $entry->fill($payload)->save();
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
    ): void
    {
        foreach ($dto->incubators as $incubatorId => $row) {
            $incubator = $report->incubators()->whereKey($incubatorId)->first();
            if ($incubator === null) {
                continue;
            }

            $incubatorTable = $this->fieldLocks->tableOf($incubator);
            $incubatorLocks = $this->fieldLocks->getForRow($incubatorTable, $incubator->id);

            $incubatorValues = [
                'no_id'                => $row['no_id']                ?? null,
                'calibration_date'     => $row['calibration_date']     ?? null,
                'due_date_calibration' => $row['due_date_calibration'] ?? null,
            ];

            $incubatorPayload = [];
            foreach ($incubatorValues as $field => $value) {
                if ($overrideLockOwnership || $this->fieldLocks->canEdit($incubatorLocks, $field, $actorId)) {
                    $incubatorPayload[$field] = $value;
                }
            }

            if (! empty($incubatorPayload)) {
                $incubator->fill($incubatorPayload)->save();
            }

            foreach ($incubatorValues as $field => $value) {
                if (array_key_exists($field, $incubatorPayload)) {
                    $this->fieldLocks->lockField($incubatorTable, $incubator->id, $field, $actorId, $value);
                }
            }

            foreach (($row['entries'] ?? []) as $mediumType => $entryRow) {
                $entry = $incubator->entries()->where('medium_type', $mediumType)->first();
                if ($entry === null) {
                    continue;
                }

                $entryTable = $this->fieldLocks->tableOf($entry);
                $entryLocks = $this->fieldLocks->getForRow($entryTable, $entry->id);

                $entryValues = [
                    'date_in'  => $entryRow['date_in']  ?? null,
                    'time_in'  => $entryRow['time_in']  ?? null,
                    'date_out' => $entryRow['date_out'] ?? null,
                    'time_out' => $entryRow['time_out'] ?? null,
                ];

                $hasExistingIncubatedActor = ! empty($entry->incubated_by);
                $hasExistingRemovedActor   = ! empty($entry->removed_by);

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

                $entryPayload['incubated_by'] = $hasIn  ? ($entry->incubated_by ?? $actorId) : $entry->incubated_by;
                $entryPayload['removed_by']   = $hasOut ? ($entry->removed_by   ?? $actorId) : $entry->removed_by;

                $entry->fill($entryPayload)->save();

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
    ): void
    {
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
                    $instance->note = $row['note'];
                    if ($instance->isDirty('note')) {
                        $instance->save();
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
                $colIdx  = (int) $colIdx;
                $columnLabelValue = MicrobialValue::normalise(
                    $colData['column_label_value'] ?? ($colData['sp_value'] ?? null)
                );
                $slots   = $colData['slots'] ?? [];

                foreach ($instance->instanceLocations as $loc) {
                    foreach ($loc->entries as $entry) {
                        if ($entry->column_index !== $colIdx) {
                            continue;
                        }
                        $slotKey = $entry->sub_column ?? '_';
                        $slot    = $slots[$slotKey] ?? null;

                        $rawTimeStart = $slot['time_start'] ?? null;
                        $rawTimeEnd   = $slot['time_end']   ?? null;
                        $timeStart    = ($rawTimeStart !== null && $rawTimeStart !== '') ? $rawTimeStart : null;
                        $timeEnd      = ($rawTimeEnd   !== null && $rawTimeEnd   !== '') ? $rawTimeEnd   : null;

                        $values = [
                            'time_start' => $timeStart,
                            'time_end'   => $timeEnd,
                            'column_label_value'   => $columnLabelValue,
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

                        $entry->fill($payload);
                        $dirtyFields = array_keys($entry->getDirty());
                        if (empty($dirtyFields)) {
                            continue;
                        }

                        $entry->save();
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
                $this->invalidateSignaturesForEditedMonitoringSection($instance);
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
            InstrumentEntry::firstOrCreate(
                ['report_id' => $report->id, 'tool_name' => $tool],
                ['tool_name' => $tool],
            );
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

            MediumEntry::firstOrCreate(
                [
                    'report_id' => $report->id,
                    'medium_id' => $mediumTemplate->id,
                ],
                [
                    'name'    => $mediumTemplate->name,
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
            $incubator = Incubator::firstOrCreate(
                [
                    'report_id'             => $report->id,
                    'incubator_template_id' => $incubatorTemplate->id,
                ],
                [],
            );

            $mediumTypes = ['monitoring'];
            if ($needsSwab) {
                $mediumTypes[] = 'swab';
            }

            foreach ($mediumTypes as $mediumType) {
                IncubatorEntry::firstOrCreate(
                    [
                        'incubator_id' => $incubator->id,
                        'medium_type'  => $mediumType,
                    ],
                    [],
                );
            }
        }
    }
}
