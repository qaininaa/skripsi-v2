<?php

namespace Domain\Report\Services;

use Domain\Report\Dtos\SaveMonitoringDto;
use Domain\Report\Models\Analyst;
use Domain\Report\Models\Incubator;
use Domain\Report\Models\IncubatorEntry;
use Domain\Report\Models\InstrumentEntry;
use Domain\Report\Models\MediumEntry;
use Domain\Report\Models\Report;
use Illuminate\Support\Facades\DB;

/**
 * Handles the analyst monitoring workflow:
 *  - "Mulai pengerjaan" (lock the report and bootstrap entry rows)
 *  - Saving instrument / medium / incubator entries on the form
 *
 * Locking model:
 *  Once an analyst calls startMonitoring(), the report is owned by that analyst
 *  via locked_by. Other analysts may only view; only the locking analyst can
 *  continue editing until the report leaves in_progress.
 */
class MonitoringService
{
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
        if ($report->locked_by !== null && $report->locked_by !== $analystId) {
            throw new \RuntimeException('Laporan ini sedang dikerjakan oleh analis lain.');
        }

        return DB::transaction(function () use ($report, $analystId) {
            $report->locked_by             = $analystId;
            $report->status                = Report::STATUS_IN_PROGRESS;
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
     * Persist analyst form input: instrument, medium, incubator entries.
     *
     * Only the analyst who owns the report (locked_by) may save.
     *
     * @throws \RuntimeException When the caller is not the report owner.
     */
    public function saveMonitoring(Report $report, string $analystId, SaveMonitoringDto $dto): void
    {
        if ($report->locked_by !== $analystId) {
            throw new \RuntimeException('Anda bukan penanggung jawab monitoring laporan ini.');
        }

        DB::transaction(function () use ($report, $dto, $analystId) {
            // 2. Identitas Instrumen
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

            // 3. Identitas Medium
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

            // 4. Inkubator + entries
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
        });
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
