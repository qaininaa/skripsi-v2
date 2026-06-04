@extends('layouts.app')

@section('title', 'Pengisian Laporan')
@section('page-title', 'Pengisian Laporan')

@section('content')
    <datalist id="microbial-count-options">
        <option value="&lt;1"></option>
        <option value="TNTC"></option>
    </datalist>

    {{-- Header --}}
    <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('report.index') }}"
               class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                <span class="text-xl leading-none font-light">&lt;</span><span>Kembali</span>
            </a>
            <div>
                @if ($template)
                    <div class="flex items-center gap-2 text-base font-semibold text-gray-800">
                        <span class="font-semibold text-gray-700">Annex {{ $template->annex_number }}</span>
                        <span class="text-gray-400">—</span>
                        <span class="font-semibold text-gray-700">{{ $template->name }}</span>
                        <x-badges.report-status :status="$report->status" />
                    </div>
                @endif
                <div class="mt-1 text-xs text-gray-500">
                    {{ $report->product_name }} · Batch {{ $report->batch_number }} · {{ $report->created_at->translatedFormat('d M Y') }}
                </div>
            </div>
        </div>

        @unless ($readonly)
            <x-report-form.fill-actions
                :phase="$phase"
                :draft-action="$draftAction"
                :release-action="$releaseAction"
                :release-label="$releaseLabel"
                :release-description="$releaseDescription"
                :finalize-action="$finalizeAction"
                :finalize-label="$finalizeLabel"
                :finalize-description="$finalizeDescription"
                :to-reading-action="$toReadingAction"
                :send-to-supervisor-action="$sendToSupervisorAction"
                :supervisor-options="$supervisorOptions"
                :is-reading-revision-send-only-mode="$isReadingRevisionSendOnlyMode"
                :is-monitoring-revision-mode="$isMonitoringRevisionMode"
                :is-dual-role-revision="$isDualRoleRevision"
            />
        @endunless
    </div>

    <x-messages.success-message />
    <x-messages.error-message />
    <x-messages.validation-errors :except="['username', 'password', 'supervisor_id']" />

    @if ($isRevisionForMe && $returnedApproval !== null)
        <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
            <div class=" font-semibold text-amber-900">Laporan dikembalikan untuk direvisi</div>
            @if (! empty($returnedApproval->notes))
                <p class="mt-1 text-amber-700 text-sm">“{{ $returnedApproval->notes }}”</p>
            @endif
            <p class="mt-1 text-sm text-amber-700">— {{ $returnedApproval->user?->name ?? $returnedApproval->role_label }}</p>
        </div>
    @endif

    <form
        id="report-form"
        action="{{ $formAction }}"
        method="POST"
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        <x-report-form.section-room-monitoring :report="$report" :readonly="$readonly" :preview-only="$previewOnly" />

        <x-report-form.section-instrument-identity
            :instrument-entries="$instrumentEntries"
            :readonly="$readonly || $phase === 'reading'"
            :lock-map="$lockMap ?? []"
        />

        <x-report-form.section-medium-identity
            :medium-entries="$mediumEntries"
            :readonly="$readonly || $phase === 'reading'"
            :lock-map="$lockMap ?? []"
        />

        <x-report-form.section-incubation
            :incubators="$incubators"
            :has-swab="$hasSwab"
            :readonly="$readonly || $phase === 'reading'"
            :lock-map="$lockMap ?? []"
        />

        @foreach ($sectionInstances as $instance)
            <x-report-form.section-instance
                :instance="$instance"
                :report="$report"
                :phase="$phase"
                :readonly="$readonly"
                :is-admin="false"
                :lock-map="$lockMap ?? []"
            />
        @endforeach
    </form>

    @if ($modalRehydrate !== null)
        <div
            id="save-confirm-rehydrate"
            data-form-id="{{ $modalRehydrate['formId'] }}"
            data-kind="{{ $modalRehydrate['kind'] }}"
            data-title="{{ $modalRehydrate['title'] }}"
            data-draft-action="{{ $modalRehydrate['draftAction'] }}"
            data-finalize-action="{{ $modalRehydrate['finalizeAction'] }}"
            data-draft-label="{{ $modalRehydrate['draftLabel'] }}"
            data-finalize-label="{{ $modalRehydrate['finalizeLabel'] }}"
            data-draft-description="{{ $modalRehydrate['draftDescription'] }}"
            data-finalize-description="{{ $modalRehydrate['finalizeDescription'] }}"
            data-message="{{ $modalRehydrate['message'] }}"
            data-username="{{ $modalRehydrate['username'] }}"
            data-username-error="{{ $modalRehydrate['usernameError'] }}"
            data-password-error="{{ $modalRehydrate['passwordError'] }}"
            data-supervisor-error="{{ $modalRehydrate['supervisorError'] }}"
            data-selected-supervisor-id="{{ $modalRehydrate['selectedSupervisorId'] }}"
            data-requires-supervisor="{{ $modalRehydrate['requiresSupervisor'] ? '1' : '0' }}"
            data-selected-action="{{ $modalRehydrate['selectedAction'] }}"
            data-submit-label="{{ $modalRehydrate['submitLabel'] }}"
            hidden
        ></div>
        <script type="application/json" id="report-save-supervisor-options">@json($supervisorOptions)</script>
    @endif
@endsection
