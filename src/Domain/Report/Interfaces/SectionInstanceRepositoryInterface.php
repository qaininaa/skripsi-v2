<?php

namespace Domain\Report\Interfaces;

use Domain\Report\Models\FieldLock;
use Domain\Report\Models\Report;
use Domain\Report\Models\SectionEntry;
use Domain\Report\Models\SectionInstance;
use Illuminate\Support\Collection;

/**
 * Contract for SectionInstance + child rows data access.
 */
interface SectionInstanceRepositoryInterface
{
    /**
     * Eager-load all section instances for a report.
     *
     * @return Collection<int, SectionInstance>
     */
    public function getInstancesForReport(Report $report): Collection;

    /**
     * Same as getInstancesForReport(), but also pre-computes the lock map
     * for all section_entries + section_instances rows of this report.
     *
     * @return array{
     *     instances: Collection<int, SectionInstance>,
     *     locks: array<string, array<string, array<string, FieldLock>>>,
     * }
     */
    public function getInstancesForReportWithLocks(Report $report): array;

    /**
     * Find a single instance with the relations needed by the form.
     */
    public function findForForm(string $instanceId): ?SectionInstance;

    /**
     * Find an instance scoped to a report. Optionally eager-load relations.
     *
     * @param  array<int, string>  $with
     */
    public function findInstanceForReport(string $reportId, string $instanceId, array $with = []): ?SectionInstance;

    /**
     * Whether a (report, section, instance_number=1) row already exists.
     */
    public function bootstrapInstanceExists(string $reportId, string $sectionId): bool;

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
     * Update an instance with the given attributes.
     */
    public function updateInstance(SectionInstance $instance, array $attributes): void;

    /**
     * Update an instance and return changed field names.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<int, string>
     */
    public function updateInstanceAndGetDirtyFields(SectionInstance $instance, array $attributes): array;

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

    /**
     * Update a SectionEntry row with the given attributes.
     */
    public function updateEntry(SectionEntry $entry, array $attributes): void;

    /**
     * Update a SectionEntry row and return changed field names.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<int, string>
     */
    public function updateEntryAndGetDirtyFields(SectionEntry $entry, array $attributes): array;

    /**
     * Eager-load relations on an existing section instance.
     *
     * @param  array<int, string>  $relations
     */
    public function loadRelations(SectionInstance $instance, array $relations): SectionInstance;

    /**
     * Get all instances for a report with section entries loaded.
     *
     * @return Collection<int, SectionInstance>
     */
    public function getInstancesWithEntriesForReport(string $reportId): Collection;

    /**
     * Get all instances for a report with signatures loaded.
     *
     * @return Collection<int, SectionInstance>
     */
    public function getInstancesWithSignaturesForReport(string $reportId): Collection;

    /**
     * Get primary keys for every section instance in a report.
     *
     * @return array<int, string>
     */
    public function getInstanceIdsForReport(string $reportId): array;

    /**
     * Delete a SectionInstance row.
     */
    public function deleteInstance(SectionInstance $instance): void;

    /**
     * Find a SectionInstance scoped to a report by its primary key.
     *
     * @param  array<int, string>  $with  Relations to eager-load.
     */
    public function findByReportAndKey(string $reportId, string $instanceId, array $with = []): ?SectionInstance;
}
