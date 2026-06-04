<?php

namespace Domain\Report\Services;

use Domain\Report\Dtos\CreateReportDto;
use Domain\Report\Dtos\GetAnalystReportsFilterDto;
use Domain\Report\Dtos\GetReportsFilterDto;
use Domain\Report\Dtos\UpdateReportDto;
use Domain\Report\Interfaces\ReportRepositoryInterface;
use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
use Domain\Report\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        protected SectionInstanceRepositoryInterface $sectionInstances,
        protected MonitoringService $monitoringService,
        protected ReadingService $readingService,
    ) {
        $this->repository = $repository;
    }

    /**
     * Retrieve a paginated, filtered list of reports (admin scope).
     *
     * @param  GetReportsFilterDto  $dto  Filter parameters (search, status).
     * @return LengthAwarePaginator
     */
    public function getReports(GetReportsFilterDto $dto)
    {
        return $this->repository->getReports($dto);
    }

    /**
     * Analyst inbox listing.
     *
     * @return LengthAwarePaginator
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
     */
    public function createReport(CreateReportDto $dto): Report
    {
        $report = $this->repository->createReport($dto);

        // Eager-load template + sections so the bootstrap can iterate without
        // an extra round-trip per section.
        $this->repository->loadRelations($report, ['reportTemplate.sections']);
        $this->sectionInstanceService->bootstrapForReport($report);

        return $report;
    }

    /**
     * Find a report or fail with a domain-friendly exception.
     *
     * @throws \RuntimeException
     */
    public function findReportById(string $reportId): Report
    {
        $report = $this->repository->findById($reportId);
        if ($report === null) {
            throw (new ModelNotFoundException)->setModel(Report::class, [$reportId]);
        }

        return $report;
    }

    /**
     * Start analyst work and route to the correct phase service.
     */
    public function startAnalystWork(string $reportId, string $analystId): Report
    {
        $report = $this->findReportById($reportId);

        if ($report->status === Report::STATUS_IN_PROGRESS_READING) {
            return $this->readingService->startReading($report, $analystId);
        }

        return $this->monitoringService->startMonitoring($report, $analystId);
    }

    /**
     * Data needed by admin QC preview/detail page.
     *
     * @return array{report: Report, sectionInstances: mixed, lockMap: array, phase: string}
     */
    public function getAssignmentDetailData(string $reportId): array
    {
        $report = $this->repository->findByIdWithRelations($reportId, $this->assignmentDetailRelations());
        if ($report === null) {
            throw (new ModelNotFoundException)->setModel(Report::class, [$reportId]);
        }

        $bundle = $this->sectionInstances->getInstancesForReportWithLocks($report);

        return [
            'report' => $report,
            'sectionInstances' => $bundle['instances'],
            'lockMap' => $bundle['locks'],
            'phase' => $report->isReadingPhase() ? 'reading' : 'monitoring',
        ];
    }

    /**
     * Data needed by analyst fill/preview page.
     *
     * @return array{report: Report, readonly: bool, previewOnly: bool, phase: string, sectionInstances: mixed, lockMap: array}
     */
    public function getFillViewData(string $reportId, string $analystId, bool $previewOnly): array
    {
        $report = $this->repository->findByIdWithRelations($reportId, $this->fillRelations());
        if ($report === null) {
            throw (new ModelNotFoundException)->setModel(Report::class, [$reportId]);
        }

        $bundle = $this->sectionInstances->getInstancesForReportWithLocks($report);
        $isOwner = $report->locked_by !== null && $report->locked_by === $analystId;

        return [
            'report' => $report,
            'readonly' => $previewOnly || ! $isOwner,
            'previewOnly' => $previewOnly,
            'phase' => $report->isReadingPhase() ? 'reading' : 'monitoring',
            'sectionInstances' => $bundle['instances'],
            'lockMap' => $bundle['locks'],
        ];
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
     * @throws \RuntimeException
     */
    public function updateReportById(string $reportId, UpdateReportDto $dto): void
    {
        $this->updateReport($this->findReportById($reportId), $dto);
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

    /**
     * @throws \RuntimeException
     */
    public function deleteReportById(string $reportId): void
    {
        $this->deleteReport($this->findReportById($reportId));
    }

    /**
     * @return array<int, string>
     */
    private function assignmentDetailRelations(): array
    {
        return [
            'reportTemplate.mediumTemplates',
            'reportTemplate.incubatorTemplates',
            'lockedByUser',
            'analysts.user',
            'instrumentEntries',
            'mediumEntries.template',
            'incubators.template',
            'incubators.entries.incubatedBy',
            'incubators.entries.removedBy',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function fillRelations(): array
    {
        return [
            'reportTemplate.mediumTemplates',
            'reportTemplate.incubatorTemplates',
            'lockedByUser',
            'analysts.user',
            'approvals.user',
            'approvals.returnedToUser',
            'instrumentEntries',
            'mediumEntries.template',
            'incubators.template',
            'incubators.entries.incubatedBy',
            'incubators.entries.removedBy',
        ];
    }
}
