{{--
    Section instance card matching the lab UI mockup:

    Header (3 rows):
      Row 1: identitas (rowspan=3) | <measurement_unit> spanning all columns
             | Alert Limit colspan=2 rowspan=2 | Alert Action colspan=2 rowspan=2
             | Kesimpulan rowspan=3
      Row 2: per column → label, optional SP single, time slot rows (A/B/...)
      Row 3: per column → B/F/T leaf headers; alert limit/action → T,F leaf headers

    Body row picks the entry whose sub_column matches the row's room.class
    (start_end_ab/multi). Machine Setup is always single-slot regardless of
    section's time_slot_type.

    Form name conventions used here:
      SP        sections[{ID}][columns][{idx}][sp_value]
      Time slot sections[{ID}][columns][{idx}][slots][{label|_}][time_start|time_end]
      Reading   sections[{ID}][rows][{loc_id}][readings][{idx}][reading_total|reading_fungi]
      Note      sections[{ID}][note]

    Props:
      $instance, $report, $phase, $readonly, $isAdmin
--}}
@props([
    'instance',
    'report',
    'phase'    => 'monitoring',
    'readonly' => true,
    'isAdmin'  => false,
    'lockMap'  => [],
])

@php
    use Domain\Report\Support\SectionColumnLayout;

    $section = $instance->section;
    $columns = SectionColumnLayout::for($section);
    $rows    = $instance->instanceLocations;

    $canEditMonitoring = ! $readonly && $phase === 'monitoring';
    $canEditReading    = ! $readonly && $phase === 'reading';

    $currentUserId = (string) (auth()->id() ?? '');

    /*
     * Lock helpers — `$lockMap` is shaped as
     *   [table_name][row_id][field_name] => FieldLock
     * and is pre-computed by the repository.
     */
    $isLockedByOther = function (string $table, ?string $rowId, string $field) use ($lockMap, $currentUserId) {
        if ($rowId === null) {
            return false;
        }
        $lock = $lockMap[$table][$rowId][$field] ?? null;
        return $lock !== null && (string) $lock->filled_by !== $currentUserId;
    };
    $lockOwner = function (string $table, ?string $rowId, string $field) use ($lockMap) {
        if ($rowId === null) {
            return null;
        }
        $lock = $lockMap[$table][$rowId][$field] ?? null;
        return $lock?->filler?->name;
    };

    /*
     * Index entries by [instance_location_id][column_index][sub_column_key].
     * sub_column_key is the literal sub_column value or '_' for null.
     */
    $entryMap = [];
    foreach ($rows as $row) {
        $entryMap[$row->id] = [];
        foreach ($row->entries as $entry) {
            $entryMap[$row->id][$entry->column_index][$entry->sub_column ?? '_'] = $entry;
        }
    }

    /**
     * Pick a representative entry for the (column_index, sub_column) header
     * cell so we can read SP / time values. Any matching entry will do —
     * the service writes the same value to all matching rows.
     */
    $headerEntry = function (int $colIdx, ?string $sub) use ($rows, $entryMap) {
        $key = $sub ?? '_';
        foreach ($rows as $row) {
            if (isset($entryMap[$row->id][$colIdx][$key])) {
                return $entryMap[$row->id][$colIdx][$key];
            }
        }
        return null;
    };

    /**
     * For a row + column, resolve the entry whose sub_column matches the
     * row's room class (start_end_ab) or the only/first entry (else).
     */
    $resolveEntry = function ($row, array $col) use ($entryMap) {
        $bag = $entryMap[$row->id][$col['column_index']] ?? [];
        if (empty($bag)) {
            return null;
        }
        if (! $col['is_setup']) {
            $rowClass = $row->location?->room?->class;
            if ($rowClass !== null && in_array($rowClass, ['A', 'B'], true) && isset($bag[$rowClass])) {
                return $bag[$rowClass];
            }
        }
        return $bag['_'] ?? collect($bag)->first();
    };

    $sigByRole = $instance->signatures->keyBy('role');

    $statusForDuplicate = in_array($report->status, ['pending', 'in_progress_monitoring'], true);

    $namePrefix = "sections[{$instance->id}]";

    // Sort rows by frequency for the FREKUENSI separator.
    $rowsByFrequency = $rows->groupBy(fn ($r) => $r->location?->frequency ?? 'unknown');

    $frequencyLabels = [
        'operational'  => 'OPERASIONAL',
        'daily'        => 'HARIAN',
        'weekly'       => 'MINGGUAN',
        'monthly'      => 'BULANAN',
        'semi_annual'  => 'SEMESTER',
        'unknown'      => 'LAINNYA',
    ];

    /*
     * Render helper: alert limit/action value display.
     *   - null/empty  → italic grey "N/A"
     *   - 1           → "<1"  (lab convention)
     *   - other ints  → as-is
     */
    $alertValue = function ($value, string $colorClass = 'text-gray-700') {
        if ($value === null || $value === '') {
            return '<span class="italic text-gray-300">N/A</span>';
        }
        $display = ((int) $value === 1) ? '&lt;1' : e((string) $value);
        return '<span class="font-semibold ' . $colorClass . '">' . $display . '</span>';
    };

    $headColCount = count($columns);
