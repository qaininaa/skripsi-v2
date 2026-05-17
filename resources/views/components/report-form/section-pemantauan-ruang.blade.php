{{--
    Section 1: Pemantauan Ruang
    Props:
      $report   — Report model (eager-loaded with analysts.user)
      $readonly — bool

    "Dimonitoring Oleh" / "Dibaca Oleh" lists every analyst on the
    `analysts` pivot for that role (multiple analysts may collaborate),
    each on its own line with a "(saya)" marker for the current user.
--}}
@props(['report', 'readonly' => true])

@php
    $monitoringAnalysts = $report->analysts
        ->where('type', 'monitoring')
        ->map(fn ($a) => $a->user)
        ->filter()
        ->unique('id')
        ->values();

    $readingAnalysts = $report->analysts
        ->where('type', 'reading')
        ->map(fn ($a) => $a->user)
        ->filter()
        ->unique('id')
        ->values();
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
            @if ($monitoringAnalysts->isNotEmpty())
                <div class="space-y-1.5">
                    @foreach ($monitoringAnalysts as $analyst)
                        @php $isMe = $analyst->id === auth()->id(); @endphp
                        <div @class([
                            'rounded-lg border px-3 py-2 text-sm font-medium',
                            'border-blue-100 bg-blue-50 text-blue-700'         => $isMe,
                            'border-emerald-100 bg-emerald-50 text-emerald-700' => ! $isMe,
                        ])>
                            ✓ {{ $analyst->name }}
                            @if ($isMe)
                                <span class="text-blue-400">(saya)</span>
                            @endif
                        </div>
                    @endforeach
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
            @if ($readingAnalysts->isNotEmpty())
                <div class="space-y-1.5">
                    @foreach ($readingAnalysts as $analyst)
                        @php $isMe = $analyst->id === auth()->id(); @endphp
                        <div @class([
                            'rounded-lg border px-3 py-2 text-sm font-medium',
                            'border-blue-100 bg-blue-50 text-blue-700'         => $isMe,
                            'border-emerald-100 bg-emerald-50 text-emerald-700' => ! $isMe,
                        ])>
                            ✓ {{ $analyst->name }}
                            @if ($isMe)
                                <span class="text-blue-400">(saya)</span>
                            @endif
                        </div>
                    @endforeach
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
