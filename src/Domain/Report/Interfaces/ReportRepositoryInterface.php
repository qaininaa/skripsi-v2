<?php

namespace Domain\Report\Interfaces;

use Domain\Report\Dtos\CreateReportDto;
use Domain\Report\Dtos\GetReportsFilterDto;
use Domain\Report\Dtos\UpdateReportDto;
use Domain\Report\Models\Report;

/**
 * Contract for Report data access.
 */
interface ReportRepositoryInterface
{
    /**
     * Retrieve a paginated, filtered list of reports.
     *
     * @param  GetReportsFilterDto  $data  Filter parameters (search, status).
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getReports(GetReportsFilterDto $data);

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
}