@endphp

<section
    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100"
    data-conclusion-section
    data-instance-id="{{ $instance->id }}"
>
    {{-- Section header --}}
    <div class="mb-3 flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                {{ $instance->displayLabel() }}
            </span>
            <div>
                <h2 class="text-base font-bold text-gray-800">{{ $section->measurement_unit }}</h2>
                <p class="mt-0.5 text-xs text-gray-500">
                    {{ $section->getMeasurementTypeLabel() }}
                    @if ($instance->parent_instance_id !== null)
                        <span class="ml-2 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">DUPLIKAT</span>
                        @if ($instance->duplication_reason)
                            <span class="ml-1 text-[11px] text-gray-400">— {{ $instance->duplication_reason }}</span>
                        @endif
                    @endif
                </p>
            </div>
        </div>

        @if ($isAdmin && $statusForDuplicate && $instance->parent_instance_id === null)
            <button
                type="button"
                @click.prevent="$store.duplicateSectionModal.open({
                    action: @js(route('report-assignment.sections.duplicate', [$report, $instance])),
                    sectionLabel: @js($instance->displayLabel() . ' — ' . $section->measurement_unit),
                })"
                class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"
            >
                + Duplikat Section
            </button>
        @endif

        @if ($isAdmin && $statusForDuplicate && $instance->parent_instance_id !== null)
            <form
                method="POST"
                action="{{ route('report-assignment.sections.duplicate.destroy', [$report, $instance]) }}"
                onsubmit="return confirm('Hapus section duplikat ini?');"
            >
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100"
                >
                    Hapus Duplikat
                </button>
            </form>
        @endif
    </div>

    {{-- Sampling table --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full border-collapse text-xs">
            <thead class="bg-sky-50 text-gray-700">
                {{-- Row 1: top --}}
                <tr class="text-center">
                    <th rowspan="3" class="border-b border-r border-sky-100 px-2 py-2 align-middle">No.</th>
                    <th rowspan="3" class="border-b border-r border-sky-100 px-2 py-2 align-middle text-center min-w-[10rem]">Nama<br>Ruangan</th>
                    <th rowspan="3" class="border-b border-r border-sky-100 px-2 py-2 align-middle">Kelas</th>
                    <th rowspan="3" class="border-b border-r border-sky-100 px-2 py-2 align-middle">No.<br>Ruangan</th>
                    <th rowspan="3" class="border-b border-r border-sky-100 px-2 py-2 align-middle">No.<br>Lokasi</th>

                    <th colspan="{{ $headColCount }}" class="border-b border-x border-sky-100 px-2 py-2 text-sm font-semibold">
                        {{ $section->measurement_unit }}
                    </th>

                    <th colspan="2" rowspan="2" class="border-b border-x border-sky-100 px-2 py-2 align-middle">
                        Alert<br>Limit
                    </th>
                    <th colspan="2" rowspan="2" class="border-b border-x border-sky-100 px-2 py-2 align-middle">
                        Alert<br>Action
                    </th>
                    <th rowspan="3" class="border-b border-l border-sky-100 px-2 py-2 align-middle">Kesimpulan</th>
                </tr>

                {{-- Row 2: per-column header (label + SP + time slots) --}}
                <tr class="text-center">
                    @foreach ($columns as $col)
                        @php
                            $colNamePrefix = "{$namePrefix}[columns][{$col['column_index']}]";
                            // Add an invisible "spacer" line to Machine Setup so the
                            // total header height matches multi-slot columns and
                            // every main column gets the same width.
                            $needsSpacer = $col['is_setup'] && count($columns) > 1
                                ? max(0, max(array_map(fn ($c) => count($c['sub_columns']), $columns)) - 1)
                                : 0;
                        @endphp
                        <th class="w-[200px] min-w-[200px] border-b border-x border-sky-100 px-2 py-2 align-top">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-700">
                                {{ $col['label'] }}
                            </div>

                            {{-- SP single, only on non-machine-setup columns --}}
                            @if (! $col['is_setup'])
                                @php
                                    $spEntry      = $headerEntry($col['column_index'], $col['sub_columns'][0] ?? null);
                                    $spLockedBy   = $spEntry ? $lockOwner('section_entries', $spEntry->id, 'sp_value') : null;
                                    $spLocked     = $spEntry ? $isLockedByOther('section_entries', $spEntry->id, 'sp_value') : false;
                                    $isSettlePlate = $section->measurement_type === 'settle_plate';
                                    $spLabel       = $isSettlePlate ? 'SP:' : 'Shift:';
                                @endphp
                                <div class="mt-1 flex items-center justify-center gap-1 text-[10px]">
                                    <span class="text-gray-500">{{ $spLabel }}</span>
                                    @if ($canEditMonitoring && ! $spLocked)
                                        <input
                                            type="text"
                                            name="{{ $colNamePrefix }}[sp_value]"
                                            value="{{ old("sections.{$instance->id}.columns.{$col['column_index']}.sp_value", $spEntry?->sp_value) }}"
                                            class="w-14 rounded border border-gray-300 bg-white px-1 py-0.5 text-center text-[10px] focus:border-blue-500 focus:outline-none"
                                            placeholder="{{ $isSettlePlate ? 'SP' : 'Shift' }}"
                                        >
                                    @elseif ($canEditMonitoring && $spLocked)
                                        <input
                                            type="text"
                                            value="{{ $spEntry?->sp_value }}"
                                            disabled
                                            class="w-14 cursor-not-allowed rounded border border-gray-200 bg-gray-100 px-1 py-0.5 text-center text-[10px] text-gray-400"
                                        >
                                    @else
                                        <span class="font-medium text-gray-700">{{ $spEntry?->sp_value ?: 'N/A' }}</span>
                                    @endif
                                </div>
                                @if ($canEditMonitoring && $spLocked)
                                    <div class="mt-0.5 text-center text-[9px] italic text-gray-400">
                                        Diisi oleh {{ $spLockedBy }}
                                    </div>
                                @endif
                            @else
                                {{-- Reserve vertical space so MS header lines up with SP rows in other columns --}}
                                <div class="mt-1 h-[18px] text-[10px] text-gray-300">N/A</div>
                            @endif

                            {{-- Time slot row(s) --}}
                            <div class="mt-1 space-y-1">
                                @foreach ($col['sub_columns'] as $sub)
                                    @php
                                        $entry     = $headerEntry($col['column_index'], $sub);
                                        $slotKey   = $sub ?? '_';
                                        $slotName  = "{$colNamePrefix}[slots][{$slotKey}]";
                                        $valStart  = old("sections.{$instance->id}.columns.{$col['column_index']}.slots.{$slotKey}.time_start", $entry?->time_start);
                                        $valEnd    = old("sections.{$instance->id}.columns.{$col['column_index']}.slots.{$slotKey}.time_end",   $entry?->time_end);
                                        $startLockedBy = $entry ? $lockOwner('section_entries', $entry->id, 'time_start') : null;
                                        $endLockedBy   = $entry ? $lockOwner('section_entries', $entry->id, 'time_end')   : null;
                                        $startLocked   = $entry ? $isLockedByOther('section_entries', $entry->id, 'time_start') : false;
                                        $endLocked     = $entry ? $isLockedByOther('section_entries', $entry->id, 'time_end')   : false;
                                    @endphp
                                    <div class="flex items-center justify-center gap-1 whitespace-nowrap text-[10px]">
                                        @if ($sub !== null)
                                            <span class="font-semibold text-gray-700">{{ $sub }}:</span>
                                        @endif
                                        @if ($canEditMonitoring && ! $startLocked)
                                            <input
                                                type="time"
                                                name="{{ $slotName }}[time_start]"
                                                value="{{ $valStart }}"
                                                class="w-[4.5rem] rounded border border-gray-300 bg-white px-1 py-0.5 text-center text-[10px] focus:border-blue-500 focus:outline-none"
                                            >
                                        @elseif ($canEditMonitoring && $startLocked)
                                            <input
                                                type="time"
                                                value="{{ $valStart }}"
                                                disabled
                                                class="w-[4.5rem] cursor-not-allowed rounded border border-gray-200 bg-gray-100 px-1 py-0.5 text-center text-[10px] text-gray-400"
                                            >
                                        @else
                                            <span class="text-gray-600">{{ $valStart ?: '--:--' }}</span>
                                        @endif
                                        <span class="text-gray-400">—</span>
                                        @if ($canEditMonitoring && ! $endLocked)
                                            <input
                                                type="time"
                                                name="{{ $slotName }}[time_end]"
                                                value="{{ $valEnd }}"
                                                class="w-[4.5rem] rounded border border-gray-300 bg-white px-1 py-0.5 text-center text-[10px] focus:border-blue-500 focus:outline-none"
                                            >
                                        @elseif ($canEditMonitoring && $endLocked)
                                            <input
                                                type="time"
                                                value="{{ $valEnd }}"
                                                disabled
                                                class="w-[4.5rem] cursor-not-allowed rounded border border-gray-200 bg-gray-100 px-1 py-0.5 text-center text-[10px] text-gray-400"
                                            >
                                        @else
                                            <span class="text-gray-600">{{ $valEnd ?: '--:--' }}</span>
                                        @endif
                                    </div>
                                    @if ($canEditMonitoring && ($startLocked || $endLocked))
                                        <div class="mt-0.5 text-center text-[9px] italic text-gray-400">
                                            Diisi oleh {{ $startLockedBy ?? $endLockedBy }}
                                        </div>
                                    @endif
                                @endforeach

                                {{-- Pad MS header so its height matches other columns --}}
                                @for ($i = 0; $i < $needsSpacer; $i++)
                                    <div class="invisible flex items-center justify-center gap-1 whitespace-nowrap text-[10px]">
                                        <span>--:--</span><span>—</span><span>--:--</span>
                                    </div>
                                @endfor
                            </div>
                        </th>
                    @endforeach
                </tr>

                {{-- Row 3: B / F / T leaf headers + Alert T/F leaf headers --}}
                <tr class="bg-sky-100/60 text-center text-[11px] font-semibold text-gray-700">
                    @foreach ($columns as $col)
                        <th class="border-b border-x border-sky-100 px-1 py-1">
                            <div class="grid grid-cols-3">
                                <span>B</span>
                                <span>F</span>
                                <span>T</span>
                            </div>
                        </th>
                    @endforeach
                    <th class="w-14 min-w-[3.5rem] border-b border-x border-sky-100 px-2 py-1 whitespace-nowrap">T</th>
                    <th class="w-14 min-w-[3.5rem] border-b border-x border-sky-100 px-2 py-1 whitespace-nowrap">F</th>
                    <th class="w-14 min-w-[3.5rem] border-b border-x border-sky-100 px-2 py-1 whitespace-nowrap">T</th>
                    <th class="w-14 min-w-[3.5rem] border-b border-x border-sky-100 px-2 py-1 whitespace-nowrap">F</th>
                </tr>
            </thead>

            <tbody class="text-center">
                @forelse ($rowsByFrequency as $frequency => $groupRows)
                    {{-- Frequency separator --}}
                    <tr class="bg-blue-50/60">
                        <td
                            colspan="{{ 5 + $headColCount + 4 + 1 }}"
                            class="border-y border-blue-100 px-3 py-1 text-left text-[11px] font-semibold tracking-wide text-blue-700"
                        >
                            FREKUENSI : {{ $frequencyLabels[$frequency] ?? strtoupper((string) $frequency) }}
                        </td>
                    </tr>

                    @foreach ($groupRows as $row)
                        @php
                            $location = $row->location;
                            $room     = $location?->room;
                            $verdict  = collect($row->entries)
                                ->pluck('location_conclusion')
                                ->filter()
                                ->first();
                            $rowName = "{$namePrefix}[rows][{$row->id}]";

                            $alertActionTotal = $location?->alert_action_total;
                            $alertActionFungi = $location?->alert_action_fungi;
                        @endphp
                        <tr
                            class="border-b border-gray-100 hover:bg-gray-50/40"
                            data-conclusion-row
                            data-instance-id="{{ $instance->id }}"
                            data-row-id="{{ $row->id }}"
                            data-action-total="{{ $alertActionTotal ?? '' }}"
                            data-action-fungi="{{ $alertActionFungi ?? '' }}"
                        >
                            <td class="border-r border-gray-100 px-2 py-2">{{ $loop->iteration }}</td>
                            <td class="border-r border-gray-100 px-2 py-2 text-left text-gray-700 min-w-[10rem]">{{ $room?->name ?? 'N/A' }}</td>
                            <td class="border-r border-gray-100 px-2 py-2">
                                @if ($room?->class)
                                    <x-badges.room-class :class="$room->class" />
                                @else
                                    <span class="italic text-gray-300">N/A</span>
                                @endif
                            </td>
                            <td class="border-r border-gray-100 px-2 py-2">{{ $room?->room_number ?? 'N/A' }}</td>
                            <td class="border-r border-gray-100 px-2 py-2">{{ $location?->loc_number ?? 'N/A' }}</td>

                            @foreach ($columns as $col)
                                @php
                                    $entry      = $resolveEntry($row, $col);
                                    $readName   = "{$rowName}[readings][{$col['column_index']}]";
                                    $oldB       = old("sections.{$instance->id}.rows.{$row->id}.readings.{$col['column_index']}.reading_total", $entry?->reading_total);
                                    $oldF       = old("sections.{$instance->id}.rows.{$row->id}.readings.{$col['column_index']}.reading_fungi", $entry?->reading_fungi);
                                    $hasTime    = $entry?->hasMonitoringTime() ?? false;
                                    $b          = is_numeric($oldB) ? (int) $oldB : null;
                                    $f          = is_numeric($oldF) ? (int) $oldF : null;
                                    $tt         = ($b !== null || $f !== null) ? ((int)($b ?? 0) + (int)($f ?? 0)) : null;
                                    $bLockedBy  = $entry ? $lockOwner('section_entries', $entry->id, 'reading_total') : null;
                                    $fLockedBy  = $entry ? $lockOwner('section_entries', $entry->id, 'reading_fungi') : null;
                                    $bLocked    = $entry ? $isLockedByOther('section_entries', $entry->id, 'reading_total') : false;
                                    $fLocked    = $entry ? $isLockedByOther('section_entries', $entry->id, 'reading_fungi') : false;
                                @endphp
                                <td class="border-x border-gray-100 px-1 py-1">
                                    <div class="grid grid-cols-3 gap-x-1 text-[11px]">
                                        {{-- B --}}
                                        <div>
                                            @if ($canEditReading && $hasTime && ! $bLocked)
                                                <input
                                                    type="text"
                                                    name="{{ $readName }}[reading_total]"
                                                    value="{{ $oldB }}"
                                                    data-microbial
                                                    data-reading="total"
                                                    data-row-id="{{ $row->id }}"
                                                    data-col-index="{{ $col['column_index'] }}"
                                                    class="w-full min-w-[2.25rem] rounded border border-gray-300 px-1 py-0.5 text-center focus:border-blue-500 focus:outline-none"
                                                    placeholder="N/A"
                                                >
                                            @elseif ($canEditReading && $hasTime && $bLocked)
                                                <input
                                                    type="text"
                                                    value="{{ $oldB }}"
                                                    disabled
                                                    title="Diisi oleh {{ $bLockedBy }}"
                                                    data-reading="total"
                                                    data-row-id="{{ $row->id }}"
                                                    data-col-index="{{ $col['column_index'] }}"
                                                    class="w-full min-w-[2.25rem] cursor-not-allowed rounded border border-gray-200 bg-gray-100 px-1 py-0.5 text-center text-gray-400"
                                                >
                                            @elseif ($oldB !== null && $oldB !== '')
                                                <span data-reading="total" data-row-id="{{ $row->id }}" data-col-index="{{ $col['column_index'] }}">{{ $oldB }}</span>
                                            @else
                                                <span data-reading="total" data-row-id="{{ $row->id }}" data-col-index="{{ $col['column_index'] }}" class="italic text-gray-300">N/A</span>
                                            @endif
                                        </div>
                                        {{-- F --}}
                                        <div>
                                            @if ($canEditReading && $hasTime && ! $fLocked)
                                                <input
                                                    type="text"
                                                    name="{{ $readName }}[reading_fungi]"
                                                    value="{{ $oldF }}"
                                                    data-microbial
                                                    data-reading="fungi"
                                                    data-row-id="{{ $row->id }}"
                                                    data-col-index="{{ $col['column_index'] }}"
                                                    class="w-full min-w-[2.25rem] rounded border border-gray-300 px-1 py-0.5 text-center focus:border-blue-500 focus:outline-none"
                                                    placeholder="N/A"
                                                >
                                            @elseif ($canEditReading && $hasTime && $fLocked)
                                                <input
                                                    type="text"
                                                    value="{{ $oldF }}"
                                                    disabled
                                                    title="Diisi oleh {{ $fLockedBy }}"
                                                    data-reading="fungi"
                                                    data-row-id="{{ $row->id }}"
                                                    data-col-index="{{ $col['column_index'] }}"
                                                    class="w-full min-w-[2.25rem] cursor-not-allowed rounded border border-gray-200 bg-gray-100 px-1 py-0.5 text-center text-gray-400"
                                                >
                                            @elseif ($oldF !== null && $oldF !== '')
                                                <span data-reading="fungi" data-row-id="{{ $row->id }}" data-col-index="{{ $col['column_index'] }}">{{ $oldF }}</span>
                                            @else
                                                <span data-reading="fungi" data-row-id="{{ $row->id }}" data-col-index="{{ $col['column_index'] }}" class="italic text-gray-300">N/A</span>
                                            @endif
                                        </div>
                                        {{-- T (derived) --}}
                                        <div
                                            class="text-gray-500"
                                            data-total-cell
                                            data-row-id="{{ $row->id }}"
                                            data-col-index="{{ $col['column_index'] }}"
                                        >
                                            {{ $tt !== null ? $tt : 'N/A' }}
                                        </div>
                                    </div>
                                </td>
                            @endforeach

                            {{-- Alert Limit T --}}
                            <td class="w-14 min-w-[3.5rem] border-x border-gray-100 px-2 py-2 text-center whitespace-nowrap">
                                {!! $alertValue($location?->alert_limit_total, 'text-amber-600') !!}
                            </td>
                            {{-- Alert Limit F --}}
                            <td class="w-14 min-w-[3.5rem] border-x border-gray-100 px-2 py-2 text-center whitespace-nowrap">
                                {!! $alertValue($location?->alert_limit_fungi, 'text-amber-600') !!}
                            </td>
                            {{-- Alert Action T --}}
                            <td class="w-14 min-w-[3.5rem] border-x border-gray-100 px-2 py-2 text-center whitespace-nowrap">
                                {!! $alertValue($location?->alert_action_total, 'text-red-600') !!}
                            </td>
                            {{-- Alert Action F --}}
                            <td class="w-14 min-w-[3.5rem] border-x border-gray-100 px-2 py-2 text-center whitespace-nowrap">
                                {!! $alertValue($location?->alert_action_fungi, 'text-red-600') !!}
                            </td>

                            {{-- Kesimpulan --}}
                            <td
                                class="border-l border-gray-100 px-2 py-2"
                                data-row-conclusion-cell
                                data-row-id="{{ $row->id }}"
                            >
                                @if ($verdict === 'TMS')
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700">TMS</span>
                                @elseif ($verdict === 'MS')
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">MS</span>
                                @else
                                    <span class="italic text-gray-300">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="50" class="px-4 py-6 text-center text-xs italic text-gray-400">
                            Belum ada lokasi terdaftar untuk section ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-2 text-[11px] text-gray-400">
        Keterangan: B: Total Bakteri · F: Total Fungi · T: Total Bakteri + Fungi · MS: Memenuhi Spesifikasi · TMS: Tidak Memenuhi Spesifikasi
    </p>

    {{-- Catatan --}}
    <div class="mt-4">
        <label class="mb-1 block text-xs font-medium text-gray-500">Catatan</label>
        @php
            $noteLockedBy = $lockOwner('section_instances', $instance->id, 'note');
            $noteLocked   = $isLockedByOther('section_instances', $instance->id, 'note');
        @endphp
        @if ($canEditMonitoring && ! $noteLocked)
            <textarea
                name="{{ $namePrefix }}[note]"
                rows="2"
                maxlength="5000"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                placeholder="Catatan untuk section ini..."
            >{{ old("sections.{$instance->id}.note", $instance->note) }}</textarea>
        @elseif ($canEditMonitoring && $noteLocked)
            <textarea
                rows="2"
                disabled
                class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-400"
            >{{ $instance->note }}</textarea>
            <p class="mt-1 text-[11px] italic text-gray-400">Telah diisi oleh {{ $noteLockedBy }}</p>
        @else
            <div class="min-h-[2.5rem] rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                {{ $instance->note ?: 'N/A' }}
            </div>
        @endif
    </div>

    {{-- Kesimpulan akhir --}}
    <div class="mt-3 flex items-center gap-2 text-sm" data-section-conclusion-cell>
        <span class="text-gray-500">Kesimpulan:</span>
        @if ($instance->final_conclusion === 'TMS')
            <span class="rounded-full bg-red-100 px-3 py-0.5 text-xs font-semibold text-red-700">TMS</span>
        @elseif ($instance->final_conclusion === 'MS')
            <span class="rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-semibold text-emerald-700">MS</span>
        @else
            <span class="text-xs italic text-gray-400">Belum ada data</span>
        @endif
    </div>

    {{-- Tanda tangan & verifikasi --}}
    <div class="mt-5">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Tanda Tangan & Verifikasi</p>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['role' => 'monitoring', 'label' => 'Dimonitoring oleh', 'subtitle' => 'Analis Lab. Mikrobiologi'],
                ['role' => 'reading',    'label' => 'Dibaca oleh',       'subtitle' => 'Analis Lab. Mikrobiologi'],
                ['role' => 'review',     'label' => 'Direview oleh',     'subtitle' => 'Supervisor Mikrobiologi'],
                ['role' => 'approval',   'label' => 'Disetujui oleh',    'subtitle' => 'QC Manager'],
            ] as $sigSlot)
                @php $sig = $sigByRole->get($sigSlot['role']); @endphp
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-3 text-center">
                    <div class="text-[11px] font-medium text-gray-500">{{ $sigSlot['label'] }}:</div>
                    @if ($sig)
                        <div class="mt-1 text-sm font-semibold text-emerald-700">✓ {{ $sig->signer?->name ?? '-' }}</div>
                        <div class="text-[10px] text-gray-400">{{ optional($sig->signed_at)->translatedFormat('d M Y H:i') }}</div>
                    @else
                        <div class="mt-1 h-5 text-xs text-gray-300">..............</div>
                    @endif
                    <div class="mt-1 text-[10px] italic text-gray-400">({{ $sigSlot['subtitle'] }})</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

