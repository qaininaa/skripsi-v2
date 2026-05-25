{{--
    Read-only rendering of an entire report — used by supervisor & manager.
    Reuses the analyst form components with readonly=true so layout stays
    identical across roles.

    Required:
      $report
      $sectionInstances
      $lockMap
--}}
@php
    use Domain\Report\Models\Incubator;
    use Domain\Report\Models\IncubatorEntry;
    use Domain\Report\Models\InstrumentEntry;
    use Domain\Report\Models\MediumEntry;

    $template = $report->reportTemplate;
    $hasSwab  = $template?->hasSwab() ?? false;

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

    $incubators = $report->incubators;
@endphp

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
            :phase="'reading'"
            :readonly="true"
            :is-admin="false"
            :lock-map="$lockMap ?? []"
        />
    @endforeach
</div>
