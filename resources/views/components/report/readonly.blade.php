@props([
    'report',
    'sectionInstances',
    'instrumentEntries',
    'mediumEntries',
    'incubators',
    'hasSwab' => false,
    'lockMap' => [],
    'previewOnly' => false,
    'readonly' => true,
    'phase' => 'reading',
    'allowOverrideLocks' => false,
    'monitoringTimeRequiresExistingValue' => false,
    'monitoringLabelRequiresExistingValue' => false,
    'monitoringInOutRequiresExistingActor' => false,
])

<div class="space-y-6">
    <x-report-form.section-room-monitoring
        :report="$report"
        :readonly="true"
        :preview-only="$previewOnly"
    />

    <x-report-form.section-instrument-identity
        :instrument-entries="$instrumentEntries"
        :readonly="$readonly"
        :lock-map="$lockMap"
        :allow-override-locks="$allowOverrideLocks"
    />

    <x-report-form.section-medium-identity
        :medium-entries="$mediumEntries"
        :readonly="$readonly"
        :lock-map="$lockMap"
        :allow-override-locks="$allowOverrideLocks"
    />

    <x-report-form.section-incubation
        :incubators="$incubators"
        :has-swab="$hasSwab"
        :readonly="$readonly"
        :lock-map="$lockMap"
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
            :lock-map="$lockMap"
            :allow-override-locks="$allowOverrideLocks"
            :monitoring-time-requires-existing-value="$monitoringTimeRequiresExistingValue"
            :monitoring-label-requires-existing-value="$monitoringLabelRequiresExistingValue"
        />
    @endforeach
</div>
