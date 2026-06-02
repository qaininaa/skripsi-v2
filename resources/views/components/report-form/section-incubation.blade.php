{{--
    Section 4: Proses Inkubasi Medium Monitoring

    Props:
      $incubators — Collection<Incubator> with template + entries.incubatedBy + entries.removedBy
      $hasSwab    — bool
      $readonly   — bool
      $lockMap    — [table][row_id][field_name] => FieldLock

    Tables involved:
      incubators           → no_id, calibration_date, due_date_calibration
      incubator_entries    → date_in, time_in, date_out, time_out
--}}
@props([
    'incubators',
    'hasSwab'  => false,
    'readonly' => true,
    'lockMap'  => [],
    'allowOverrideLocks' => false,
    'monitoringTimeRequiresExistingValue' => false,
    'monitoringInOutRequiresExistingActor' => false,
])

@php
    $currentUserId = (string) (auth()->id() ?? '');

    $isLocked = function (string $table, string $rowId, string $field) use ($lockMap, $currentUserId, $allowOverrideLocks) {
        if ($allowOverrideLocks) {
            return false;
        }
        $lock = $lockMap[$table][$rowId][$field] ?? null;
        return $lock !== null && (string) $lock->filled_by !== $currentUserId;
    };
    $lockOwner = function (string $table, string $rowId, string $field) use ($lockMap) {
        $lock = $lockMap[$table][$rowId][$field] ?? null;
        return $lock?->filler?->name;
    };

    $editableClass = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100';
    $readonlyClass = 'w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-400 cursor-not-allowed';
    $lockedClass   = 'flex w-full items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700';

    $mediumTypeLabels = [
        'monitoring' => 'Tanggal Inkubasi Medium Monitoring',
        'swab'       => 'Tanggal Inkubasi Swab',
    ];

    $isPreviewId = fn ($id) => is_string($id) && str_starts_with($id, 'preview-');
@endphp

