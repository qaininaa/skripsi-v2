{{--
    Read-only rendering of an entire report — used by supervisor & manager.
    Reuses the analyst form components with readonly=true so layout stays
    identical across roles.

    Required:
      $report
      $sectionInstances
      $lockMap
      $previewOnly
      $readonly
      $phase
      $allowOverrideLocks
      $monitoringTimeRequiresExistingValue
      $monitoringInOutRequiresExistingActor
--}}
@php
    use Domain\Report\Models\Incubator;
    use Domain\Report\Models\IncubatorEntry;
    use Domain\Report\Models\InstrumentEntry;
    use Domain\Report\Models\MediumEntry;

    $readonly = $readonly ?? true;
    $phase = $phase ?? 'reading';
    $allowOverrideLocks = $allowOverrideLocks ?? false;
    $monitoringTimeRequiresExistingValue = $monitoringTimeRequiresExistingValue ?? false;
    $monitoringInOutRequiresExistingActor = $monitoringInOutRequiresExistingActor ?? false;

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
    <x-report-form.section-room-monitoring :report="$report" :readonly="true" :preview-only="($previewOnly ?? false)" />

    <x-report-form.section-instrument-identity
        :instrument-entries="$instrumentEntries"
        :readonly="$readonly"
        :lock-map="$lockMap ?? []"
        :allow-override-locks="$allowOverrideLocks"
    />

    <x-report-form.section-medium-identity
        :medium-entries="$mediumEntries"
        :readonly="$readonly"
        :lock-map="$lockMap ?? []"
        :allow-override-locks="$allowOverrideLocks"
    />

    <x-report-form.section-incubation
        :incubators="$incubators"
        :has-swab="$hasSwab"
        :readonly="$readonly"
        :lock-map="$lockMap ?? []"
        :allow-override-locks="$allowOverrideLocks"
        :monitoring-time-requires-existing-value="$monitoringTimeRequiresExistingValue"
        :monitoring-in-out-requires-existing-actor="$monitoringInOutRequiresExistingActor"
    />

    @foreach ($sectionInstances as $instance)
        <x-report-form.section-instance
            :instance="$instance"
            :report="$report"
            :phase="$phase"
            :readonly="$readonly"
            :is-admin="false"
            :lock-map="$lockMap ?? []"
            :allow-override-locks="$allowOverrideLocks"
            :monitoring-time-requires-existing-value="$monitoringTimeRequiresExistingValue"
        />
    @endforeach
</div>
