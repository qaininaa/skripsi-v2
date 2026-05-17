{{--
    Section 4: Proses Inkubasi Medium Monitoring
    Props:
      $incubators — Collection<Incubator> eager-loaded with template + entries.incubatedBy + entries.removedBy
      $hasSwab    — bool
      $readonly   — bool
--}}
@props(['incubators', 'hasSwab' => false, 'readonly' => true])

@php
    $inputClass = $readonly
        ? 'w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-400 cursor-not-allowed'
        : 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100';

    $mediumTypeLabels = [
        'monitoring' => 'Tanggal Inkubasi Medium Monitoring',
        'swab'       => 'Tanggal Inkubasi Swab',
    ];
@endphp

<section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
    <h2 class="mb-4 text-base font-bold text-gray-800">4. Proses Inkubasi Medium Monitoring</h2>

    <div class="space-y-8">
        @foreach ($incubators as $incubator)
                @php
                    $incubatorName = $incubator->template?->label ?? '—';
                    $minDay        = $incubator->template?->min_day ?? null;
                    $entriesByType = $incubator->entries->keyBy('medium_type');
                    $rowOrder      = $hasSwab ? ['monitoring', 'swab'] : ['monitoring'];
                @endphp

                <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-5">
                    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-blue-500">
                        {{ strtoupper($incubatorName) }}
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
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">No. ID Inkubator</label>
                            <input
                                type="text"
                                name="incubators[{{ $incubator->id }}][no_id]"
                                value="{{ old('incubators.' . $incubator->id . '.no_id', $incubator->no_id) }}"
                                placeholder="{{ $readonly ? '—' : '' }}"
                                @disabled($readonly)
                                class="{{ $inputClass }}"
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">Tanggal Kalibrasi Inkubator</label>
                            <input
                                type="date"
                                name="incubators[{{ $incubator->id }}][calibration_date]"
                                value="{{ old('incubators.' . $incubator->id . '.calibration_date', optional($incubator->calibration_date)->format('Y-m-d')) }}"
                                @disabled($readonly)
                                class="{{ $inputClass }}"
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">Tgl Due Date Kalibrasi Inkubator</label>
                            <input
                                type="date"
                                name="incubators[{{ $incubator->id }}][due_date_calibration]"
                                value="{{ old('incubators.' . $incubator->id . '.due_date_calibration', optional($incubator->due_date_calibration)->format('Y-m-d')) }}"
                                @disabled($readonly)
                                class="{{ $inputClass }}"
                            >
                        </div>
                    </div>

                    {{-- Incubation entries per medium type --}}
                    @foreach ($rowOrder as $mediumType)
                        @php $entry = $entriesByType[$mediumType] ?? null; @endphp
                        @if ($entry === null) @continue @endif

                        <div class="mt-6">
                            <h4 class="mb-3 text-sm font-semibold text-blue-500">
                                {{ $mediumTypeLabels[$mediumType] ?? $mediumType }}
                                @if ($minDay)
                                    <span class="text-xs font-normal text-gray-500">(min {{ $minDay }} hari)</span>
                                @endif
                            </h4>

                            <div class="grid gap-4 lg:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Diinkubasi oleh</label>
                                    <div class="rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-500">
                                        {{ $entry->incubatedBy?->name ?? 'N/A' }}
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Dikeluarkan oleh</label>
                                    <div class="rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-500">
                                        {{ $entry->removedBy?->name ?? 'N/A' }}
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Tanggal Masuk Inkubator</label>
                                    <input
                                        type="date"
                                        name="incubators[{{ $incubator->id }}][entries][{{ $mediumType }}][date_in]"
                                        value="{{ old('incubators.' . $incubator->id . '.entries.' . $mediumType . '.date_in', optional($entry->date_in)->format('Y-m-d')) }}"
                                        @disabled($readonly)
                                        class="{{ $inputClass }}"
                                    >
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Tanggal Keluar Inkubator</label>
                                    <input
                                        type="date"
                                        name="incubators[{{ $incubator->id }}][entries][{{ $mediumType }}][date_out]"
                                        value="{{ old('incubators.' . $incubator->id . '.entries.' . $mediumType . '.date_out', optional($entry->date_out)->format('Y-m-d')) }}"
                                        @disabled($readonly)
                                        class="{{ $inputClass }}"
                                    >
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Jam Masuk</label>
                                    <input
                                        type="time"
                                        name="incubators[{{ $incubator->id }}][entries][{{ $mediumType }}][time_in]"
                                        value="{{ old('incubators.' . $incubator->id . '.entries.' . $mediumType . '.time_in', $entry->time_in) }}"
                                        @disabled($readonly)
                                        class="{{ $inputClass }}"
                                    >
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Jam Keluar</label>
                                    <input
                                        type="time"
                                        name="incubators[{{ $incubator->id }}][entries][{{ $mediumType }}][time_out]"
                                        value="{{ old('incubators.' . $incubator->id . '.entries.' . $mediumType . '.time_out', $entry->time_out) }}"
                                        @disabled($readonly)
                                        class="{{ $inputClass }}"
                                    >
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
</section>
