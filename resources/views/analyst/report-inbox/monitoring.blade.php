@extends('layouts.app')

@section('title', 'Pemantauan Ruangan')
@section('page-title', 'Pemantauan Ruangan')

@section('content')
    @php
        $template = $report->reportTemplate;
        // Sort medium entries: regular first, swab last.
        $mediumEntries = $report->mediumEntries->sortBy(fn ($m) => $m->is_swab ? 1 : 0)->values();
        // Sort instruments: Air Sampler first, Swab Kit last.
        $instrumentEntries = $report->instrumentEntries->sortBy(function ($i) {
            return $i->tool_name === 'Swab Kit' ? 1 : 0;
        })->values();
        $hasSwab = $template?->hasSwab() ?? false;

        $monitoringAnalyst = $report->analystOfType('monitoring')?->user;
        $readingAnalyst    = $report->analystOfType('reading')?->user;

        $mediumTypeLabels = [
            'monitoring' => 'Tanggal Inkubasi Medium Monitoring',
            'swab'       => 'Tanggal Inkubasi Swab',
        ];
    @endphp

    {{-- Header bar --}}
    <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            <a
                href="{{ route('analyst.reports.index') }}"
                class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700"
            >
                <span>&larr;</span>
                <span>Kembali</span>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    @if ($template)
                        <span class="text-base font-bold text-gray-700">{{ $template->annex_number }}</span>
                        <span class="text-gray-400">—</span>
                        <span class="text-base font-semibold text-gray-800">{{ $template->name }}</span>
                    @endif
                </div>
                <div class="mt-1 text-xs text-gray-500">
                    {{ $report->product_name }} · Batch {{ $report->batch_number }} · {{ $report->created_at->translatedFormat('d M Y') }}
                </div>
            </div>
        </div>

        @unless ($readonly)
            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    form="monitoring-form"
                    name="action"
                    value="draft"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    <span>💾</span>
                    <span>Simpan Draft</span>
                </button>
                <button
                    type="submit"
                    form="monitoring-form"
                    name="action"
                    value="finalize"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-600"
                >
                    <span>💾</span>
                    <span>Simpan & Selesaikan</span>
                </button>
            </div>
        @endunless
    </div>

    <x-messages.success-message />
    <x-messages.error-message />
    <x-messages.validation-errors />

    <form
        id="monitoring-form"
        action="{{ route('analyst.reports.monitoring.save', $report) }}"
        method="POST"
        class="space-y-6 {{ $readonly ? 'pointer-events-none opacity-90' : '' }}"
    >
        @csrf
        @method('PUT')

        {{-- 1. Pemantauan Ruang --}}
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h2 class="mb-4 text-base font-bold text-gray-800">1. Pemantauan Ruang</h2>

            <div class="grid gap-4 lg:grid-cols-5">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Tanggal Pemantauan Ruang</label>
                    <input
                        type="text"
                        value="{{ $report->monitoring_started_at?->translatedFormat('d M Y') ?? $report->created_at->translatedFormat('d M Y') }}"
                        readonly
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
                    >
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Dimonitoring Oleh</label>
                    <div class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700">
                        @if ($monitoringAnalyst)
                            ✓ {{ $monitoringAnalyst->name }}
                            @if ($monitoringAnalyst->id === auth()->id())
                                <span class="text-blue-400">(saya)</span>
                            @endif
                        @else
                            <span class="italic text-gray-400">Belum ada analis</span>
                        @endif
                    </div>
                </div>
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
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Nama Produk</label>
                    <input
                        type="text"
                        value="{{ $report->product_name }}"
                        readonly
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
                    >
                </div>
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

        {{-- 2. Identitas Instrumen --}}
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
                                placeholder="Contoh: AS-001"
                                @disabled($readonly)
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">Tanggal Kalibrasi {{ $instrument->tool_name }}</label>
                            <input
                                type="date"
                                name="instruments[{{ $instrument->tool_name }}][calibration_date]"
                                value="{{ old('instruments.' . $instrument->tool_name . '.calibration_date', optional($instrument->calibration_date)->format('Y-m-d')) }}"
                                @disabled($readonly)
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">Tgl Due Date Kalibrasi {{ $instrument->tool_name }}</label>
                            <input
                                type="date"
                                name="instruments[{{ $instrument->tool_name }}][due_date]"
                                value="{{ old('instruments.' . $instrument->tool_name . '.due_date', optional($instrument->due_date)->format('Y-m-d')) }}"
                                @disabled($readonly)
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            >
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- 3. Identitas Medium --}}
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h2 class="mb-4 text-base font-bold text-gray-800">3. Identitas Medium</h2>

            <div class="grid gap-6 lg:grid-cols-3">
                @foreach ($mediumEntries as $medium)
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-blue-500">
                            {{ strtoupper($medium->name ?? '—') }}
                        </h3>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">Nomor Batch Medium</label>
                            <input
                                type="text"
                                name="mediums[{{ $medium->id }}][batch_number]"
                                value="{{ old('mediums.' . $medium->id . '.batch_number', $medium->batch_number) }}"
                                @disabled($readonly)
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            >
                        </div>

                        @unless ($medium->is_swab)
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-500">Nomor GPT Medium</label>
                                <input
                                    type="text"
                                    name="mediums[{{ $medium->id }}][gpt_number]"
                                    value="{{ old('mediums.' . $medium->id . '.gpt_number', $medium->gpt_number) }}"
                                    @disabled($readonly)
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                >
                            </div>
                        @endunless

                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">
                                {{ $medium->is_swab ? 'Tanggal ED Swab Kit' : 'Tanggal ED Medium' }}
                            </label>
                            <input
                                type="date"
                                name="mediums[{{ $medium->id }}][expiration_date]"
                                value="{{ old('mediums.' . $medium->id . '.expiration_date', optional($medium->expiration_date)->format('Y-m-d')) }}"
                                @disabled($readonly)
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            >
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- 4. Proses Inkubasi Medium Monitoring --}}
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h2 class="mb-4 text-base font-bold text-gray-800">4. Proses Inkubasi Medium Monitoring</h2>

            <div class="space-y-8">
                @forelse ($report->incubators as $incubator)
                    @php
                        $incubatorTemplate = $incubator->template;
                        $incubatorName     = $incubatorTemplate?->label ?? '—';
                        $minDay            = $incubatorTemplate?->min_day ?? null;

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
                                    class="w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-700"
                                >
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-500">No. ID Inkubator</label>
                                <input
                                    type="text"
                                    name="incubators[{{ $incubator->id }}][no_id]"
                                    value="{{ old('incubators.' . $incubator->id . '.no_id', $incubator->no_id) }}"
                                    @disabled($readonly)
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                >
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-500">Tanggal Kalibrasi Inkubator</label>
                                <input
                                    type="date"
                                    name="incubators[{{ $incubator->id }}][calibration_date]"
                                    value="{{ old('incubators.' . $incubator->id . '.calibration_date', optional($incubator->calibration_date)->format('Y-m-d')) }}"
                                    @disabled($readonly)
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                >
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-500">Tgl Due Date Kalibrasi Inkubator</label>
                                <input
                                    type="date"
                                    name="incubators[{{ $incubator->id }}][due_date_calibration]"
                                    value="{{ old('incubators.' . $incubator->id . '.due_date_calibration', optional($incubator->due_date_calibration)->format('Y-m-d')) }}"
                                    @disabled($readonly)
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                >
                            </div>
                        </div>

                        {{-- Incubation entries: monitoring, swab? --}}
                        @foreach ($rowOrder as $mediumType)
                            @php $entry = $entriesByType[$mediumType] ?? null; @endphp
                            @if ($entry === null) @continue @endif

                            <div class="mt-6">
                                <h4 class="mb-3 text-sm font-semibold text-blue-500">
                                    {{ $mediumTypeLabels[$mediumType] }}
                                    @if ($minDay) <span class="text-xs font-normal text-gray-500">(min {{ $minDay }} hari)</span> @endif
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
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                        >
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Tanggal Keluar Inkubator</label>
                                        <input
                                            type="date"
                                            name="incubators[{{ $incubator->id }}][entries][{{ $mediumType }}][date_out]"
                                            value="{{ old('incubators.' . $incubator->id . '.entries.' . $mediumType . '.date_out', optional($entry->date_out)->format('Y-m-d')) }}"
                                            @disabled($readonly)
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                        >
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Jam Masuk</label>
                                        <input
                                            type="time"
                                            name="incubators[{{ $incubator->id }}][entries][{{ $mediumType }}][time_in]"
                                            value="{{ old('incubators.' . $incubator->id . '.entries.' . $mediumType . '.time_in', $entry->time_in) }}"
                                            @disabled($readonly)
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                        >
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Jam Keluar</label>
                                        <input
                                            type="time"
                                            name="incubators[{{ $incubator->id }}][entries][{{ $mediumType }}][time_out]"
                                            value="{{ old('incubators.' . $incubator->id . '.entries.' . $mediumType . '.time_out', $entry->time_out) }}"
                                            @disabled($readonly)
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                        >
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                        Template laporan ini belum memiliki konfigurasi inkubator.
                    </div>
                @endforelse
            </div>
        </section>
    </form>
@endsection
