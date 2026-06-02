<?php

namespace Domain\Report\Repositories;

use Domain\Report\Interfaces\FieldLockRepositoryInterface;
use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
use Domain\Report\Models\FieldLock;
use Domain\Report\Models\Report;
use Domain\Report\Models\SectionEntry;
use Domain\Report\Models\SectionInstance;
use Illuminate\Support\Collection;

class SectionInstanceRepository implements SectionInstanceRepositoryInterface
{
    public function __construct(
        protected FieldLockRepositoryInterface $fieldLockRepository,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getInstancesForReport(Report $report): Collection
    {
        return SectionInstance::query()
            ->where('report_id', $report->id)
            ->with([
                'section',
                'parent.section',
                'instanceLocations.location.room',
                'instanceLocations.entries',
                'signatures.signer',
            ])
            ->orderBy('section_id')
            ->orderBy('instance_number')
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->section?->order ?? 0) <=> ($b->section?->order ?? 0),
                fn ($a, $b) => $a->instance_number <=> $b->instance_number,
            ])
            ->values();
    }

    /**
     * {@inheritDoc}
     */
    public function getInstancesForReportWithLocks(Report $report): array
    {
        $instances = $this->getInstancesForReport($report);

        // Section-level IDs
        $entryIds    = [];
        $instanceIds = [];
        foreach ($instances as $instance) {
            $instanceIds[] = $instance->id;
            foreach ($instance->instanceLocations as $loc) {
                foreach ($loc->entries as $entry) {
                    $entryIds[] = $entry->id;
                }
            }
        }

        // Header-section IDs (instruments / mediums / incubators)
        $report->loadMissing(['instrumentEntries', 'mediumEntries', 'incubators.entries']);

        $instrumentIds     = $report->instrumentEntries->pluck('id')->all();
        $mediumIds         = $report->mediumEntries->pluck('id')->all();
        $incubatorIds      = $report->incubators->pluck('id')->all();
        $incubatorEntryIds = $report->incubators
            ->flatMap(fn ($incubator) => $incubator->entries->pluck('id'))
            ->all();

        $byTable = [
            'section_entries'    => $entryIds,
            'section_instances'  => $instanceIds,
            'instrument_entries' => $instrumentIds,
            'medium_entries'     => $mediumIds,
            'incubators'         => $incubatorIds,
            'incubator_entries'  => $incubatorEntryIds,
        ];

        $locks = [];
        foreach ($byTable as $table => $ids) {
            $locks[$table] = [];
            if (empty($ids)) {
                continue;
            }
            $rows = $this->fieldLockRepository->getForRows($table, $ids)->load('filler');
            foreach ($rows as $lock) {
                $locks[$table][$lock->row_id][$lock->field_name] = $lock;
            }
        }

        return [
            'instances' => $instances,
            'locks'     => $locks,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function findForForm(string $instanceId): ?SectionInstance
    {
        return SectionInstance::query()
            ->with([
                'section',
                'instanceLocations.location.room',
                'instanceLocations.entries',
                'signatures.signer',
            ])
            ->find($instanceId);
    }

    /**
     * {@inheritDoc}
     */
    public function findInstanceForReport(string $reportId, string $instanceId, array $with = []): ?SectionInstance
    {
        return SectionInstance::query()
            ->where('report_id', $reportId)
            ->when(! empty($with), fn ($q) => $q->with($with))
            ->whereKey($instanceId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function bootstrapInstanceExists(string $reportId, string $sectionId): bool
    {
        return SectionInstance::query()
            ->where('report_id', $reportId)
            ->where('section_id', $sectionId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function nextInstanceNumber(string $reportId, string $sectionId): int
    {
        $max = SectionInstance::query()
            ->where('report_id', $reportId)
            ->where('section_id', $sectionId)
            ->max('instance_number');

        return ((int) $max) + 1;
    }

    /**
     * {@inheritDoc}
     */
    public function createInstance(array $attributes): SectionInstance
    {
        return SectionInstance::create($attributes);
    }

    /**
     * {@inheritDoc}
     */
    public function updateInstance(SectionInstance $instance, array $attributes): void
    {
        $instance->fill($attributes)->save();
    }

    /**
     * {@inheritDoc}
     */
    public function createInstanceLocations(SectionInstance $instance, array $rows): void
    {
        foreach ($rows as $row) {
            $instance->instanceLocations()->create([
                'location_id'   => $row['location_id'],
                'display_order' => $row['display_order'],
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createEntries(string $instanceLocationId, array $rows): void
    {
        $payload = array_map(
            fn ($row) => array_merge($row, [
                'section_instance_location_id' => $instanceLocationId,
            ]),
            $rows,
        );

        foreach ($payload as $attrs) {
            SectionEntry::create($attrs);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function updateEntry(SectionEntry $entry, array $attributes): void
    {
        $entry->fill($attributes)->save();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteInstance(SectionInstance $instance): void
    {
        $instance->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function findByReportAndKey(string $reportId, string $instanceId, array $with = []): ?SectionInstance
    {
        return SectionInstance::query()
            ->where('report_id', $reportId)
            ->when(! empty($with), fn ($q) => $q->with($with))
            ->when(empty($with), fn ($q) => $q->with('instanceLocations.entries'))
            ->whereKey($instanceId)
            ->first();
    }
}
