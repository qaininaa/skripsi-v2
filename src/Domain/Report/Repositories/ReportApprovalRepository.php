<?php

namespace Domain\Report\Repositories;

use Domain\Report\Dtos\GetApprovalReportsFilterDto;
use Domain\Report\Interfaces\ReportApprovalRepositoryInterface;
use Domain\Report\Models\Report;
use Domain\Report\Models\ReportApproval;

class ReportApprovalRepository implements ReportApprovalRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findByReportAndStep(string $reportId, int $step): ?ReportApproval
    {
        return ReportApproval::query()
            ->where('report_id', $reportId)
            ->where('step', $step)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function createOrSkip(array $attributes): ReportApproval
    {
        // Unique constraint (report_id, step) guards against duplicates.
        return ReportApproval::firstOrCreate(
            [
                'report_id' => $attributes['report_id'],
                'step'      => $attributes['step'],
            ],
            $attributes,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function update(ReportApproval $approval, array $attributes): void
    {
        $approval->fill($attributes)->save();
    }

    /**
     * {@inheritDoc}
     */
    public function getReportsForAssignee(GetApprovalReportsFilterDto $dto)
    {
        return Report::query()
            ->with(['reportTemplate', 'lockedByUser', 'approvals.user'])
            ->whereHas('approvals', function ($q) use ($dto) {
                $q->where('step', $dto->step)
                    ->where('user_id', $dto->userId);

                if ($dto->tab !== 'all') {
                    $q->where('status', $dto->tab);
                }
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function countByAssigneeTab(int $step, string $userId): array
    {
        $tabs = [
            ReportApproval::STATUS_PENDING,
            ReportApproval::STATUS_APPROVED,
            ReportApproval::STATUS_RETURNED,
        ];

        $counts = [];
        foreach ($tabs as $tab) {
            $counts[$tab] = ReportApproval::query()
                ->where('step', $step)
                ->where('user_id', $userId)
                ->where('status', $tab)
                ->count();
        }
        $counts['all'] = ReportApproval::query()
            ->where('step', $step)
            ->where('user_id', $userId)
            ->count();

        return $counts;
    }

    /**
     * {@inheritDoc}
     *
     * "In progress" for the assignee = approval row with status=pending. The
     * report is on their plate but they haven't decided yet.
     */
    public function getInProgressReportsForAssignee(int $step, string $userId)
    {
        return Report::query()
            ->with(['reportTemplate', 'lockedByUser', 'approvals.user'])
            ->whereHas('approvals', function ($q) use ($step, $userId) {
                $q->where('step', $step)
                    ->where('user_id', $userId)
                    ->where('status', ReportApproval::STATUS_PENDING);
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();
    }
}
