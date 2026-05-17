{{--
    Section 2: Identitas Instrumen
    Props:
      $instrumentEntries — Collection<InstrumentEntry>
      $readonly          — bool
--}}
@props(['instrumentEntries', 'readonly' => true])

@php
    $inputClass = $readonly
        ? 'w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-400 cursor-not-allowed'
        : 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100';
@endphp

<section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
    <h2 class="mb-4 text-base font-bold text-gray-800">2. Identitas Instrumen</h2>

    <div class="space-y-6">
        @foreach ($instrumentEntries as $instrument)
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
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">No. ID {{ $instrument->tool_name }}</label>
                    <input
                        type="text"
                        name="instruments[{{ $instrument->tool_name }}][no_id]"
                        value="{{ old('instruments.' . $instrument->tool_name . '.no_id', $instrument->no_id) }}"
                        placeholder="{{ $readonly ? '—' : 'Contoh: AS-001' }}"
                        @disabled($readonly)
                        class="{{ $inputClass }}"
                    >
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Tanggal Kalibrasi</label>
                    <input
                        type="date"
                        name="instruments[{{ $instrument->tool_name }}][calibration_date]"
                        value="{{ old('instruments.' . $instrument->tool_name . '.calibration_date', optional($instrument->calibration_date)->format('Y-m-d')) }}"
                        @disabled($readonly)
                        class="{{ $inputClass }}"
                    >
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Tgl Due Date Kalibrasi</label>
                    <input
                        type="date"
                        name="instruments[{{ $instrument->tool_name }}][due_date]"
                        value="{{ old('instruments.' . $instrument->tool_name . '.due_date', optional($instrument->due_date)->format('Y-m-d')) }}"
                        @disabled($readonly)
                        class="{{ $inputClass }}"
                    >
                </div>
            </div>
        @endforeach
    </div>
</section>
