<?php

namespace Domain\Report\Services;

use Domain\Report\Dtos\CreateReportDto;
use Domain\Report\Dtos\GetReportsFilterDto;
use Domain\Report\Dtos\UpdateReportDto;
use Domain\Report\Interfaces\ReportRepositoryInterface;
use Domain\Report\Models\Report;

/**
 * Handles business logic for the Report domain.
 *
 * Delegates all data access to the ReportRepositoryInterface.
 */
class ReportService
{
    protected ReportRepositoryInterface $repository;

    public function __construct(ReportRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Retrieve a paginated, filtered list of reports.
     *
     * @param  GetReportsFilterDto  $dto  Filter parameters (search, status).
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getReports(GetReportsFilterDto $dto)
    {
        try {
            return $this->repository->getReports($dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Create a new report.
     *
     * @param  CreateReportDto  $dto  Data for the new report.
     * @return Report
     */
    public function createReport(CreateReportDto $dto): Report
    {
        try {
            return $this->repository->createReport($dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Update an existing report.
     *
     * Only pending reports may be edited. Throws RuntimeException if report is not pending.
     *
     * @param  Report           $report  The report model to update.
     * @param  UpdateReportDto  $dto     New data for the report.
     * @return void
     *
     * @throws \RuntimeException  If the report is not in pending status.
     */
    public function updateReport(Report $report, UpdateReportDto $dto): void
    {
        try {
            if ($report->status !== 'pending') {
                throw new \RuntimeException('Hanya laporan dengan status pending yang dapat diubah.');
            }

            $this->repository->updateReport($report, $dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Delete a report.
     *
     * Only pending reports may be deleted. Throws RuntimeException if report is not pending.
     *
     * @param  Report  $report  The report model to delete.
     * @return void
     *
     * @throws \RuntimeException  If the report is not in pending status.
     */
    public function deleteReport(Report $report): void
    {
        try {
            if ($report->status !== 'pending') {
                throw new \RuntimeException('Hanya laporan dengan status pending yang dapat dihapus.');
            }

            $this->repository->deleteReport($report);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
