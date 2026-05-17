<?php

namespace Domain\Report\Support;

use Domain\Location\Models\Location;
use Domain\Report\Models\SectionEntry;
use Domain\Report\Models\SectionInstance;

/**
 * Computes per-location MS/TMS verdicts and the section-wide rollup.
 *
 * Rules:
 *   - A reading is "Action breach" when reading_total ≥ alert_action_total
 *     OR reading_fungi ≥ alert_action_fungi.
 *   - TNTC (PHP_INT_MAX from MicrobialValue) always breaches.
 *   - A row is TMS if ANY of its entries is an action breach.
 *   - A row with no readings yet remains null (not assessed).
 *   - Section's final_conclusion = TMS when ANY row TMS, MS otherwise.
 */
final class LocationConclusion
{
    /**
     * Compute the conclusion for one location row.
     *
     * @param  iterable<SectionEntry>  $entries
     */
    public static function forRow(Location $location, iterable $entries): ?string
    {
        $hasReading  = false;
        $anyBreach   = false;

        foreach ($entries as $entry) {
            $total = MicrobialValue::toCount($entry->reading_total);
            $fungi = MicrobialValue::toCount($entry->reading_fungi);

            if ($total === null && $fungi === null) {
                continue;
            }
            $hasReading = true;

            if ($total !== null && $location->alert_action_total !== null
                && $total >= $location->alert_action_total) {
                $anyBreach = true;
            }
            if ($fungi !== null && $location->alert_action_fungi !== null
                && $fungi >= $location->alert_action_fungi) {
                $anyBreach = true;
            }
        }

        if (! $hasReading) {
            return null;
        }

        return $anyBreach ? SectionEntry::CONCLUSION_TMS : SectionEntry::CONCLUSION_MS;
    }

    /**
     * Compute final_conclusion for the whole instance.
     *
     * Returns null if no row has readings yet.
     */
    public static function forInstance(SectionInstance $instance): ?string
    {
        $instance->loadMissing(['instanceLocations.entries']);

        $verdicts = [];
        foreach ($instance->instanceLocations as $row) {
            $verdict = $row->location_conclusion ?? null;
            // Recompute on the fly if not cached.
            if ($verdict === null) {
                $verdict = self::forRow($row->location, $row->entries);
            }
            if ($verdict !== null) {
                $verdicts[] = $verdict;
            }
        }

        if (empty($verdicts)) {
            return null;
        }

        return in_array(SectionInstance::CONCLUSION_TMS, $verdicts, true)
            ? SectionInstance::CONCLUSION_TMS
            : SectionInstance::CONCLUSION_MS;
    }
}
