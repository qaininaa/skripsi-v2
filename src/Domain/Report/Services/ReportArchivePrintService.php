<?php

namespace Domain\Report\Services;

use Domain\Report\Models\SectionInstance;
use Domain\Report\Models\SectionSignature;
use Domain\Report\Support\MicrobialValue;
use Domain\Report\Support\SectionColumnLayout;
use Illuminate\Support\Collection;

class ReportArchivePrintService
{
    public function __construct(
        protected ReportArchiveService $archiveService,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function getPrintData(string $reportId): ?array
    {
        $data = $this->archiveService->getArchivedReportDetailData($reportId);
        if ($data === null) {
            return null;
        }

        return array_merge($data, [
            'roomMonitoring' => $this->roomMonitoring($data['report']),
            'printSections' => $this->printSections($data['sectionInstances']),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function roomMonitoring($report): array
    {
        $monitoringNames = $this->analystNames($report->analysts->where('type', 'monitoring'));
        $readingNames = $this->analystNames($report->analysts->where('type', 'reading'));

        return [
            'date' => ($report->monitoring_started_at ?? $report->created_at)->translatedFormat('d M Y'),
            'monitoringAnalysts' => $monitoringNames !== '' ? $monitoringNames : 'Belum ada analis',
            'readingAnalysts' => $readingNames !== '' ? $readingNames : 'Belum ada analis pembacaan',
            'productName' => $report->product_name,
            'batchNumber' => $report->batch_number,
        ];
    }

    private function analystNames(Collection $analysts): string
    {
        return $analysts
            ->map(fn ($analyst) => $analyst->user?->name)
            ->filter()
            ->unique()
            ->implode(', ');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function printSections(Collection $sectionInstances): array
    {
        return $sectionInstances
            ->map(fn (SectionInstance $instance) => $this->printSection($instance))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function printSection(SectionInstance $instance): array
    {
        $section = $instance->section;
        $columns = SectionColumnLayout::for($section);
        $entryMap = $this->entryMap($instance);

        return [
            'instance' => $instance,
            'section' => $section,
            'orientation' => $section->has_machine_setup && (int) $section->max_column >= 4
                ? 'landscape'
                : 'portrait',
            'columns' => $this->columns($instance, $columns, $entryMap),
            'rowsByFrequency' => $this->rowsByFrequency($instance, $columns, $entryMap),
            'signatures' => [
                'monitoring' => $this->signatureSummary($instance, SectionSignature::ROLE_MONITORING),
                'reading' => $this->signatureSummary($instance, SectionSignature::ROLE_READING),
                'review' => $this->signatureSummary($instance, SectionSignature::ROLE_REVIEW),
                'approval' => $this->signatureSummary($instance, SectionSignature::ROLE_APPROVAL),
            ],
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function rowsByFrequency(SectionInstance $instance, array $columns, array $entryMap): array
    {
        return $instance->instanceLocations
            ->groupBy(fn ($row) => $row->location?->frequency ?? 'unknown')
            ->map(function (Collection $rows) use ($columns, $entryMap) {
                return $rows->map(function ($row) use ($columns, $entryMap) {
                    return [
                        'row' => $row,
                        'location' => $row->location,
                        'room' => $row->location?->room,
                        'cells' => $this->rowCells($row, $columns, $entryMap),
                        'conclusion' => $row->entries->pluck('conclusion')->filter()->first(),
                    ];
                })->values()->all();
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rowCells($row, array $columns, array $entryMap): array
    {
        return array_map(function (array $column) use ($row, $entryMap) {
            $entry = $this->resolveEntry($row, $column, $entryMap);
            $bacteri = $entry?->cfu_bacteri;
            $fungi = $entry?->cfu_fungi;

            return [
                'bacteri' => $this->display($bacteri),
                'fungi' => $this->display($fungi),
                'total' => MicrobialValue::displayTotal($bacteri, $fungi) ?? 'N/A',
            ];
        }, $columns);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function columns(SectionInstance $instance, array $columns, array $entryMap): array
    {
        return array_map(function (array $column) use ($instance, $entryMap) {
            $subColumns = collect($column['sub_columns'])
                ->map(function (?string $subColumn) use ($instance, $column, $entryMap) {
                    $entry = $this->headerEntry($instance, $column['column_index'], $subColumn, $entryMap);

                    return [
                        'label' => $subColumn,
                        'sp' => $this->display($entry?->column_label_value),
                        'time' => $this->timeRange($entry?->time_start, $entry?->time_end),
                    ];
                })
                ->values()
                ->all();

            return array_merge($column, [
                'sub_columns' => $subColumns,
            ]);
        }, $columns);
    }

    private function signatureSummary(SectionInstance $instance, string $role): array
    {
        return $instance->signatures
            ->where('role', $role)
            ->sortBy(fn ($signature) => $signature->signed_at?->getTimestamp() ?? PHP_INT_MAX)
            ->map(fn ($signature) => [
                'name' => $signature->signer?->name ?? '-',
                'date' => $signature->signed_at?->translatedFormat('d M Y'),
                'time' => $signature->signed_at?->format('H:i'),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function entryMap(SectionInstance $instance): array
    {
        $map = [];

        foreach ($instance->instanceLocations as $row) {
            $map[$row->id] = [];
            foreach ($row->entries as $entry) {
                $map[$row->id][$entry->column_index][$entry->sub_column ?? '_'] = $entry;
            }
        }

        return $map;
    }

    private function headerEntry(SectionInstance $instance, int $columnIndex, ?string $subColumn, array $entryMap)
    {
        $key = $subColumn ?? '_';

        foreach ($instance->instanceLocations as $row) {
            if (isset($entryMap[$row->id][$columnIndex][$key])) {
                return $entryMap[$row->id][$columnIndex][$key];
            }
        }

        return null;
    }

    private function resolveEntry($row, array $column, array $entryMap)
    {
        $entries = $entryMap[$row->id][$column['column_index']] ?? [];
        if ($entries === []) {
            return null;
        }

        if (! $column['is_setup']) {
            $roomClass = $row->location?->room?->class;
            if ($roomClass !== null && isset($entries[$roomClass])) {
                return $entries[$roomClass];
            }
        }

        return $entries['_'] ?? collect($entries)->first();
    }

    private function timeRange(?string $start, ?string $end): string
    {
        if (($start === null || $start === '') && ($end === null || $end === '')) {
            return 'N/A';
        }

        return trim(($start ?: '-').' - '.($end ?: '-'));
    }

    private function display(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        return (string) $value;
    }
}
