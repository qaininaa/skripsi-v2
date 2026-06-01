<?php

namespace Domain\Report\Services;

use Domain\Report\Dtos\CreateReportDto;
use Domain\Report\Dtos\GetAnalystReportsFilterDto;
use Domain\Report\Dtos\GetReportsFilterDto;
use Domain\Report\Dtos\UpdateReportDto;
use Domain\Report\Interfaces\ReportRepositoryInterface;
use Domain\Report\Models\Report;
use Domain\Report\Services\SectionInstanceService;

/**
 * Handles business logic for the Report domain.
 *
 * Delegates all data access to the ReportRepositoryInterface.
 */
class ReportService
{
    protected ReportRepositoryInterface $repository;

    public function __construct(
        ReportRepositoryInterface $repository,
        protected SectionInstanceService $sectionInstanceService,
    ) {
        $this->repository = $repository;
    }

    /**
     * Retrieve a paginated, filtered list of reports (admin scope).
     *
     * @param  GetReportsFilterDto  $dto  Filter parameters (search, status).
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getReports(GetReportsFilterDto $dto)
    {
        return $this->repository->getReports($dto);
    }

    /**
     * Analyst inbox listing.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getReportsForAnalyst(GetAnalystReportsFilterDto $dto)
    {
        return $this->repository->getReportsForAnalyst($dto);
    }

    /**
     * Counts per analyst tab, used to render the tab badges.
     *
     * @return array<string, int>
     */
    public function countByAnalystTab(?string $analystId = null): array
    {
        return $this->repository->countByAnalystTab($analystId);
    }

    /**
     * Create a new report.
     *
     * Bootstraps section_instances + locations + entries for the chosen
     * template so analysts can immediately fill in once they "Mulai".
     *
     * @param  CreateReportDto  $dto  Data for the new report.
     * @return Report
     */
    public function createReport(CreateReportDto $dto): Report
    {
        $report = $this->repository->createReport($dto);

        // Eager-load template + sections so the bootstrap can iterate without
        // an extra round-trip per section.
        $report->load('reportTemplate.sections');
        $this->sectionInstanceService->bootstrapForReport($report);

        return $report;
    }

    /**
     * Update an existing report.
     *
     * Only pending reports may be edited.
     *
     * @throws \RuntimeException
     */
    public function updateReport(Report $report, UpdateReportDto $dto): void
    {
        if ($report->status !== Report::STATUS_PENDING) {
            throw new \RuntimeException('Hanya laporan dengan status pending yang dapat diubah.');
        }

        $this->repository->updateReport($report, $dto);
    }

    /**
     * Delete a report.
     *
     * Only pending reports may be deleted.
     *
     * @throws \RuntimeException
     */
    public function deleteReport(Report $report): void
    {
        if ($report->status !== Report::STATUS_PENDING) {
            throw new \RuntimeException('Hanya laporan dengan status pending yang dapat dihapus.');
        }

        $this->repository->deleteReport($report);
    }
}
