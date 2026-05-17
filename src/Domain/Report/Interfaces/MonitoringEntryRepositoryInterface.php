<?php

namespace Domain\Report\Interfaces;

use Domain\Report\Models\Incubator;
use Domain\Report\Models\IncubatorEntry;
use Domain\Report\Models\InstrumentEntry;
use Domain\Report\Models\MediumEntry;
use Domain\Report\Models\Report;

/**
 * Contract for the monitoring sub-entities of a Report:
 *   instrument_entries, medium_entries, incubators, incubator_entries.
 *
 * Used by MonitoringService both for bootstrap (when an analyst starts
 * monitoring a report) and for persisting the per-row form input.
 */
interface MonitoringEntryRepositoryInterface
{
    public function findOrCreateInstrumentEntry(string $reportId, string $toolName): InstrumentEntry;

    public function findInstrumentEntry(Report $report, string $toolName): ?InstrumentEntry;

    public function updateInstrumentEntry(InstrumentEntry $entry, array $payload): void;

    public function findOrCreateMediumEntry(string $reportId, string $mediumId, array $defaults = []): MediumEntry;

    public function findMediumEntry(Report $report, string $entryId): ?MediumEntry;

    public function updateMediumEntry(MediumEntry $entry, array $payload): void;

    public function findOrCreateIncubator(string $reportId, string $incubatorTemplateId): Incubator;

    public function findIncubator(Report $report, string $incubatorId): ?Incubator;

    public function updateIncubator(Incubator $incubator, array $payload): void;

    public function findOrCreateIncubatorEntry(string $incubatorId, string $mediumType): IncubatorEntry;

    public function findIncubatorEntry(Incubator $incubator, string $mediumType): ?IncubatorEntry;

    public function updateIncubatorEntry(IncubatorEntry $entry, array $payload): void;
}
