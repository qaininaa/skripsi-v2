<?php

namespace Domain\Report\Interfaces;

use Domain\Report\Dtos\GetApprovalReportsFilterDto;
use Domain\Report\Models\Report;
use Domain\Report\Models\ReportApproval;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
     * Delete an approval row.
     */
    public function delete(ReportApproval $approval): void;

    /**
     * Refresh an approval row after updates.
     */
    public function refresh(ReportApproval $approval): ReportApproval;

    /**
     * Check whether a report has a returned approval for an analyst.
     */
    public function hasReturnedForAnalyst(string $reportId, string $analystId): bool;

    /**
     * Inbox listing for a given step assignee, filtered by tab.
     *
     * @return LengthAwarePaginator
     */
    public function getReportsForAssignee(GetApprovalReportsFilterDto $dto);

    /**
     * Counts per inbox tab for the given step assignee. Returns map: tab => count.
     *
     * @return array<string, int>
     */
    public function countByAssigneeTab(int $step, string $userId): array;

    /**
     * Reports that are still ongoing in the workflow (not completed/archived).
     * Used by the "Sedang Dikerjakan" page on the supervisor / manager sidebar.
     *
     * @return LengthAwarePaginator
     */
    public function getInProgressReportsForAssignee(int $step, string $userId, string $stage = 'all');

    /**
     * Counts for each in-progress stage filter.
     *
     * @return array<string, int>
     */
    public function countInProgressByStage(int $step, string $userId): array;
}
