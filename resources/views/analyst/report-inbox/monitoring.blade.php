@extends('layouts.app')

@section('title', 'Pemantauan Ruangan')
@section('page-title', 'Pemantauan Ruangan')

@section('content')
    @php
        use Domain\Report\Models\Incubator;
        use Domain\Report\Models\IncubatorEntry;
        use Domain\Report\Models\InstrumentEntry;
        use Domain\Report\Models\MediumEntry;

        $template = $report->reportTemplate;
        $hasSwab  = $template?->hasSwab() ?? false;

        // Section 2: Identitas Instrumen
        // Air Sampler is the single instrument tracked under this section.
        // If no real entries exist yet, render a preview row from the constant.
        $instrumentEntries = $report->instrumentEntries
            ->sortBy(fn ($i) => $i->tool_name === 'Swab Kit' ? 1 : 0)
            ->values();

        if ($instrumentEntries->isEmpty()) {
            $instrumentEntries = collect([
                new InstrumentEntry(['tool_name' => 'Air Sampler']),
            ]);
        }

        // Section 3: Identitas Medium
        // Fall back to MediumTemplate rows so the form structure is visible.
        $mediumEntries = $report->mediumEntries
            ->sortBy(fn ($m) => $m->is_swab ? 1 : 0)
            ->values();

        if ($mediumEntries->isEmpty() && $template) {
            $mediumEntries = $template->mediumTemplates->map(function ($mt) {
                $entry          = new MediumEntry();
                $entry->id      = 'preview-' . $mt->id;
                $entry->name    = $mt->name;
                $entry->is_swab = str_contains(strtolower($mt->name), 'swab');
                return $entry;
            })->sortBy(fn ($m) => $m->is_swab ? 1 : 0)->values();
        }

        // Section 4: Inkubasi
        // Build preview Incubators (with empty IncubatorEntries) from IncubatorTemplate.
        $incubators = $report->incubators;

        if ($incubators->isEmpty() && $template) {
            $incubators = $template->incubatorTemplates->map(function ($it) use ($hasSwab) {
                $incubator             = new Incubator();
                $incubator->id         = 'preview-' . $it->id;
                $incubator->setRelation('template', $it);

                $entries = collect();
                $entries->push(tap(new IncubatorEntry(), function ($e) use ($it) {
                    $e->id          = 'preview-mon-' . $it->id;
                    $e->medium_type = 'monitoring';
                }));
                if ($hasSwab) {
                    $entries->push(tap(new IncubatorEntry(), function ($e) use ($it) {
                        $e->id          = 'preview-swab-' . $it->id;
                        $e->medium_type = 'swab';
                    }));
                }
                $incubator->setRelation('entries', $entries);

                return $incubator;
            });
        }
    @endphp

    {{-- Header --}}
    <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('analyst.reports.index') }}"
               class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                <span>&larr;</span><span>Kembali</span>
            </a>
            <div>
                @if ($template)
                    <div class="flex items-center gap-2 text-base font-semibold text-gray-800">
                        <span class="font-bold text-gray-700">{{ $template->annex_number }}</span>
                        <span class="text-gray-400">—</span>
                        <span>{{ $template->name }}</span>
                    </div>
                @endif
                <div class="mt-1 text-xs text-gray-500">
                    {{ $report->product_name }} · Batch {{ $report->batch_number }} · {{ $report->created_at->translatedFormat('d M Y') }}
                </div>
            </div>
        </div>

        @unless ($readonly)
            <div class="flex items-center gap-2">
                <button type="submit" form="monitoring-form" name="action" value="draft"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    <span>💾</span><span>Simpan Draft</span>
                </button>
                <button type="submit" form="monitoring-form" name="action" value="finalize"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-600">
                    <span>💾</span><span>Simpan & Selesaikan</span>
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
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        <x-monitoring.section-pemantauan-ruang :report="$report" :readonly="$readonly" />

        <x-monitoring.section-identitas-instrumen :instrument-entries="$instrumentEntries" :readonly="$readonly" />

        <x-monitoring.section-identitas-medium :medium-entries="$mediumEntries" :readonly="$readonly" />

        <x-monitoring.section-inkubasi :incubators="$incubators" :has-swab="$hasSwab" :readonly="$readonly" />
    </form>
@endsection
