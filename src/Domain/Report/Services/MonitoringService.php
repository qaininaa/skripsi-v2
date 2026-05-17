<?php

namespace Domain\Report\Services;

use Domain\Report\Dtos\SaveMonitoringDto;
use Domain\Report\Models\Analyst;
use Domain\Report\Models\Incubator;
use Domain\Report\Models\IncubatorEntry;
use Domain\Report\Models\InstrumentEntry;
use Domain\Report\Models\MediumEntry;
use Domain\Report\Models\Report;
use Domain\Report\Models\SectionInstance;
use Domain\Report\Models\SectionSignature;
use Domain\Report\Support\MicrobialValue;
use Illuminate\Support\Facades\DB;

/**
 * Handles the analyst monitoring workflow:
 *  - "Mulai pengerjaan" (lock the report and bootstrap entry rows)
 *  - Saving instrument / medium / incubator entries on the form
 *  - Saving section monitoring data (time_start, time_end, sp_value)
 *  - Finalizing the monitoring phase — moves the report into "in_progress_reading"
 *
 * Locking model:
 *  Once an analyst calls startMonitoring(), the report is owned by that analyst
 *  via locked_by. Other analysts may only view; only the locking analyst can
 *  continue editing until the report leaves in_progress.
 *
 *  When the analyst chooses "Simpan Monitoring" (draft), locked_by is cleared
 *  so a fellow analyst can take over. The report status remains
 *  in_progress_monitoring.
 *
 *  When the analyst chooses "Selesaikan Monitoring & Mulai Pembacaan", the
 *  monitoring signature is recorded for every section instance and the report
 *  transitions into in_progress_reading; locked_by is cleared so a reading
 *  analyst can pick it up.
 */
class MonitoringService
{
    public const ACTION_DRAFT    = 'draft';                  // save & keep lock; analyst stays on the form.
    public const ACTION_RELEASE  = 'release';                // save & release lock; analis lain bisa lanjut.
    public const ACTION_FINALIZE = 'finalize_monitoring';    // sign off + transition to reading phase.

    /**
     * Tool names that always appear in section "Identitas Instrumen".
     * The default first row is "Air Sampler" (cannot be edited by name).
     */
    private const DEFAULT_INSTRUMENTS = ['Air Sampler'];

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
    public function saveMonitoring(Report $report, string $analystId, SaveMonitoringDto $dto, string $action = self::ACTION_DRAFT): void
    {
        if ($report->locked_by !== $analystId) {
            throw new \RuntimeException('Anda bukan penanggung jawab monitoring laporan ini.');
        }

        if (! in_array($action, [self::ACTION_DRAFT, self::ACTION_RELEASE, self::ACTION_FINALIZE], true)) {
            throw new \RuntimeException('Aksi penyimpanan tidak dikenali.');
        }

        DB::transaction(function () use ($report, $dto, $analystId, $action) {
            $this->saveInstrumentRows($report, $dto);
            $this->saveMediumRows($report, $dto);
            $this->saveIncubatorRows($report, $dto, $analystId);
            $this->saveSectionMonitoringRows($report, $dto, $analystId);

            if ($action === self::ACTION_FINALIZE) {
                $this->finalizeMonitoring($report, $analystId);
            } elseif ($action === self::ACTION_RELEASE) {
                // Release the lock so a fellow analyst may take over.
                $report->locked_by = null;
                $report->save();
            }
            // ACTION_DRAFT: keep locked_by on the current analyst.
        });
    }

    /**
     * Finalize the monitoring phase. Records SectionSignature(role=monitoring)
     * for every instance and transitions report status.
     */
    private function finalizeMonitoring(Report $report, string $analystId): void
    {
        $report->loadMissing('sectionInstances');
        $now = now();

        foreach ($report->sectionInstances as $instance) {
            SectionSignature::firstOrCreate(
                [
                    'section_instance_id' => $instance->id,
                    'role'                => SectionSignature::ROLE_MONITORING,
                ],
                [
                    'signed_by' => $analystId,
                    'signed_at' => $now,
                ],
            );
        }

        $report->status    = Report::STATUS_IN_PROGRESS_READING;
        $report->locked_by = null;
        $report->save();
    }

    private function saveInstrumentRows(Report $report, SaveMonitoringDto $dto): void
    {
        foreach ($dto->instruments as $toolName => $row) {
            $instrument = $report->instrumentEntries()->where('tool_name', $toolName)->first();
            if ($instrument === null) {
                continue;
            }
            $instrument->fill([
                'no_id'            => $row['no_id']            ?? null,
                'calibration_date' => $row['calibration_date'] ?? null,
                'due_date'         => $row['due_date']         ?? null,
            ])->save();
        }
    }

