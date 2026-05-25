<?php

namespace Domain\Report\Interfaces;

use Domain\Report\Dtos\GetApprovalReportsFilterDto;
use Domain\Report\Models\Report;
use Domain\Report\Models\ReportApproval;

/**
 * Contract for ReportApproval data access.
 */
interface ReportApprovalRepositoryInterface
{
    /**
     * Find a report's approval row by step.
     */
    public function findByReportAndStep(string $reportId, int $step): ?ReportApproval;

    /**
     * Persist a new approval row. Use firstOrCreate semantics under the hood
     * so callers don't have to worry about duplicates per (report_id, step).
     */
    public function createOrSkip(array $attributes): ReportApproval;

    /**
     * Update an approval row's mutable fields.
     */
    public function update(ReportApproval $approval, array $attributes): void;

    /**
     * Inbox listing for a given step assignee, filtered by tab.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getReportsForAssignee(GetApprovalReportsFilterDto $dto);

    /**
     * Counts per inbox tab for the given step assignee. Returns map: tab => count.
     *
     * @return array<string, int>
     */
    public function countByAssigneeTab(int $step, string $userId): array;

    /**
     * Reports the assignee currently has work-in-progress on (status=pending or
     * returned waiting for them). Used by the "Sedang Dikerjakan" page on the
     * supervisor / manager sidebar.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getInProgressReportsForAssignee(int $step, string $userId);
}
