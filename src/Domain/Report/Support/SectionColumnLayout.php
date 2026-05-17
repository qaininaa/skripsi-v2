<?php

namespace Domain\Report\Support;

use Domain\ReportTemplate\Models\Section;

/**
 * Computes the (column_index, sub_column) pairs that a Section template
 * produces.
 *
 * Used by:
 *   - SectionInstanceService when creating empty SectionEntry rows
 *   - the form view to render headers + cells
 *
 * Column index conventions:
 *   has_machine_setup=true  → 0=Machine Setup, 1..N=Exposure I..N
 *   has_machine_setup=false → 0..N-1=Exposure I..N
 *
 * Sub-column conventions (per time_slot_type):
 *   start_end         → [null]
 *   start_end_ab      → ['A', 'B']
 *   start_end_multi   → ['S1', 'S1-2', 'S1-3']
 *   by_location       → [null]   (slot is per-row, not per-column)
 */
final class SectionColumnLayout
{
    /**
     * @return array<int, array{column_index:int, label:string, sub_columns: array<int, string|null>, is_setup: bool}>
     */
    public static function for(Section $section): array
    {
        $layout    = [];
        $maxColumn = max(1, (int) $section->max_column);
        $hasSetup  = (bool) $section->has_machine_setup;
        $expSubs   = self::subColumnsFor($section->time_slot_type);

        $idx = 0;

        if ($hasSetup) {
            // Machine Setup is always a single time slot regardless of the
            // section's time_slot_type — it is not class-split.
            $layout[] = [
                'column_index' => $idx++,
                'label'        => 'Machine Set-up',
                'sub_columns'  => [null],
                'is_setup'     => true,
            ];
        }

        for ($i = 1; $i <= $maxColumn; $i++) {
            $layout[] = [
                'column_index' => $idx++,
                'label'        => self::exposureLabel($i),
                'sub_columns'  => $expSubs,
                'is_setup'     => false,
            ];
        }

        return $layout;
    }

    /**
     * @return array<int, string|null>
     */
    private static function subColumnsFor(string $timeSlotType): array
    {
        return match ($timeSlotType) {
            'start_end_ab'    => ['A', 'B'],
            'start_end_multi' => ['S1', 'S1-2', 'S1-3'],
            default           => [null],
        };
    }

    private static function exposureLabel(int $n): string
    {
        return match ($n) {
            1 => 'Exposure I',
            2 => 'Exposure II',
            3 => 'Exposure III',
            4 => 'Exposure IV',
            5 => 'Exposure V',
            default => 'Exposure ' . $n,
        };
    }
}
