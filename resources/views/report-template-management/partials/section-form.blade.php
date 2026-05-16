{{--
    Partial: section-form.blade.php
    Inline form for creating or editing a section.

    Required variables:
      $action  — form action URL
      $method  — 'POST' or 'PUT'
      $section — Section model instance (null for create)

    Optional Alpine state:
      $cancelExpression — Alpine expression to evaluate on Cancel click
                          (e.g. "open = false" or "showEdit = false").
                          Defaults to no-op.
--}}
@php
    $cancelExpression = $cancelExpression ?? null;
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    <div class="grid grid-cols-3 gap-3">
        {{-- Satuan Ukur --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700">
                Satuan Ukur <span class="text-red-500">*</span>
            </label>
            <input
                type="text"
                name="measurement_unit"
                value="{{ old('measurement_unit', $section->measurement_unit ?? '') }}"
                placeholder="cth: CFU/4hours/plate"
                required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
            >
        </div>

        {{-- Tipe Pengukuran --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700">
                Tipe Pengukuran <span class="text-red-500">*</span>
            </label>
            <select
                name="measurement_type"
                required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
            >
                <option value="">Pilih</option>
                @foreach (['settle_plate' => 'Settle Plate', 'air_sampler' => 'Air Sampler', 'contact_plate' => 'Contact Plate', 'swab' => 'Swab'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('measurement_type', $section->measurement_type ?? '') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Jumlah Kolom --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700">
                Jumlah Kolom <span class="text-red-500">*</span>
            </label>
            <input
                type="number"
                name="max_column"
                value="{{ old('max_column', $section->max_column ?? 1) }}"
                min="1"
                max="20"
                required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
            >
        </div>

        {{-- Label Kolom --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700">Label Kolom</label>
            <input
                type="text"
                name="column_label"
                value="{{ old('column_label', $section->column_label ?? '') }}"
                placeholder="cth: Exposure"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
            >
        </div>

        {{-- Input Waktu per Kolom --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700">
                Input Waktu per Kolom <span class="text-red-500">*</span>
            </label>
            <select
                name="time_slot_type"
                required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
            >
                <option value="">Pilih</option>
                @foreach ([
                    'by_location'     => 'Per Lokasi',
                    'start_end'       => '1 slot (Mulai & Selesai)',
                    'start_end_ab'    => 'A/B (Mulai A, Mulai B)',
                    'start_end_multi' => 'S1/S1-2/S1-3 (Multi Swab)',
                ] as $value => $label)
                    <option value="{{ $value }}" @selected(old('time_slot_type', $section->time_slot_type ?? '') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Machine Set-up --}}
    <div class="mt-3">
        <label class="inline-flex cursor-pointer items-center gap-2">
            <input
                type="checkbox"
                name="has_machine_setup"
                value="1"
                @checked(old('has_machine_setup', ($section->has_machine_setup ?? false) ? '1' : '0') === '1')
                class="rounded border-gray-300 text-green-600"
            >
            <span class="text-sm text-gray-700">Machine Set-up (waktu bersama)</span>
        </label>
    </div>

    {{-- Actions --}}
    <div class="mt-4 flex justify-end gap-2">
        <button
            type="button"
            @if ($cancelExpression) @click="{{ $cancelExpression }}" @endif
            class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 cursor-pointer"
        >
            Batal
        </button>
        <button
            type="submit"
            class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-green-800 cursor-pointer"
        >
            {{ $method === 'PUT' ? 'Simpan Perubahan' : 'Simpan Section' }}
        </button>
    </div>
</form>
