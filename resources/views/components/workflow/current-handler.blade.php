@props(['report'])

@php
    $status = $report->status;
    $name = '-';
    $role = null;

    if ($status === 'pending') {
        $name = 'Belum dimulai';
    } elseif ($status === 'in_progress_monitoring' || $status === 'in_progress_reading') {
        $name = $report->lockedByUser?->name
            ?? $report->analystOfType('reading')?->user?->name
            ?? $report->analystOfType('monitoring')?->user?->name
            ?? 'Analis belum ditentukan';
        $role = 'Analis';
    } elseif ($status === 'pending_review') {
        $supervisorApproval = $report->approvals->firstWhere('step', \Domain\Report\Models\ReportApproval::STEP_SUPERVISOR);
        $name = $supervisorApproval?->user?->name ?? 'Supervisor';
        $role = 'Supervisor';
    } elseif ($status === 'pending_approval') {
        $managerApproval = $report->approvals->firstWhere('step', \Domain\Report\Models\ReportApproval::STEP_MANAGER);
        $name = $managerApproval?->user?->name ?? 'Manajer';
        $role = 'Manajer';
    } elseif ($status === 'completed') {
        $name = 'Proses selesai';
    } elseif ($status === 'archived') {
        $name = 'Diarsipkan';
    }
@endphp

<div class="text-sm text-gray-700">
    <span class="font-medium">{{ $name }}</span>
    @if ($role !== null)
        <span class="text-xs text-gray-400">({{ $role }})</span>
    @endif
</div>
