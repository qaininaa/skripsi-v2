<?php

namespace Domain\Report\Interfaces;

use Domain\Report\Models\Report;
use Domain\Report\Models\SectionInstance;
use Illuminate\Support\Collection;

/**
 * Contract for SectionInstance + child rows data access.
 *
 * Includes both reads (eager-loaded for the form view) and writes
 * (bootstrap on report creation, duplicate, save monitoring/reading).
 */
interface SectionInstanceRepositoryInterface
{
    /**
     * Eager-load all section instances for a report, including the data
     * needed by the fill-in form (sections, locations, entries, signatures).
     *
     * @return Collection<int, SectionInstance>
     */
    public function getInstancesForReport(Report $report): Collection;

    /**
     * Find a single instance with all relations needed for the form.
     */
    public function findForForm(string $instanceId): ?SectionInstance;

    /**
     * Next instance_number for a (report, section) pair.
     */
    public function nextInstanceNumber(string $reportId, string $sectionId): int;

    /**
     * Persist a new SectionInstance row.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createInstance(array $attributes): SectionInstance;

    /**
     * Persist N section_instance_locations rows for an instance.
     *
     * @param  array<int, array{location_id: string, display_order: int}>  $rows
     */
    public function createInstanceLocations(SectionInstance $instance, array $rows): void;

    /**
     * Persist a batch of section_entries rows for one instance_location.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function createEntries(string $instanceLocationId, array $rows): void;
}
