{{--
    Section 1: Pemantauan Ruang
    Props:
      $report   - Report model (eager-loaded with analysts.user)
      $readonly - bool

    "Dimonitoring Oleh" / "Dibaca Oleh" shows analyst names in
    a simple read-only field to keep visual style consistent.
--}}
@props(['report', 'readonly' => true, 'previewOnly' => false])

@php
    $monitoringAnalysts = $report->analysts
        ->where('type', 'monitoring')
        ->map(fn ($a) => $a->user)
        ->filter()
        ->unique('id')
        ->values();

    $monitoringJoinedNames = $monitoringAnalysts
        ->map(fn ($analyst) => $analyst->name)
        ->implode(', ');

    $readingAnalysts = $report->analysts
        ->where('type', 'reading')
        ->map(fn ($a) => $a->user)
        ->filter()
        ->unique('id')
        ->values();

    $readingJoinedNames = $readingAnalysts
        ->map(fn ($analyst) => $analyst->name)
        ->implode(', ');

    $monitoringDisplay = $monitoringJoinedNames !== ''
        ? $monitoringJoinedNames
        : 'Belum ada analis';

    $readingDisplay = $readingJoinedNames !== ''
        ? $readingJoinedNames
        : ($previewOnly ? 'Belum ada analis pembacaan' : 'Diisi setelah monitoring selesai');
@endphp

<section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
    <h2 class="mb-4 text-base font-bold text-gray-800">1. Pemantauan Ruang</h2>

    <div class="grid gap-4 lg:grid-cols-5">
        {{-- Tanggal Pemantauan Ruang --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-500">Tanggal Pemantauan Ruang</label>
            <input
                type="text"
                value="{{ $report->monitoring_started_at?->translatedFormat('d M Y') ?? $report->created_at->translatedFormat('d M Y') }}"
                readonly
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
            >
        </div>

        {{-- Dimonitoring Oleh --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-500">Dimonitoring Oleh</label>
            <input
                type="text"
                value="{{ $monitoringDisplay }}"
                readonly
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
            >
        </div>

        {{-- Dibaca Oleh --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-500">Dibaca Oleh</label>
            <input
                type="text"
                value="{{ $readingDisplay }}"
                readonly
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
            >
        </div>

        {{-- Nama Produk --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-500">Nama Produk</label>
            <input
                type="text"
                value="{{ $report->product_name }}"
                readonly
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
            >
        </div>

        {{-- Nomor Batch Produk --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-500">Nomor Batch Produk</label>
            <input
                type="text"
                value="{{ $report->batch_number }}"
                readonly
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
            >
        </div>
    </div>
</section>
