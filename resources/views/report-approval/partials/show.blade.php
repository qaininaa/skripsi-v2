{{--
    Shared show page for supervisor & manager.

    Renders the full read-only report (using the same section components as
    the analyst form) plus an action panel for approve / return.

    Required:
      $report           — Report model with all relations
      $approval         — current ReportApproval row (for status / metadata)
      $sectionInstances — collection from SectionInstanceRepository
      $lockMap          — field locks
      $returnTargets    — Collection<User> the actor may return to
      $approveRoute     — route name (e.g. supervisor.reports.approve)
      $returnRoute      — route name (e.g. supervisor.reports.return)
      $backRoute        — route name (e.g. supervisor.laporan-masuk)
      $roleLabel        — 'Supervisor' | 'Manajer'
      $previewOnly      — bool (true = read-only preview from in-progress list)
--}}
@php
    use Domain\Report\Models\ReportApproval;

    $previewOnly       = $previewOnly ?? false;
    $saveMonitoringRoute = $saveMonitoringRoute ?? null;

    $isPending = ! $previewOnly
        && $approval !== null
        && $approval->status === ReportApproval::STATUS_PENDING;

    $canEditMonitoring = $isPending && $saveMonitoringRoute !== null;
    $saveMonitoringConfirmAction = 'save_monitoring_supervisor';
@endphp

{{-- Header --}}
<div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex items-center gap-4">
        <a href="{{ route($backRoute) }}"
           class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <span>&larr;</span><span>Kembali</span>
        </a>
        <div>
            @if ($report->reportTemplate)
                <div class="flex items-center gap-2 text-base font-semibold text-gray-800">
                    <span class="font-bold text-gray-700">{{ $report->reportTemplate->annex_number }}</span>
                    <span class="text-gray-400">—</span>
                    <span>{{ $report->reportTemplate->name }}</span>
                    <x-badges.report-status :status="$report->status" />
                </div>
            @endif
            <div class="mt-1 text-xs text-gray-500">
                {{ $report->product_name }} · Batch {{ $report->batch_number }} · {{ $report->created_at->translatedFormat('d M Y') }}
            </div>
        </div>
    </div>

    @if ($isPending)
        <div class="flex items-center gap-2">
            @if ($canEditMonitoring)
                <button
                    type="button"
                    @click.prevent="
                        $store.saveConfirmModal.open({
                            formId: 'supervisor-monitoring-form',
                            kind: 'draft',
                            title: 'Konfirmasi Simpan Perubahan',
                            draftAction: @js($saveMonitoringConfirmAction),
                            selectedAction: @js($saveMonitoringConfirmAction),
                            submitLabel: 'Simpan Perubahan',
                        })
                    "
                    class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100"
                >
                    <span>Simpan Perubahan</span>
                </button>
            @endif
            <button
                type="button"
                @click.prevent="$store.approvalReturnModal.open()"
                class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100"
            >
                Kembalikan ke Analis
            </button>
            <button
                type="button"
                @click.prevent="$store.approvalApproveModal.open()"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm hover:bg-emerald-100"
            >
                Setujui Laporan
            </button>
        </div>
    @endif
</div>

<x-messages.success-message />
<x-messages.error-message />
<x-messages.validation-errors :except="['username', 'password']" />

@if ($canEditMonitoring)
    <form
        id="supervisor-monitoring-form"
        action="{{ route($saveMonitoringRoute, $report) }}"
        method="POST"
        class="space-y-6"
    >
        @csrf
        @method('PUT')
        @include('report-approval.partials.report-readonly', [
            'report'           => $report,
            'sectionInstances' => $sectionInstances,
            'lockMap'          => $lockMap,
            'previewOnly'      => $previewOnly,
            'readonly'         => false,
            'phase'            => 'monitoring',
            'allowOverrideLocks' => true,
            'monitoringTimeRequiresExistingValue' => true,
            'monitoringInOutRequiresExistingActor' => true,
        ])
    </form>
@else
    {{-- Read-only report content (reuses analyst section components) --}}
    @include('report-approval.partials.report-readonly', [
        'report'           => $report,
        'sectionInstances' => $sectionInstances,
        'lockMap'          => $lockMap,
        'previewOnly'      => $previewOnly,
        'readonly'         => true,
        'phase'            => 'reading',
        'allowOverrideLocks' => false,
        'monitoringTimeRequiresExistingValue' => false,
        'monitoringInOutRequiresExistingActor' => false,
    ])
