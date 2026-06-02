<?php

namespace Domain\Report\Repositories;

use Domain\Report\Interfaces\MonitoringEntryRepositoryInterface;
use Domain\Report\Models\Incubator;
use Domain\Report\Models\IncubatorEntry;
use Domain\Report\Models\InstrumentEntry;
use Domain\Report\Models\MediumEntry;
use Domain\Report\Models\Report;

class MonitoringEntryRepository implements MonitoringEntryRepositoryInterface
{
    /* ----------------------------- Instrument ----------------------------- */

    public function findOrCreateInstrumentEntry(string $reportId, string $toolName): InstrumentEntry
    {
        return InstrumentEntry::firstOrCreate(
            ['report_id' => $reportId, 'tool_name' => $toolName],
            ['tool_name' => $toolName],
        );
    }

    public function findInstrumentEntry(Report $report, string $toolName): ?InstrumentEntry
    {
        return $report->instrumentEntries()->where('tool_name', $toolName)->first();
    }

    public function updateInstrumentEntry(InstrumentEntry $entry, array $payload): void
    {
        $entry->fill($payload)->save();
    }

    /* ------------------------------ Medium -------------------------------- */

    public function findOrCreateMediumEntry(string $reportId, string $mediumId, array $defaults = []): MediumEntry
    {
        return MediumEntry::firstOrCreate(
            ['report_id' => $reportId, 'medium_id' => $mediumId],
            $defaults,
        );
    }

    public function findMediumEntry(Report $report, string $entryId): ?MediumEntry
    {
        return $report->mediumEntries()->whereKey($entryId)->first();
    }

    public function updateMediumEntry(MediumEntry $entry, array $payload): void
    {
        $entry->fill($payload)->save();
    }

    /* ----------------------------- Incubator ------------------------------ */

    public function findOrCreateIncubator(string $reportId, string $incubatorTemplateId): Incubator
    {
        return Incubator::firstOrCreate(
            ['report_id' => $reportId, 'incubator_template_id' => $incubatorTemplateId],
            [],
        );
    }

    public function findIncubator(Report $report, string $incubatorId): ?Incubator
    {
        return $report->incubators()->whereKey($incubatorId)->first();
    }

    public function updateIncubator(Incubator $incubator, array $payload): void
    {
        $incubator->fill($payload)->save();
    }

    /* -------------------------- Incubator Entry --------------------------- */

    public function findOrCreateIncubatorEntry(string $incubatorId, string $mediumType): IncubatorEntry
    {
        return IncubatorEntry::firstOrCreate(
            ['incubator_id' => $incubatorId, 'medium_type' => $mediumType],
            [],
        );
    }

    public function findIncubatorEntry(Incubator $incubator, string $mediumType): ?IncubatorEntry
    {
        return $incubator->entries()->where('medium_type', $mediumType)->first();
    }

    public function updateIncubatorEntry(IncubatorEntry $entry, array $payload): void
    {
        $entry->fill($payload)->save();
    }
}
