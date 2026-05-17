{{--
    Section 1: Pemantauan Ruang
    Props:
      $report   — Report model (eager-loaded with analysts.user)
      $readonly — bool
--}}
@props(['report', 'readonly' => true])

@php
    $monitoringAnalyst = $report->analystOfType('monitoring')?->user;
    $readingAnalyst    = $report->analystOfType('reading')?->user;
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
            @if ($monitoringAnalyst)
                <div class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700">
                    ✓ {{ $monitoringAnalyst->name }}
                    @if ($monitoringAnalyst->id === auth()->id())
                        <span class="text-blue-400">(saya)</span>
                    @endif
                </div>
            @else
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm italic text-gray-400">
                    Belum ada analis
                </div>
            @endif
        </div>

        {{-- Dibaca Oleh --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-500">Dibaca Oleh</label>
            @if ($readingAnalyst)
                <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                    ✓ {{ $readingAnalyst->name }}
                </div>
            @else
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm italic text-gray-400">
                    Diisi setelah monitoring selesai
                </div>
            @endif
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
