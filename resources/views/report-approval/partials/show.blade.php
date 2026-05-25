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
--}}
@php
    use Domain\Report\Models\ReportApproval;

    $analystMonitoring = $report->analystOfType('monitoring')?->user;
    $analystReading    = $report->analystOfType('reading')?->user;
    $supervisorRow     = $report->approvals->firstWhere('step', ReportApproval::STEP_SUPERVISOR);
    $managerRow        = $report->approvals->firstWhere('step', ReportApproval::STEP_MANAGER);

    $isPending = $approval->status === ReportApproval::STATUS_PENDING;
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
            <button
                type="button"
                @click.prevent="$store.approvalReturnModal.open()"
                class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100"
            >
                <span>↩</span><span>Kembalikan ke Analis</span>
            </button>
            <button
                type="button"
                @click.prevent="$store.approvalApproveModal.open()"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-600"
            >
                <span>✓</span><span>Setujui Laporan</span>
            </button>
        </div>
    @endif
</div>

<x-messages.success-message />
<x-messages.error-message />
<x-messages.validation-errors :except="['username', 'password']" />

{{-- Approval pipeline summary --}}
<div class="mb-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
    <h2 class="mb-3 text-sm font-semibold text-gray-800">Riwayat Persetujuan</h2>
    <div class="grid gap-3 md:grid-cols-3">
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
            <div class="text-xs text-gray-500">Analis Pembacaan</div>
            <div class="mt-1 text-sm font-semibold text-gray-800">
                {{ $analystReading?->name ?? $analystMonitoring?->name ?? '—' }}
            </div>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
            <div class="text-xs text-gray-500">Supervisor</div>
            <div class="mt-1 text-sm font-semibold text-gray-800">
                {{ $supervisorRow?->user?->name ?? '—' }}
            </div>
            @if ($supervisorRow)
                <div class="mt-0.5 text-[11px] text-gray-500">
                    @if ($supervisorRow->status === 'approved')
                        Disetujui {{ optional($supervisorRow->signed_at)->translatedFormat('d M Y H:i') }}
                    @elseif ($supervisorRow->status === 'returned')
                        Dikembalikan ke {{ $supervisorRow->returnedToUser?->name ?? '—' }}
                    @else
                        Menunggu
                    @endif
                </div>
            @endif
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
            <div class="text-xs text-gray-500">Manajer</div>
            <div class="mt-1 text-sm font-semibold text-gray-800">
                {{ $managerRow?->user?->name ?? '—' }}
            </div>
            @if ($managerRow)
                <div class="mt-0.5 text-[11px] text-gray-500">
                    @if ($managerRow->status === 'approved')
                        Disetujui {{ optional($managerRow->signed_at)->translatedFormat('d M Y H:i') }}
                    @elseif ($managerRow->status === 'returned')
                        Dikembalikan ke {{ $managerRow->returnedToUser?->name ?? '—' }}
                    @else
                        Menunggu
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if ($supervisorRow?->status === 'returned' || $managerRow?->status === 'returned')
        @php
            $latestReturned = $managerRow?->status === 'returned' ? $managerRow : $supervisorRow;
        @endphp
        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
            <p class="font-semibold">Catatan pengembalian terakhir</p>
            <p class="mt-1 whitespace-pre-line">{{ $latestReturned->notes ?? '—' }}</p>
        </div>
    @endif
</div>

{{-- Read-only report content (reuses analyst section components) --}}
@include('report-approval.partials.report-readonly', [
    'report'           => $report,
    'sectionInstances' => $sectionInstances,
    'lockMap'          => $lockMap,
])

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

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            const errorState = @json([
                'auth_error_approve' => session('approve_auth_error'),
                'auth_error_return'  => session('return_auth_error'),
            ]);

            Alpine.store('approvalApproveModal', {
                isOpen: false,
                username: '',
                password: '',
                showPassword: false,
                authError: '',
                open() { this.isOpen = true; this.authError = ''; },
                close() { this.isOpen = false; this.username = ''; this.password = ''; this.authError = ''; },
            });

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
        });
    </script>
    @if ($errors->has('auth_error'))
        <script>
            document.addEventListener('alpine:initialized', () => {
                if (! window.Alpine) return;
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
            });
        </script>
    @endif
@endpush
