@extends('layouts.app')

@section('title', 'Pengisian Laporan')
@section('page-title', 'Pengisian Laporan')

@section('content')
    @php
        use Domain\Report\Models\Incubator;
        use Domain\Report\Models\IncubatorEntry;
        use Domain\Report\Models\InstrumentEntry;
        use Domain\Report\Models\MediumEntry;
        use Domain\Report\Models\Report;

        $template = $report->reportTemplate;
        $hasSwab  = $template?->hasSwab() ?? false;

        // Section 2: Identitas Instrumen — Air Sampler is the only tracked instrument.
        $instrumentEntries = $report->instrumentEntries
            ->sortBy(fn ($i) => $i->tool_name === 'Swab Kit' ? 1 : 0)
            ->values();

        if ($instrumentEntries->isEmpty()) {
            $instrumentEntries = collect([
                new InstrumentEntry(['tool_name' => 'Air Sampler']),
            ]);
        }

        // Section 3: Identitas Medium — fall back to MediumTemplate rows.
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

        // Section 4: Inkubasi — build preview Incubators from IncubatorTemplate.
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

        // Phase-driven submit handlers.
        $phase ??= $report->isReadingPhase() ? 'reading' : 'monitoring';

        $formAction = $phase === 'reading'
            ? route('analyst.reports.save-reading', $report)
            : route('analyst.reports.save-monitoring', $report);

        // Inline "Simpan Draft" button keeps the lock and stays on this page.
        $draftAction        = 'draft';

        // Modal "Simpan & Serahkan" → first option releases the lock to others.
        $releaseAction      = 'release';
        $releaseLabel       = $phase === 'reading' ? 'Simpan Pembacaan' : 'Simpan Monitoring';
        $releaseDescription = $phase === 'reading'
            ? 'Draft tersimpan, analis lain bisa melanjutkan pembacaan.'
            : 'Draft tersimpan, analis lain bisa melanjutkan monitoring.';

        // Modal second option — finalize the phase.
        $finalizeAction      = $phase === 'reading' ? 'finalize_reading' : 'finalize_monitoring';
        $finalizeLabel       = $phase === 'reading'
            ? 'Selesaikan Pembacaan & Kirim Review'
            : 'Selesaikan Monitoring & Mulai Pembacaan';
        $finalizeDescription = $phase === 'reading'
            ? 'Data pembacaan sudah lengkap, kirim ke supervisor untuk review.'
            : 'Data monitoring sudah lengkap, lanjut ke tahap baca.';

        $isAdmin = optional(auth()->user())->role === 'admin';
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
                        <x-badges.report-status :status="$report->status" />
                    </div>
                @endif
                <div class="mt-1 text-xs text-gray-500">
                    {{ $report->product_name }} · Batch {{ $report->batch_number }} · {{ $report->created_at->translatedFormat('d M Y') }}
                </div>
            </div>
        </div>

        @unless ($readonly)
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click.prevent="$store.saveConfirmModal.open({
                        formId: 'report-form',
                        kind: 'draft',
                        title: 'Konfirmasi Simpan Draft',
                        draftAction: @js($draftAction),
                        submitLabel: 'Simpan',
                    })"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    <span>💾</span><span>Simpan Draft</span>
                </button>
                <button
                    type="button"
                    @click.prevent="$store.saveConfirmModal.open({
                        formId: 'report-form',
                        kind: 'finalize',
                        title: 'Simpan & Serahkan Laporan',
                        draftAction: @js($releaseAction),
                        finalizeAction: @js($finalizeAction),
                        draftLabel: @js($releaseLabel),
                        finalizeLabel: @js($finalizeLabel),
                        draftDescription: @js($releaseDescription),
                        finalizeDescription: @js($finalizeDescription),
                    })"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-600"
                >
                    <span>📤</span><span>Simpan & Serahkan</span>
                </button>
            </div>
        @endunless
    </div>

    <x-messages.success-message />
    <x-messages.error-message />
    <x-messages.validation-errors />

    <form
        id="report-form"
        action="{{ $formAction }}"
        method="POST"
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        <x-report-form.section-pemantauan-ruang :report="$report" :readonly="$readonly" />

        <x-report-form.section-identitas-instrumen :instrument-entries="$instrumentEntries" :readonly="$readonly || $phase === 'reading'" />

        <x-report-form.section-identitas-medium :medium-entries="$mediumEntries" :readonly="$readonly || $phase === 'reading'" />

        <x-report-form.section-inkubasi :incubators="$incubators" :has-swab="$hasSwab" :readonly="$readonly || $phase === 'reading'" />

        @foreach ($sectionInstances as $instance)
            <x-report-form.section-instance
                :instance="$instance"
                :report="$report"
                :phase="$phase"
                :readonly="$readonly"
                :is-admin="false"
            />
        @endforeach
    </form>

    @push('scripts')
        <script>
            // Live border-red feedback for microbial value inputs.
            (function () {
                const PATTERN = /^(\d+|<1|tntc)$/i;
                const inputs = document.querySelectorAll('input[data-microbial]');
                const validate = (el) => {
                    const v = (el.value || '').trim();
                    const ok = v === '' || PATTERN.test(v);
                    el.classList.toggle('border-red-500', !ok);
                    el.classList.toggle('ring-2',          !ok);
                    el.classList.toggle('ring-red-100',    !ok);
                };
                inputs.forEach((el) => {
                    validate(el);
                    el.addEventListener('input', () => validate(el));
                    el.addEventListener('blur',  () => validate(el));
                });
            })();
        </script>
    @endpush
@endsection
