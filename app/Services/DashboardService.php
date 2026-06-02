<?php

namespace App\Services;

use Domain\Report\Interfaces\ReportApprovalRepositoryInterface;
use Domain\Report\Interfaces\ReportRepositoryInterface;
use Domain\Report\Models\ReportApproval;
use InvalidArgumentException;

/**
 * Provides helper methods for resolving dashboard view and display data based on user role.
 */
class DashboardService
{
    public function __construct(
        protected ReportRepositoryInterface $reports,
        protected ReportApprovalRepositoryInterface $approvals,
    ) {
    }

    /**
     * Resolve the Blade view name for the given user role.
     *
     * Throws InvalidArgumentException if the role does not have a dedicated dashboard view,
     *
     * @param  string|null  $role  
     * @return string              
     *
     * @throws InvalidArgumentException  
     */
    public function resolveViewByRole(?string $role): string
    {
        return match ($role) {
            'super'      => 'dashboard.super-admin',
            'admin'      => 'dashboard.admin-qc',
            'analyst'    => 'dashboard.analyst',
            'supervisor' => 'dashboard.supervisor',
            'manager'    => 'dashboard.manager',
            default      => throw new InvalidArgumentException(
                "Tidak ada tampilan dashboard yang dikonfigurasi untuk peran: [{$role}]"
            ),
        };
    }

    /**
     * Resolve the display name for the given user object.
     *
     * @param  object|null  $user 
     * @return string              
     */
    public function resolveUserName(?object $user): string
    {
        return $user?->name ?? 'Pengguna';
    }

    /**
     * Resolve the "perlu ditinjau" report count for the current user role.
     *
     * For supervisor/manager this is scoped to their pending approval inbox.
     * For other roles this is a global pending review + approval count.
     */
    public function resolveReviewCount(?object $user): int
    {
        if ($user === null) {
            return 0;
        }

        $role = (string) ($user->role ?? '');
        $userId = (string) ($user->id ?? '');

        return match ($role) {
            'supervisor' => $this->approvals->countByAssigneeTab(
                ReportApproval::STEP_SUPERVISOR,
                $userId,
            )[ReportApproval::STATUS_PENDING] ?? 0,
            'manager' => $this->approvals->countByAssigneeTab(
                ReportApproval::STEP_MANAGER,
                $userId,
            )[ReportApproval::STATUS_PENDING] ?? 0,
            default => $this->reports->countPendingReviewPipeline(),
        };
    }

    /**
     * Build a human-friendly note shown in dashboard welcome card.
     */
    public function resolveReviewNote(?object $user): string
    {
        $count = $this->resolveReviewCount($user);

        if ($count === 0) {
            return 'Tidak ada laporan yang perlu ditinjau saat ini.';
        }

        return "Ada {$count} laporan yang perlu ditinjau.";
    }

    /**
     * Count reports returned to the given analyst for revision.
     */
    public function resolveRevisionCount(?object $user): int
    {
        if ($user === null || ($user->role ?? null) !== 'analyst') {
            return 0;
        }

        return $this->reports->countReturnedForAnalyst((string) $user->id);
    }

    /**
     * Build dashboard note for analyst revision work.
     */
    public function resolveRevisionNote(?object $user): ?string
    {
        $count = $this->resolveRevisionCount($user);

        return $count > 0
            ? "Ada {$count} laporan yang harus direvisi."
            : null;
    }
}