@endif

@if ($isPending)
    {{-- Modals --}}
    @include('report-approval.partials.approve-modal', [
        'action'    => route($approveRoute, $report),
        'roleLabel' => $roleLabel,
    ])
    @include('report-approval.partials.return-modal', [
        'action'        => route($returnRoute, $report),
        'returnTargets' => $returnTargets,
        'roleLabel'     => $roleLabel,
    ])
@endif

@push('scripts')
    @if ($isPending)
        <script>
            (() => {
                const bootStores = () => {
                    if (! window.Alpine) return;

                    if (! Alpine.store('approvalApproveModal')) {
                        Alpine.store('approvalApproveModal', {
                            isOpen: false,
                            username: '',
                            password: '',
                            showPassword: false,
                            authError: '',
                            open() { this.isOpen = true; this.authError = ''; },
                            close() { this.isOpen = false; this.username = ''; this.password = ''; this.authError = ''; },
                        });
                    }

                    if (! Alpine.store('approvalReturnModal')) {
                        Alpine.store('approvalReturnModal', {
                            isOpen: false,
                            username: '',
                            password: '',
                            showPassword: false,
                            returnedToUserId: '',
                            notes: '',
                            authError: '',
                            open() { this.isOpen = true; this.authError = ''; },
                            close() {
                                this.isOpen = false;
                                this.username = '';
                                this.password = '';
                                this.returnedToUserId = '';
                                this.notes = '';
                                this.authError = '';
                            },
                        });
                    }
                };

                if (window.Alpine) {
                    bootStores();
                } else {
                    document.addEventListener('alpine:init', bootStores, { once: true });
                }
            })();
        </script>
        @if ($errors->has('auth_error'))
            <script>
                const rehydrateAuthModal = () => {
                    if (! window.Alpine) return;
                    if (! Alpine.store('approvalApproveModal') || ! Alpine.store('approvalReturnModal')) return;

                    // Best-effort rehydrate: reopen approve modal if old('action') === 'approve'.
                    const action = @json(old('action'));
                    if (action === 'approve') {
                        Alpine.store('approvalApproveModal').open();
                        Alpine.store('approvalApproveModal').authError = @json($errors->first('auth_error'));
                        Alpine.store('approvalApproveModal').username = @json(old('username', ''));
                    } else if (action === 'return') {
                        Alpine.store('approvalReturnModal').open();
                        Alpine.store('approvalReturnModal').authError = @json($errors->first('auth_error'));
                        Alpine.store('approvalReturnModal').username = @json(old('username', ''));
                        Alpine.store('approvalReturnModal').returnedToUserId = @json(old('returned_to_user_id', ''));
                        Alpine.store('approvalReturnModal').notes = @json(old('notes', ''));
                    }
                };

                if (window.Alpine) {
                    rehydrateAuthModal();
                } else {
                    document.addEventListener('alpine:initialized', rehydrateAuthModal, { once: true });
                }
            </script>
        @endif

        @php
            $saveMonitoringUsernameError = $errors->first('username');
            $saveMonitoringPasswordError = $errors->first('password');
            $hasSaveMonitoringAuthError = $canEditMonitoring
                && old('action') === $saveMonitoringConfirmAction
                && ($saveMonitoringUsernameError !== '' || $saveMonitoringPasswordError !== '');
        @endphp
        @if ($hasSaveMonitoringAuthError)
            <script>
                const rehydrateSaveMonitoringModal = () => {
                    if (! window.Alpine) return;
                    const store = Alpine.store('saveConfirmModal');
                    if (! store) return;

                    store.open({
                        formId: 'supervisor-monitoring-form',
                        kind: 'draft',
                        title: 'Konfirmasi Simpan Monitoring',
                        draftAction: @json($saveMonitoringConfirmAction),
                        selectedAction: @json(old('action', $saveMonitoringConfirmAction)),
                        submitLabel: 'Simpan Monitoring',
                        username: @json(old('username', '')),
                        usernameError: @json($saveMonitoringUsernameError),
                        passwordError: @json($saveMonitoringPasswordError),
                    });
                };

                if (window.Alpine) {
                    rehydrateSaveMonitoringModal();
                } else {
                    document.addEventListener('alpine:initialized', rehydrateSaveMonitoringModal, { once: true });
                }
            </script>
        @endif
    @endif
@endpush
