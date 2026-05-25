@extends('layouts.app')

@section('title', 'Detail Tugas Pelaporan')
@section('page-title', 'Detail Tugas Pelaporan')

@section('content')
    @php
        use Domain\Report\Models\Incubator;
        use Domain\Report\Models\IncubatorEntry;
        use Domain\Report\Models\InstrumentEntry;
        use Domain\Report\Models\MediumEntry;

        $template = $report->reportTemplate;
        $hasSwab = $template?->hasSwab() ?? false;

        $instrumentEntries = $report->instrumentEntries
            ->sortBy(fn ($i) => $i->tool_name === 'Swab Kit' ? 1 : 0)
            ->values();
        if ($instrumentEntries->isEmpty()) {
            $instrumentEntries = collect([
                new InstrumentEntry(['tool_name' => 'Air Sampler']),
            ]);
        }

        $mediumEntries = $report->mediumEntries
            ->sortBy(fn ($m) => $m->is_swab ? 1 : 0)
            ->values();
        if ($mediumEntries->isEmpty() && $template) {
            $mediumEntries = $template->mediumTemplates->map(function ($mt) {
                $entry = new MediumEntry();
                $entry->id = 'preview-' . $mt->id;
                $entry->name = $mt->name;
                $entry->is_swab = str_contains(strtolower($mt->name), 'swab');
                return $entry;
            })->sortBy(fn ($m) => $m->is_swab ? 1 : 0)->values();
        }

        $incubators = $report->incubators;
        if ($incubators->isEmpty() && $template) {
            $incubators = $template->incubatorTemplates->map(function ($it) use ($hasSwab) {
                $incubator = new Incubator();
                $incubator->id = 'preview-' . $it->id;
                $incubator->setRelation('template', $it);

                $entries = collect();
                $entries->push(tap(new IncubatorEntry(), function ($e) use ($it) {
                    $e->id = 'preview-mon-' . $it->id;
                    $e->medium_type = 'monitoring';
                }));
                if ($hasSwab) {
                    $entries->push(tap(new IncubatorEntry(), function ($e) use ($it) {
                        $e->id = 'preview-swab-' . $it->id;
                        $e->medium_type = 'swab';
                    }));
                }
                $incubator->setRelation('entries', $entries);

                return $incubator;
            });
        }
    @endphp

    <x-messages.success-message />
    <x-messages.error-message />

    <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('report-assignment.index') }}"
               class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                <span>&larr;</span><span>Kembali</span>
            </a>
            <div>
                @if ($template)
                    <div class="flex items-center gap-2 text-base font-semibold text-gray-800">
                        <span class="font-bold text-gray-700">{{ $template->annex_number }}</span>
                        <span class="text-gray-400">-</span>
                        <span>{{ $template->name }}</span>
                        <x-badges.report-status :status="$report->status" />
                    </div>
                @endif
                <div class="mt-1 text-xs text-gray-500">
                    {{ $report->product_name }} - Batch {{ $report->batch_number }} - {{ $report->created_at->translatedFormat('d M Y') }}
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <x-report-form.section-room-monitoring :report="$report" :readonly="true" />

        <x-report-form.section-instrument-identity
            :instrument-entries="$instrumentEntries"
            :readonly="true"
            :lock-map="$lockMap ?? []"
        />

        <x-report-form.section-medium-identity
            :medium-entries="$mediumEntries"
            :readonly="true"
            :lock-map="$lockMap ?? []"
        />

        <x-report-form.section-incubation
            :incubators="$incubators"
            :has-swab="$hasSwab"
            :readonly="true"
            :lock-map="$lockMap ?? []"
        />

        @foreach ($sectionInstances as $instance)
            <x-report-form.section-instance
                :instance="$instance"
                :report="$report"
                :phase="$phase"
                :readonly="true"
                :is-admin="true"
                :lock-map="$lockMap ?? []"
            />
        @endforeach
    </div>
@endsection
