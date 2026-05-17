<?php

namespace Domain\Report\Services;

use Domain\Location\Models\Location;
use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
use Domain\Report\Models\Report;
use Domain\Report\Models\SectionInstance;
use Domain\Report\Support\SectionColumnLayout;
use Domain\ReportTemplate\Models\Section;
use Illuminate\Support\Facades\DB;

/**
 * Lifecycle and structure-level operations for SectionInstance.
 *
 *   - bootstrapForReport(): called when Admin QC creates a new report. Creates
 *     instance_number=1 for every Section in the template, plus the location
 *     rows and empty entries.
 *
 *   - duplicate(): called when Admin QC clicks "Duplicate" on an existing
 *     section block. Copies the locations from the source instance, but
 *     entries (time, readings, sp) start blank.
 */
class SectionInstanceService
{
    public function __construct(
        protected SectionInstanceRepositoryInterface $repository,
    ) {
    }

    /**
     * Bootstrap section_instances + locations + entries for a freshly created
     * report. Idempotent: skips sections that already have an instance.
     */
    public function bootstrapForReport(Report $report): void
    {
        $template = $report->reportTemplate()->with('sections')->first();

        if ($template === null) {
            return;
        }

        foreach ($template->sections as $section) {
            $exists = SectionInstance::where('report_id', $report->id)
                ->where('section_id', $section->id)
                ->exists();
            if ($exists) {
                continue;
            }
            $this->createInitialInstance($report, $section);
        }
    }

    /**
     * Duplicate an existing section instance into a new sibling.
     *
     * @throws \RuntimeException When the report status disallows duplication.
     */
    public function duplicate(SectionInstance $source, ?string $reason = null): SectionInstance
    {
        $report = $source->report ?? Report::find($source->report_id);
        if ($report === null) {
            throw new \RuntimeException('Laporan tidak ditemukan.');
        }

        $allowed = [
            Report::STATUS_PENDING,
            Report::STATUS_IN_PROGRESS_MONITORING,
        ];
        if (! in_array($report->status, $allowed, true)) {
            throw new \RuntimeException(
                'Section hanya dapat diduplikasi sebelum tahap pembacaan dimulai.'
            );
        }

        return DB::transaction(function () use ($source, $report, $reason) {
            $next = $this->repository->nextInstanceNumber($source->report_id, $source->section_id);

            $copy = $this->repository->createInstance([
                'report_id'           => $source->report_id,
                'section_id'          => $source->section_id,
                'instance_number'     => $next,
                'parent_instance_id'  => $source->id,
                'duplication_reason'  => $reason,
            ]);

            // Snapshot the location rows from the source. Entries start blank.
            $sourceRows = $source->instanceLocations()->orderBy('display_order')->get();
            $rows = $sourceRows->map(fn ($row) => [
                'location_id'   => $row->location_id,
                'display_order' => $row->display_order,
            ])->all();

            $this->repository->createInstanceLocations($copy, $rows);

            $section = $source->section ?? Section::find($source->section_id);
            $copy->loadMissing('instanceLocations');
            foreach ($copy->instanceLocations as $row) {
                $this->createEmptyEntries($row->id, $section);
            }

            return $copy;
        });
    }

    /**
     * Create instance_number=1 plus its rows + entries for one Section.
     */
    private function createInitialInstance(Report $report, Section $section): SectionInstance
    {
        return DB::transaction(function () use ($report, $section) {
            $instance = $this->repository->createInstance([
                'report_id'       => $report->id,
                'section_id'      => $section->id,
                'instance_number' => 1,
            ]);

            $locations = Location::where('section_id', $section->id)
                ->orderBy('section_assigned_at')
                ->orderBy('created_at')
                ->get();

            $rows = $locations->values()->map(fn ($loc, $index) => [
                'location_id'   => $loc->id,
                'display_order' => $index,
            ])->all();
            $this->repository->createInstanceLocations($instance, $rows);

            $instance->loadMissing('instanceLocations');
            foreach ($instance->instanceLocations as $row) {
                $this->createEmptyEntries($row->id, $section);
            }

            return $instance;
        });
    }

    /**
     * Build empty section_entries rows for one location row,
     * matching the section's column layout.
     */
    private function createEmptyEntries(string $instanceLocationId, Section $section): void
    {
        $rows = [];
        foreach (SectionColumnLayout::for($section) as $column) {
            foreach ($column['sub_columns'] as $sub) {
                $rows[] = [
                    'column_index' => $column['column_index'],
                    'sub_column'   => $sub,
                ];
            }
        }
        $this->repository->createEntries($instanceLocationId, $rows);
    }
}
