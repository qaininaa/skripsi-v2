<?php

namespace Domain\Report\Services;

use Domain\Report\Models\Report;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;

class ReportFormViewDataService
{
    /**
     * @return array{template: mixed, hasSwab: bool, instrumentEntries: Collection, mediumEntries: Collection, incubators: Collection}
     */
    public function forReport(Report $report): array
    {
        return [
            'template' => $report->reportTemplate,
            'hasSwab' => $report->reportTemplate?->hasSwab() ?? false,
            'instrumentEntries' => $this->instrumentEntries($report),
            'mediumEntries' => $this->mediumEntries($report),
            'incubators' => $this->incubators($report),
        ];
    }

    private function instrumentEntries(Report $report): Collection
    {
        $instrumentEntries = $report->instrumentEntries
            ->sortBy(fn ($instrument) => $instrument->tool_name === 'Swab Kit' ? 1 : 0)
            ->values();

        if ($instrumentEntries->isNotEmpty()) {
            return $instrumentEntries;
        }

        return collect([
            new Fluent([
                'id' => null,
                'tool_name' => 'Air Sampler',
                'no_id' => null,
                'calibration_date' => null,
                'due_date' => null,
            ]),
        ]);
    }

    private function mediumEntries(Report $report): Collection
    {
        $mediumEntries = $report->mediumEntries
            ->sortBy(fn ($medium) => $medium->is_swab ? 1 : 0)
            ->values();

        if ($mediumEntries->isNotEmpty() || $report->reportTemplate === null) {
            return $mediumEntries;
        }

        return $report->reportTemplate->mediumTemplates
            ->map(function ($template) {
                return new Fluent([
                    'id' => 'preview-'.$template->id,
                    'name' => $template->name,
                    'is_swab' => str_contains(strtolower($template->name), 'swab'),
                    'batch_number' => null,
                    'gpt_number' => null,
                    'expiration_date' => null,
                ]);
            })
            ->sortBy(fn ($medium) => $medium->is_swab ? 1 : 0)
            ->values();
    }

    private function incubators(Report $report): Collection
    {
        if ($report->incubators->isNotEmpty() || $report->reportTemplate === null) {
            return $report->incubators;
        }

        $hasSwab = $report->reportTemplate->hasSwab();

        return $report->reportTemplate->incubatorTemplates->map(function ($template) use ($hasSwab) {
            $entries = collect([
                $this->previewIncubatorEntry((string) $template->id, 'monitoring'),
            ]);

            if ($hasSwab) {
                $entries->push($this->previewIncubatorEntry((string) $template->id, 'swab'));
            }

            return new Fluent([
                'id' => 'preview-'.$template->id,
                'template' => $template,
                'entries' => $entries,
                'no_id' => null,
                'calibration_date' => null,
                'due_date_calibration' => null,
            ]);
        });
    }

    private function previewIncubatorEntry(string $templateId, string $mediumType): Fluent
    {
        return new Fluent([
            'id' => 'preview-'.$mediumType.'-'.$templateId,
            'medium_type' => $mediumType,
            'date_in' => null,
            'time_in' => null,
            'date_out' => null,
            'time_out' => null,
            'incubated_by' => null,
            'removed_by' => null,
            'incubatedBy' => null,
            'removedBy' => null,
        ]);
    }
}
