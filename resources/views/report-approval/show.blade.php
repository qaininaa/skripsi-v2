@extends('layouts.app')

@section('title', 'Detail Laporan - ' . $roleLabel)
@section('page-title', 'Detail Laporan')

@section('content')
    @php
        $previewOnly = $previewOnly ?? false;
        $saveMonitoringRoute = $saveMonitoringRoute ?? null;
        $isPending = $isPending ?? false;
        $canEditMonitoring = $canEditMonitoring ?? false;
        $saveMonitoringFormId = 'approval-monitoring-form';
        $saveMonitoringConfirmAction = 'save_monitoring_approval';
    @endphp

    <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route($backRoute) }}"
               class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                <span>&larr;</span><span>Kembali</span>
            </a>
            <div>
                @if ($report->reportTemplate)
                    <div class="flex items-center gap-2 text-base font-semibold text-gray-800">
                        <span class="font-bold text-gray-700">Annex {{ $report->reportTemplate->annex_number }}</span>
                        <span class="text-gray-400">&mdash;</span>
                        <span>{{ $report->reportTemplate->name }}</span>
                        <x-badges.report-status :status="$report->status" />
                    </div>
                @endif
                <div class="mt-1 text-xs text-gray-500">
                    {{ $report->product_name }} &middot; Batch {{ $report->batch_number }} &middot; {{ $report->created_at->translatedFormat('d M Y') }}
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
                                formId: @js($saveMonitoringFormId),
                                kind: 'draft',
                                title: 'Konfirmasi Simpan Perubahan',
                                draftAction: @js($saveMonitoringConfirmAction),
                                selectedAction: @js($saveMonitoringConfirmAction),
                                submitLabel: 'Simpan Perubahan',
                            })
                        "
                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100"
                    >
                        <span>Simpan Perubahan</span>
                    </button>
                @endif
                <button
                    type="button"
                    @click.prevent="$store.approvalReturnModal.open()"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100"
                >
                    Kembalikan ke Analis
                </button>
                <button
                    type="button"
                    @click.prevent="$store.approvalApproveModal.open()"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm hover:bg-emerald-100"
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
            id="{{ $saveMonitoringFormId }}"
            action="{{ route($saveMonitoringRoute, $report) }}"
            method="POST"
            class="space-y-6"
        >
            @csrf
            @method('PUT')
            <x-report.readonly
                :report="$report"
                :section-instances="$sectionInstances"
                :instrument-entries="$instrumentEntries"
                :medium-entries="$mediumEntries"
                :incubators="$incubators"
                :has-swab="$hasSwab"
                :lock-map="$lockMap"
                :preview-only="$previewOnly"
                :readonly="false"
                phase="monitoring"
                :allow-override-locks="true"
                :monitoring-time-requires-existing-value="true"
                :monitoring-label-requires-existing-value="true"
                :monitoring-in-out-requires-existing-actor="true"
            />
        </form>
    @else
        <x-report.readonly
            :report="$report"
            :section-instances="$sectionInstances"
            :instrument-entries="$instrumentEntries"
            :medium-entries="$mediumEntries"
            :incubators="$incubators"
            :has-swab="$hasSwab"
            :lock-map="$lockMap"
            :preview-only="$previewOnly"
        />
    @endif

    @if ($isPending)
        <x-modals.approve-report-modal
            :action="route($approveRoute, $report)"
            :role-label="$roleLabel"
        />
        <x-modals.return-report-modal
            :action="route($returnRoute, $report)"
            :return-targets="$returnTargets"
            :role-label="$roleLabel"
        />
    @endif
@endsection

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
                        formId: @json($saveMonitoringFormId),
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
