{{--
    Section 2: Identitas Instrumen

    Props:
      $instrumentEntries — Collection<InstrumentEntry>
      $readonly          — bool, true when the analyst can't edit at all
      $lockMap           — pre-computed [table][row_id][field_name] => FieldLock
                            (see SectionInstanceRepository::getInstancesForReportWithLocks)

    Field locking: each editable input checks the polymorphic field_locks table
    via $lockMap. If a lock exists owned by another user, the input is replaced
    with a 🔒 read-only badge tagged with the locker's name.
--}}
@props([
    'instrumentEntries',
    'readonly' => true,
    'lockMap'  => [],
    'allowOverrideLocks' => false,
])

@php
    $currentUserId = (string) (auth()->id() ?? '');

    $isLockedByOther = function (string $rowId, string $field) use ($lockMap, $currentUserId, $allowOverrideLocks) {
        if ($allowOverrideLocks) {
            return false;
        }
        $lock = $lockMap['instrument_entries'][$rowId][$field] ?? null;
        return $lock !== null && (string) $lock->filled_by !== $currentUserId;
    };
    $lockOwner = function (string $rowId, string $field) use ($lockMap) {
        $lock = $lockMap['instrument_entries'][$rowId][$field] ?? null;
        return $lock?->filler?->name;
    };

    $editableClass = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100';
    $readonlyClass = 'w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-400 cursor-not-allowed';
    $lockedClass   = 'flex w-full items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700';
@endphp

<section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
    <h2 class="mb-4 text-base font-bold text-gray-800">2. Identitas Instrumen</h2>

    <div class="space-y-6">
        @foreach ($instrumentEntries as $instrument)
            @php
                $rowId    = (string) ($instrument->id ?? '');
                $hasRow   = $rowId !== '';
                // Field-level lock state — only meaningful for persisted rows.
                $noIdLocked         = $hasRow && $isLockedByOther($rowId, 'no_id');
                $calibLocked        = $hasRow && $isLockedByOther($rowId, 'calibration_date');
                $dueLocked          = $hasRow && $isLockedByOther($rowId, 'due_date');
                $noIdLockedBy       = $hasRow ? $lockOwner($rowId, 'no_id') : null;
                $calibLockedBy      = $hasRow ? $lockOwner($rowId, 'calibration_date') : null;
                $dueLockedBy        = $hasRow ? $lockOwner($rowId, 'due_date') : null;
            @endphp

            <div class="grid gap-4 lg:grid-cols-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Nama Alat</label>
                    <input
                        type="text"
                        value="{{ $instrument->tool_name }}"
                        readonly
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
                    >
                </div>

                {{-- No. ID --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">No. ID {{ $instrument->tool_name }}</label>
                    @if (! $readonly && ! $noIdLocked)
                        <input
                            type="text"
                            name="instruments[{{ $instrument->tool_name }}][no_id]"
                            value="{{ old('instruments.' . $instrument->tool_name . '.no_id', $instrument->no_id) }}"
                            placeholder="Contoh: AS-001"
                            class="{{ $editableClass }}"
                        >
                    @elseif (! $readonly && $noIdLocked)
                        <input
                            type="text"
                            value="{{ $instrument->no_id }}"
                            disabled
                            class="{{ $readonlyClass }}"
                        >
                        <p class="mt-1 text-[11px] italic text-gray-400">Telah diisi oleh {{ $noIdLockedBy }}</p>
                    @else
                        <input
                            type="text"
                            value="{{ $instrument->no_id }}"
                            placeholder="—"
                            disabled
                            class="{{ $readonlyClass }}"
                        >
                    @endif
                </div>

                {{-- Tanggal Kalibrasi --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Tanggal Kalibrasi</label>
                    @if (! $readonly && ! $calibLocked)
                        <input
                            type="date"
                            name="instruments[{{ $instrument->tool_name }}][calibration_date]"
                            value="{{ old('instruments.' . $instrument->tool_name . '.calibration_date', optional($instrument->calibration_date)->format('Y-m-d')) }}"
                            class="{{ $editableClass }}"
                        >
                    @elseif (! $readonly && $calibLocked)
                        <input
                            type="date"
                            value="{{ optional($instrument->calibration_date)->format('Y-m-d') }}"
                            disabled
                            class="{{ $readonlyClass }}"
                        >
                        <p class="mt-1 text-[11px] italic text-gray-400">Telah diisi oleh {{ $calibLockedBy }}</p>
                    @else
                        <input
                            type="date"
                            value="{{ optional($instrument->calibration_date)->format('Y-m-d') }}"
                            disabled
                            class="{{ $readonlyClass }}"
                        >
                    @endif
                </div>

                {{-- Due Date --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Tgl Due Date Kalibrasi</label>
                    @if (! $readonly && ! $dueLocked)
                        <input
                            type="date"
                            name="instruments[{{ $instrument->tool_name }}][due_date]"
                            value="{{ old('instruments.' . $instrument->tool_name . '.due_date', optional($instrument->due_date)->format('Y-m-d')) }}"
                            class="{{ $editableClass }}"
                        >
                    @elseif (! $readonly && $dueLocked)
                        <input
                            type="date"
                            value="{{ optional($instrument->due_date)->format('Y-m-d') }}"
                            disabled
                            class="{{ $readonlyClass }}"
                        >
                        <p class="mt-1 text-[11px] italic text-gray-400">Telah diisi oleh {{ $dueLockedBy }}</p>
                    @else
                        <input
                            type="date"
                            value="{{ optional($instrument->due_date)->format('Y-m-d') }}"
                            disabled
                            class="{{ $readonlyClass }}"
                        >
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