<section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
    <h2 class="mb-4 text-base font-semibold text-gray-800">4. Proses Inkubasi Medium Monitoring</h2>

    <div class="space-y-8">
        @foreach ($incubators as $incubator)
            @php
                $incubatorName = $incubator->template?->label ?? '—';
                $minDay        = $incubator->template?->min_day ?? null;
                $entriesByType = $incubator->entries->keyBy('medium_type');
                $rowOrder      = $hasSwab ? ['monitoring', 'swab'] : ['monitoring'];

                $incubatorId   = (string) ($incubator->id ?? '');
                $incubatorReal = ! $isPreviewId($incubatorId);

                $noIdLocked  = $incubatorReal && $isLocked('incubators', $incubatorId, 'no_id');
                $calibLocked = $incubatorReal && $isLocked('incubators', $incubatorId, 'calibration_date');
                $dueLocked   = $incubatorReal && $isLocked('incubators', $incubatorId, 'due_date_calibration');

                $noIdOwner   = $incubatorReal ? $lockOwner('incubators', $incubatorId, 'no_id') : null;
                $calibOwner  = $incubatorReal ? $lockOwner('incubators', $incubatorId, 'calibration_date') : null;
                $dueOwner    = $incubatorReal ? $lockOwner('incubators', $incubatorId, 'due_date_calibration') : null;
            @endphp

                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-sky-500">
                    Incubator {{ strtoupper($incubatorName) }}
                </h3>

                {{-- Incubator identity --}}
                <div class="grid gap-4 lg:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500">Nama Alat</label>
                        <input
                            type="text"
                            value="{{ $incubatorName }}"
                            readonly
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
                        >
                    </div>

                    {{-- No. ID Inkubator --}}
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500">No. ID Inkubator</label>
                        @if (! $readonly && ! $noIdLocked)
                            <input
                                type="text"
                                name="incubators[{{ $incubator->id }}][no_id]"
                                value="{{ old('incubators.' . $incubator->id . '.no_id', $incubator->no_id) }}"
                                class="{{ $editableClass }}"
                            >
                        @elseif (! $readonly && $noIdLocked)
                            <input
                                type="text"
                                value="{{ $incubator->no_id }}"
                                disabled
                                class="{{ $readonlyClass }}"
                            >
                            <p class="mt-1 text-[11px] italic text-gray-400">Telah diisi oleh {{ $noIdOwner }}</p>
                        @else
                            <input
                                type="text"
                                value="{{ $incubator->no_id }}"
                                placeholder="—"
                                disabled
                                class="{{ $readonlyClass }}"
                            >
                        @endif
                    </div>

                    {{-- Tanggal Kalibrasi --}}
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500">Tanggal Kalibrasi Inkubator</label>
                        @if (! $readonly && ! $calibLocked)
                            <input
                                type="date"
                                name="incubators[{{ $incubator->id }}][calibration_date]"
                                value="{{ old('incubators.' . $incubator->id . '.calibration_date', optional($incubator->calibration_date)->format('Y-m-d')) }}"
                                class="{{ $editableClass }}"
                            >
                        @elseif (! $readonly && $calibLocked)
                            <input
                                type="date"
                                value="{{ optional($incubator->calibration_date)->format('Y-m-d') }}"
                                disabled
                                class="{{ $readonlyClass }}"
                            >
                            <p class="mt-1 text-[11px] italic text-gray-400">Telah diisi oleh {{ $calibOwner }}</p>
                        @else
                            <input
                                type="date"
                                value="{{ optional($incubator->calibration_date)->format('Y-m-d') }}"
                                disabled
                                class="{{ $readonlyClass }}"
                            >
                        @endif
                    </div>

                    {{-- Due Date --}}
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500">Tgl Due Date Kalibrasi Inkubator</label>
                        @if (! $readonly && ! $dueLocked)
                            <input
                                type="date"
                                name="incubators[{{ $incubator->id }}][due_date_calibration]"
                                value="{{ old('incubators.' . $incubator->id . '.due_date_calibration', optional($incubator->due_date_calibration)->format('Y-m-d')) }}"
                                class="{{ $editableClass }}"
                            >
                        @elseif (! $readonly && $dueLocked)
                            <input
                                type="date"
                                value="{{ optional($incubator->due_date_calibration)->format('Y-m-d') }}"
                                disabled
                                class="{{ $readonlyClass }}"
                            >
                            <p class="mt-1 text-[11px] italic text-gray-400">Telah diisi oleh {{ $dueOwner }}</p>
                        @else
                            <input
                                type="date"
                                value="{{ optional($incubator->due_date_calibration)->format('Y-m-d') }}"
                                disabled
                                class="{{ $readonlyClass }}"
                            >
                        @endif
                    </div>
                </div>

                {{-- Incubation entries per medium type --}}
                @foreach ($rowOrder as $mediumType)
                    @php $entry = $entriesByType[$mediumType] ?? null; @endphp
                    @if ($entry === null) @continue @endif
                    @php
                        $entryId    = (string) ($entry->id ?? '');
                        $entryReal  = ! $isPreviewId($entryId);

                        $dateInLocked  = $entryReal && $isLocked('incubator_entries', $entryId, 'date_in');
                        $timeInLocked  = $entryReal && $isLocked('incubator_entries', $entryId, 'time_in');
                        $dateOutLocked = $entryReal && $isLocked('incubator_entries', $entryId, 'date_out');
                        $timeOutLocked = $entryReal && $isLocked('incubator_entries', $entryId, 'time_out');

                        $dateInOwner   = $entryReal ? $lockOwner('incubator_entries', $entryId, 'date_in')  : null;
                        $timeInOwner   = $entryReal ? $lockOwner('incubator_entries', $entryId, 'time_in')  : null;
                        $dateOutOwner  = $entryReal ? $lockOwner('incubator_entries', $entryId, 'date_out') : null;
                        $timeOutOwner  = $entryReal ? $lockOwner('incubator_entries', $entryId, 'time_out') : null;

                        // Initial Alpine state for the live "Diinkubasi/Dikeluarkan oleh" preview.
                        $initDateIn  = old("incubators.{$incubator->id}.entries.{$mediumType}.date_in",  optional($entry->date_in)->format('Y-m-d')) ?? '';
                        $initTimeIn  = old("incubators.{$incubator->id}.entries.{$mediumType}.time_in",  $entry->time_in)  ?? '';
                        $initDateOut = old("incubators.{$incubator->id}.entries.{$mediumType}.date_out", optional($entry->date_out)->format('Y-m-d')) ?? '';
                        $initTimeOut = old("incubators.{$incubator->id}.entries.{$mediumType}.time_out", $entry->time_out) ?? '';

                        // Names to fall back to when the analyst changes the inputs.
                        $existingIncubatedName = $entry->incubatedBy?->name;
                        $existingRemovedName   = $entry->removedBy?->name;
                        $currentUserName       = optional(auth()->user())->name ?? '';

                        $hasExistingIncubatedActor = ! empty($entry->incubated_by);
                        $hasExistingRemovedActor   = ! empty($entry->removed_by);

                        $canEditDateIn = ! $readonly
                            && ! $dateInLocked
                            && (! $monitoringInOutRequiresExistingActor || $hasExistingIncubatedActor);
                        $canEditDateOut = ! $readonly
                            && ! $dateOutLocked
                            && (! $monitoringInOutRequiresExistingActor || $hasExistingRemovedActor);

                        $timeInHasExistingValue = ! empty($entry->time_in);
                        $timeOutHasExistingValue = ! empty($entry->time_out);

                        $canEditTimeIn = ! $readonly
                            && ! $timeInLocked
                            && (! $monitoringInOutRequiresExistingActor || $hasExistingIncubatedActor)
                            && (! $monitoringTimeRequiresExistingValue || $timeInHasExistingValue);
                        $canEditTimeOut = ! $readonly
                            && ! $timeOutLocked
                            && (! $monitoringInOutRequiresExistingActor || $hasExistingRemovedActor)
                            && (! $monitoringTimeRequiresExistingValue || $timeOutHasExistingValue);

                        $canQuickFillIn  = $canEditDateIn || $canEditTimeIn;
                        $canQuickFillOut = $canEditDateOut || $canEditTimeOut;
                    @endphp

                    <div
                        class="mt-6"
                        x-data="{
                            dateIn:  @js($initDateIn),
                            timeIn:  @js($initTimeIn),
                            dateOut: @js($initDateOut),
                            timeOut: @js($initTimeOut),
                            existingIncubatedName: @js($existingIncubatedName),
                            existingRemovedName:   @js($existingRemovedName),
                            currentUserName:       @js($currentUserName),
                            canSetDateIn:          @js($canEditDateIn),
                            canSetTimeIn:          @js($canEditTimeIn),
                            canSetDateOut:         @js($canEditDateOut),
                            canSetTimeOut:         @js($canEditTimeOut),
                            nowDate() {
                                const now = new Date();
                                const year = now.getFullYear();
                                const month = String(now.getMonth() + 1).padStart(2, '0');
                                const day = String(now.getDate()).padStart(2, '0');
                                return `${year}-${month}-${day}`;
                            },
                            nowTime() {
                                const now = new Date();
                                const hours = String(now.getHours()).padStart(2, '0');
                                const minutes = String(now.getMinutes()).padStart(2, '0');
                                return `${hours}:${minutes}`;
                            },
                            setIncubatedNow() {
                                const date = this.nowDate();
                                const time = this.nowTime();
                                if (this.canSetDateIn) this.dateIn = date;
                                if (this.canSetTimeIn) this.timeIn = time;
                            },
                            setRemovedNow() {
                                const date = this.nowDate();
                                const time = this.nowTime();
                                if (this.canSetDateOut) this.dateOut = date;
                                if (this.canSetTimeOut) this.timeOut = time;
                            },
                            get hasIn()  { return (this.dateIn  || '').trim() !== '' || (this.timeIn  || '').trim() !== ''; },
                            get hasOut() { return (this.dateOut || '').trim() !== '' || (this.timeOut || '').trim() !== ''; },
                            get incubatedByName() {
                                if (! this.hasIn) return '';
                                return this.existingIncubatedName || this.currentUserName;
                            },
                            get removedByName() {
                                if (! this.hasOut) return '';
                                return this.existingRemovedName || this.currentUserName;
                            },
                        }"
                    >
                        <h4 class="mb-3 text-sm font-semibold text-sky-500">
                            {{ $mediumTypeLabels[$mediumType] ?? $mediumType }}
                            @if ($minDay)
                                <span class="text-xs font-normal text-gray-500">(min {{ $minDay }} hari)</span>
                            @endif
                        </h4>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-2">
                                    <label class="block text-xs font-medium text-gray-500">Diinkubasi oleh</label>
                                    @if ($canQuickFillIn)
                                        <button
                                            type="button"
                                            @click="setIncubatedNow()"
                                            class="rounded-lg border border-sky-300 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 hover:bg-sky-100"
                                        >
                                            Diinkubasi Sekarang
                                        </button>
                                    @endif
                                </div>
                                <div class="rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-500">
                                    <span x-text="incubatedByName || 'N/A'"></span>
                                </div>
                            </div>
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-2">
                                    <label class="block text-xs font-medium text-gray-500">Dikeluarkan oleh</label>
                                    @if ($canQuickFillOut)
                                        <button
                                            type="button"
                                            @click="setRemovedNow()"
                                            class="rounded-lg border border-sky-300 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 hover:bg-sky-100"
                                        >
                                            Dikeluarkan Sekarang
                                        </button>
                                    @endif
                                </div>
                                <div class="rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-500">
                                    <span x-text="removedByName || 'N/A'"></span>
                                </div>
                            </div>

                            {{-- Tanggal Masuk --}}
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-500">Tanggal Masuk Inkubator</label>
                                @if ($canEditDateIn)
                                    <input
                                        type="date"
                                        name="incubators[{{ $incubator->id }}][entries][{{ $mediumType }}][date_in]"
                                        value="{{ $initDateIn }}"
                                        x-model="dateIn"
                                        class="{{ $editableClass }}"
                                    >
                                @elseif (! $readonly && $dateInLocked)
                                    <input
                                        type="date"
                                        value="{{ optional($entry->date_in)->format('Y-m-d') }}"
                                        disabled
                                        class="{{ $readonlyClass }}"
                                    >
                                    <p class="mt-1 text-[11px] italic text-gray-400">Telah diisi oleh {{ $dateInOwner }}</p>
                                @elseif (! $readonly && $monitoringInOutRequiresExistingActor && ! $hasExistingIncubatedActor)
                                    <input
                                        type="date"
                                        value="{{ optional($entry->date_in)->format('Y-m-d') }}"
                                        disabled
                                        class="{{ $readonlyClass }}"
                                    >
                                    <p class="mt-1 text-[11px] italic text-gray-400">Belum ada data diinkubasi oleh analis, tidak bisa diedit SPV.</p>
                                @else
                                    <input
                                        type="date"
                                        value="{{ optional($entry->date_in)->format('Y-m-d') }}"
                                        disabled
                                        class="{{ $readonlyClass }}"
                                    >
                                @endif
                            </div>

                            {{-- Tanggal Keluar --}}
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-500">Tanggal Keluar Inkubator</label>
                                @if ($canEditDateOut)
                                    <input
                                        type="date"
                                        name="incubators[{{ $incubator->id }}][entries][{{ $mediumType }}][date_out]"
                                        value="{{ $initDateOut }}"
                                        x-model="dateOut"
                                        class="{{ $editableClass }}"
                                    >
                                @elseif (! $readonly && $dateOutLocked)
                                    <input
                                        type="date"
                                        value="{{ optional($entry->date_out)->format('Y-m-d') }}"
                                        disabled
                                        class="{{ $readonlyClass }}"
                                    >
                                    <p class="mt-1 text-[11px] italic text-gray-400">Telah diisi oleh {{ $dateOutOwner }}</p>
                                @elseif (! $readonly && $monitoringInOutRequiresExistingActor && ! $hasExistingRemovedActor)
                                    <input
                                        type="date"
                                        value="{{ optional($entry->date_out)->format('Y-m-d') }}"
                                        disabled
                                        class="{{ $readonlyClass }}"
                                    >
                                    <p class="mt-1 text-[11px] italic text-gray-400">Belum ada data dikeluarkan oleh analis, tidak bisa diedit SPV.</p>
                                @else
                                    <input
                                        type="date"
                                        value="{{ optional($entry->date_out)->format('Y-m-d') }}"
                                        disabled
                                        class="{{ $readonlyClass }}"
                                    >
                                @endif
                            </div>

                            {{-- Jam Masuk --}}
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-500">Jam Masuk</label>
                                @if ($canEditTimeIn)
                                    <input
                                        type="time"
                                        name="incubators[{{ $incubator->id }}][entries][{{ $mediumType }}][time_in]"
                                        value="{{ $initTimeIn }}"
                                        x-model="timeIn"
                                        class="{{ $editableClass }}"
                                    >
                                @elseif (! $readonly && $timeInLocked)
                                    <input
                                        type="time"
                                        value="{{ $entry->time_in }}"
                                        disabled
                                        class="{{ $readonlyClass }}"
                                    >
                                    <p class="mt-1 text-[11px] italic text-gray-400">Telah diisi oleh {{ $timeInOwner }}</p>
                                @elseif (! $readonly && $monitoringInOutRequiresExistingActor && ! $hasExistingIncubatedActor)
                                    <input
                                        type="time"
                                        value="{{ $entry->time_in }}"
                                        disabled
                                        class="{{ $readonlyClass }}"
                                    >
                                    <p class="mt-1 text-[11px] italic text-gray-400">Belum ada data diinkubasi oleh analis, tidak bisa diedit SPV.</p>
                                @elseif (! $readonly && $monitoringTimeRequiresExistingValue && ! $timeInHasExistingValue)
                                    <input
                                        type="time"
                                        value="{{ $entry->time_in }}"
                                        disabled
                                        class="{{ $readonlyClass }}"
                                    >
                                    <p class="mt-1 text-[11px] italic text-gray-400">Jam belum diisi analis, tidak bisa diedit SPV.</p>
                                @else
                                    <input
                                        type="time"
                                        value="{{ $entry->time_in }}"
                                        disabled
                                        class="{{ $readonlyClass }}"
                                    >
                                @endif
                            </div>

                            {{-- Jam Keluar --}}
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-500">Jam Keluar</label>
                                @if ($canEditTimeOut)
                                    <input
                                        type="time"
                                        name="incubators[{{ $incubator->id }}][entries][{{ $mediumType }}][time_out]"
                                        value="{{ $initTimeOut }}"
                                        x-model="timeOut"
                                        class="{{ $editableClass }}"
                                    >
                                @elseif (! $readonly && $timeOutLocked)
                                    <input
                                        type="time"
                                        value="{{ $entry->time_out }}"
                                        disabled
                                        class="{{ $readonlyClass }}"
                                    >
                                    <p class="mt-1 text-[11px] italic text-gray-400">Telah diisi oleh {{ $timeOutOwner }}</p>
                                @elseif (! $readonly && $monitoringInOutRequiresExistingActor && ! $hasExistingRemovedActor)
                                    <input
                                        type="time"
                                        value="{{ $entry->time_out }}"
                                        disabled
                                        class="{{ $readonlyClass }}"
                                    >
                                    <p class="mt-1 text-[11px] italic text-gray-400">Belum ada data dikeluarkan oleh analis, tidak bisa diedit SPV.</p>
                                @elseif (! $readonly && $monitoringTimeRequiresExistingValue && ! $timeOutHasExistingValue)
                                    <input
                                        type="time"
                                        value="{{ $entry->time_out }}"
                                        disabled
                                        class="{{ $readonlyClass }}"
                                    >
                                    <p class="mt-1 text-[11px] italic text-gray-400">Jam belum diisi analis, tidak bisa diedit SPV.</p>
                                @else
                                    <input
                                        type="time"
                                        value="{{ $entry->time_out }}"
                                        disabled
                                        class="{{ $readonlyClass }}"
                                    >
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
        @endforeach
    </div>
</section>
