<?php

namespace Domain\Report\Repositories;

use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
use Domain\Report\Models\Report;
use Domain\Report\Models\SectionEntry;
use Domain\Report\Models\SectionInstance;
use Illuminate\Support\Collection;

class SectionInstanceRepository implements SectionInstanceRepositoryInterface
{
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
            // Sort in PHP by section.order so the UI ordering matches the template.
            ->sortBy([
                fn ($a, $b) => ($a->section?->order ?? 0) <=> ($b->section?->order ?? 0),
                fn ($a, $b) => $a->instance_number <=> $b->instance_number,
            ])
            ->values();
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

        // Use the model so HasUuids fires the boot event for ids.
        foreach ($payload as $attrs) {
            SectionEntry::create($attrs);
        }
    }
}
