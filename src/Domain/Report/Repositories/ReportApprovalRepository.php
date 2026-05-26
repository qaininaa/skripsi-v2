<?php

namespace Domain\Report\Repositories;

use Domain\Report\Dtos\GetApprovalReportsFilterDto;
use Domain\Report\Interfaces\ReportApprovalRepositoryInterface;
use Domain\Report\Models\Report;
use Domain\Report\Models\ReportApproval;
use Illuminate\Database\Eloquent\Builder;

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
     * "In progress" for supervisor/manager page = every report that is still
     * ongoing in the pipeline (not completed/archived), regardless of whether
     * the current actor can take action on it right now.
     */
    public function getInProgressReportsForAssignee(int $step, string $userId, string $stage = 'all')
    {
        $query = Report::query()
            ->with(['reportTemplate', 'lockedByUser', 'approvals.user', 'analysts.user'])
            ->whereNotIn('status', [
                Report::STATUS_COMPLETED,
                Report::STATUS_ARCHIVED,
            ]);

        $this->applyInProgressStageFilter($query, $stage);

        return $query
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function countInProgressByStage(int $step, string $userId): array
    {
        $stages = [
            'all',
            'pending',
            'monitoring',
            'reading',
            'review_supervisor',
            'approval_manager',
            'returned',
        ];

        $base = Report::query()
            ->whereNotIn('status', [
                Report::STATUS_COMPLETED,
                Report::STATUS_ARCHIVED,
            ]);

        $counts = [];
        foreach ($stages as $stage) {
            $query = clone $base;
            $this->applyInProgressStageFilter($query, $stage);
            $counts[$stage] = $query->count();
        }

        return $counts;
    }

    /**
     * Apply in-progress stage filter to a report query.
     */
    private function applyInProgressStageFilter(Builder $query, string $stage): void
    {
        match ($stage) {
            'pending' => $query->where('status', Report::STATUS_PENDING),
            'monitoring' => $query->where('status', Report::STATUS_IN_PROGRESS_MONITORING),
            'reading' => $query
                ->where('status', Report::STATUS_IN_PROGRESS_READING)
                ->whereDoesntHave('approvals', function ($q) {
                    $q->where('status', ReportApproval::STATUS_RETURNED);
                }),
            'review_supervisor' => $query->where('status', Report::STATUS_PENDING_REVIEW),
            'approval_manager' => $query->where('status', Report::STATUS_PENDING_APPROVAL),
            'returned' => $query
                ->where('status', Report::STATUS_IN_PROGRESS_READING)
                ->whereHas('approvals', function ($q) {
                    $q->where('status', ReportApproval::STATUS_RETURNED);
                }),
            default => null,
        };
    }
}