    private function saveMediumRows(Report $report, SaveMonitoringDto $dto): void
    {
        foreach ($dto->mediums as $entryId => $row) {
            $entry = $report->mediumEntries()->whereKey($entryId)->first();
            if ($entry === null) {
                continue;
            }
            $payload = [
                'batch_number'    => $row['batch_number']    ?? null,
                'expiration_date' => $row['expiration_date'] ?? null,
            ];
            // Swab Kit must never carry a GPT number.
            if (! $entry->is_swab) {
                $payload['gpt_number'] = $row['gpt_number'] ?? null;
            }
            $entry->fill($payload)->save();
        }
    }

    private function saveIncubatorRows(Report $report, SaveMonitoringDto $dto, string $analystId): void
    {
        foreach ($dto->incubators as $incubatorId => $row) {
            $incubator = $report->incubators()->whereKey($incubatorId)->first();
            if ($incubator === null) {
                continue;
            }
            $incubator->fill([
                'no_id'                => $row['no_id']                ?? null,
                'calibration_date'     => $row['calibration_date']     ?? null,
                'due_date_calibration' => $row['due_date_calibration'] ?? null,
            ])->save();

            foreach (($row['entries'] ?? []) as $mediumType => $entryRow) {
                $entry = $incubator->entries()->where('medium_type', $mediumType)->first();
                if ($entry === null) {
                    continue;
                }

                $hasIn  = ! empty($entryRow['date_in'])  || ! empty($entryRow['time_in']);
                $hasOut = ! empty($entryRow['date_out']) || ! empty($entryRow['time_out']);

                $entry->fill([
                    'date_in'      => $entryRow['date_in']  ?? null,
                    'time_in'      => $entryRow['time_in']  ?? null,
                    'date_out'     => $entryRow['date_out'] ?? null,
                    'time_out'     => $entryRow['time_out'] ?? null,
                    'incubated_by' => $hasIn  ? ($entry->incubated_by ?? $analystId) : $entry->incubated_by,
                    'removed_by'   => $hasOut ? ($entry->removed_by   ?? $analystId) : $entry->removed_by,
                ])->save();
            }
        }
    }

    /**
     * Persist time_start, time_end, sp_value, plus the catatan text
     * for every section_instance the form posted.
     *
     * Form shape:
     *   sections[{id}][note]
     *   sections[{id}][columns][{idx}][sp_value]
     *   sections[{id}][columns][{idx}][slots][{label_or_underscore}][time_start|time_end]
     *
     * Same SP / time values are broadcast to every entry of the matching
     * (column_index, sub_column) — i.e. for an A/B section the entry whose
     * sub_column='A' receives the slot 'A' time, sub_column='B' receives
     * slot 'B' time, regardless of how many physical rows there are.
     */
    private function saveSectionMonitoringRows(Report $report, SaveMonitoringDto $dto, string $analystId): void
    {
        foreach ($dto->sections as $instanceId => $row) {
            $instance = SectionInstance::with('instanceLocations.entries')
                ->where('report_id', $report->id)
                ->whereKey($instanceId)
                ->first();
            if ($instance === null) {
                continue;
            }

            if (array_key_exists('note', $row)) {
                $instance->note = $row['note'];
                $instance->save();
            }

            $columns = $row['columns'] ?? [];
            foreach ($columns as $colIdx => $colData) {
                $colIdx  = (int) $colIdx;
                $spValue = MicrobialValue::normalise($colData['sp_value'] ?? null);
                $slots   = $colData['slots'] ?? [];

                foreach ($instance->instanceLocations as $loc) {
                    foreach ($loc->entries as $entry) {
                        if ($entry->column_index !== $colIdx) {
                            continue;
                        }
                        $slotKey = $entry->sub_column ?? '_';
                        $slot    = $slots[$slotKey] ?? null;

                        $timeStart = $slot['time_start'] ?? null;
                        $timeEnd   = $slot['time_end']   ?? null;

                        $entry->fill([
                            'sp_value'             => $spValue,
                            'time_start'           => $timeStart !== '' ? $timeStart : null,
                            'time_end'             => $timeEnd   !== '' ? $timeEnd   : null,
                            'filled_by_monitoring' => $entry->filled_by_monitoring ?? $analystId,
                        ])->save();
                    }
                }
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
