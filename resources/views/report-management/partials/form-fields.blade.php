{{--
    Partial: report-management/partials/form-fields.blade.php
    Shared form fields for create and edit report template pages.
    Requires x-data="reportTemplateForm(...)" on the parent element.
--}}

{{-- Kode SOP + Versi SOP --}}
<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="sop_code" class="mb-1 block text-sm font-medium text-gray-700">
            Kode SOP <span class="text-red-500">*</span>
        </label>
        <input
            type="text"
            id="sop_code"
            name="sop_code"
            value="{{ old('sop_code', $reportTemplate->sop_code ?? '') }}"
            placeholder="cth: SOP-QC035-A18"
            required
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 @error('sop_code') border-red-400 @enderror"
        >
    </div>
    <div>
        <label for="sop_version" class="mb-1 block text-sm font-medium text-gray-700">
            Versi SOP <span class="text-red-500">*</span>
        </label>
        <input
            type="text"
            id="sop_version"
            name="sop_version"
            value="{{ old('sop_version', $reportTemplate->sop_version ?? '') }}"
            placeholder="cth: 11"
            required
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 @error('sop_version') border-red-400 @enderror"
        >
    </div>
</div>

{{-- Nomor Annex --}}
<div>
    <label for="annex_number" class="mb-1 block text-sm font-medium text-gray-700">
        Nomor Annex <span class="text-red-500">*</span>
    </label>
    <input
        type="number"
        id="annex_number"
        name="annex_number"
        value="{{ old('annex_number', $reportTemplate->annex_number ?? '') }}"
        placeholder="cth: 18"
        min="1"
        required
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 @error('annex_number') border-red-400 @enderror"
    >
</div>

{{-- Nama Laporan --}}
<div>
    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">
        Nama Laporan <span class="text-red-500">*</span>
    </label>
    <input
        type="text"
        id="name"
        name="name"
        value="{{ old('name', $reportTemplate->name ?? '') }}"
        placeholder="cth: Laporan Pemantauan Ruangan Produksi..."
        required
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 @error('name') border-red-400 @enderror"
    >
</div>

{{-- Medium --}}
<div>
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-semibold text-gray-800">Medium <span class="text-red-500">*</span></p>
            <p class="text-xs text-gray-500">Daftar medium yang digunakan (Medium TSP, Swab Kit, dll).</p>
        </div>
        <button
            type="button"
            @click="addMedium()"
            class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 transition-colors hover:bg-indigo-100 cursor-pointer"
        >
            + Tambah Medium
        </button>
    </div>

    <div class="mt-3 space-y-2">
        <template x-if="mediums.length === 0">
            <p class="text-xs italic text-gray-400">Belum ada medium. Klik "+ Tambah Medium" untuk menambahkan.</p>
        </template>

        <template x-for="(medium, index) in mediums" :key="index">
            <div class="flex items-center gap-2">
                <input
                    type="text"
                    :name="`medium_templates[${index}][name]`"
                    x-model="medium.name"
                    placeholder="cth: Medium TSP 60mm"
                    required
                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                >
                <button
                    type="button"
                    @click="removeMedium(index)"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-red-50 transition-colors hover:bg-red-100 cursor-pointer"
                    title="Hapus medium"
                    aria-label="Hapus medium"
                >
                    <img src="{{ asset('icons/trash.svg') }}" alt="" class="h-4 w-4" aria-hidden="true">
                </button>
            </div>
        </template>
    </div>
</div>

{{-- Inkubator --}}
<div>
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-semibold text-gray-800">Inkubator <span class="text-red-500">*</span></p>
            <p class="text-xs text-gray-500">Daftar suhu inkubasi dan durasi minimum hari.</p>
        </div>
        <button
            type="button"
            @click="addIncubator()"
            class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 transition-colors hover:bg-indigo-100 cursor-pointer"
        >
            + Tambah Inkubator
        </button>
    </div>

    <div class="mt-3 space-y-2">
        <template x-if="incubators.length === 0">
            <p class="text-xs italic text-gray-400">Belum ada inkubator. Klik "+ Tambah Inkubator" untuk menambahkan.</p>
        </template>

        <template x-for="(incubator, index) in incubators" :key="index">
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <input
                        type="text"
                        :name="`incubator_templates[${index}][label]`"
                        x-model="incubator.label"
                        @blur="formatIncubatorLabel(index)"
                        placeholder="cth: 20-25"
                        required
                        class="w-full rounded-lg border border-gray-300 py-2 pl-3 pr-10 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                    >
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-gray-400" aria-hidden="true">°C</span>
                </div>
                <input
                    type="number"
                    :name="`incubator_templates[${index}][min_day]`"
                    x-model="incubator.min_day"
                    placeholder="3"
                    min="1"
                    required
                    class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                    aria-label="Minimum hari inkubasi"
                >
                <span class="shrink-0 text-xs text-gray-500">hari min.</span>
                <button
                    type="button"
                    @click="removeIncubator(index)"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-red-50 transition-colors hover:bg-red-100 cursor-pointer"
                    title="Hapus inkubator"
                    aria-label="Hapus inkubator"
                >
                    <img src="{{ asset('icons/trash.svg') }}" alt="" class="h-4 w-4" aria-hidden="true">
                </button>
            </div>
        </template>
    </div>
</div>

{{-- Pemantauan Personel --}}
<div
    x-data="{ enabled: {{ old('has_personnel', ($reportTemplate->has_personnel ?? false) ? '1' : '0') }} ? true : false }"
    class="flex items-start justify-between gap-4 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3"
>
    <div>
        <p class="text-sm font-semibold text-gray-800">Pemantauan Personel</p>
        <p class="text-xs text-gray-500">Aktifkan jika laporan ini mencakup section pemantauan personel (Cawan Kontak &amp; Finger Dab).</p>
    </div>
    <button
        type="button"
        @click="enabled = !enabled"
        :class="enabled ? 'bg-green-600' : 'bg-gray-300'"
        class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
        role="switch"
        :aria-checked="enabled.toString()"
        aria-label="Pemantauan Personel"
    >
        <span
            :class="enabled ? 'translate-x-5' : 'translate-x-0'"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
        ></span>
    </button>
    <input type="hidden" name="has_personnel" :value="enabled ? '1' : '0'">
</div>
