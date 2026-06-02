<?php

namespace Domain\Report\Interfaces;

use Domain\Report\Dtos\CreateReportDto;
use Domain\Report\Dtos\GetArchiveReportsFilterDto;
use Domain\Report\Dtos\GetAnalystReportsFilterDto;
use Domain\Report\Dtos\GetReportsFilterDto;
use Domain\Report\Dtos\UpdateReportDto;
use Domain\Report\Models\Report;

/**
 * Contract for Report data access.
 */
interface ReportRepositoryInterface
{
    /**
     * Retrieve a paginated, filtered list of reports (admin scope).
     *
     * @param  GetReportsFilterDto  $data  Filter parameters (search, status).
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getReports(GetReportsFilterDto $data);

    /**
     * Retrieve archived reports in a given folder (annex bucket).
     *
     * Only reports that have been approved by manager are included.
     *
     * @param  GetArchiveReportsFilterDto  $data
     * @param  array<int, int>             $annexNumbers
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getArchivedReports(GetArchiveReportsFilterDto $data, array $annexNumbers);

    /**
     * Count manager-approved archived reports in a given annex bucket.
     *
     * @param  array<int, int>  $annexNumbers
     */
    public function countArchivedReports(array $annexNumbers): int;

    /**
     * Find one manager-approved archived report with all read-only relations.
     */
    public function findArchivedReportById(string $id): ?Report;

    /**
     * Retrieve a paginated list of reports for the analyst inbox view.
     * Filters by tab (all, belum_dikerjakan, sedang_dimonitoring, ...).
     *
     * @param  GetAnalystReportsFilterDto  $data
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getReportsForAnalyst(GetAnalystReportsFilterDto $data);

    /**
     * Count reports in each analyst tab. Returns map: tab => count.
     *
     * @param  string|null  $analystId  Needed for the "dikembalikan" tab scope.
     *
     * @return array<string, int>
     */
    public function countByAnalystTab(?string $analystId = null): array;

    /**
     * Count reports returned to a specific analyst for revision.
     */
    public function countReturnedForAnalyst(string $analystId): int;

    /**
     * Count reports currently in review or approval pipeline.
     */
    public function countPendingReviewPipeline(): int;

    /**
     * Persist a new report to the database.
     *
     * @param  CreateReportDto  $data
     * @return Report
     */
    public function createReport(CreateReportDto $data): Report;

    /**
     * Update an existing report with new data.
     *
     * @param  Report           $report
     * @param  UpdateReportDto  $data
     * @return void
     */
    public function updateReport(Report $report, UpdateReportDto $data): void;

    /**
     * Delete a report from the database.
     *
     * @param  Report  $report
     * @return void
     */
    public function deleteReport(Report $report): void;

    /**
     * Find a report by its primary key.
     */
    public function findById(string $id): ?Report;

    /**
     * Update lock + status fields atomically. Pass null in $status to leave
     * status unchanged. monitoringStartedAt only set when not already set.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateMeta(Report $report, array $attributes): void;
}
