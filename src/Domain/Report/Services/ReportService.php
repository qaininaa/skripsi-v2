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
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;

/**
 * Handles business logic for the Report domain.
 *
 * Delegates all data access to the ReportRepositoryInterface.
 */
class ReportService
{
    private const ANALYST_TYPE_MONITORING = 'monitoring';

    private const ANALYST_TYPE_READING = 'reading';

    private const APPROVAL_STATUS_RETURNED = 'returned';

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
     * @return array<string, mixed>
     */
    public function getFillViewData(string $reportId, string $analystId, bool $previewOnly): array
    {
        $report = $this->repository->findByIdWithRelations($reportId, $this->fillRelations());
        if ($report === null) {
            throw (new ModelNotFoundException)->setModel(Report::class, [$reportId]);
        }

        $bundle = $this->sectionInstances->getInstancesForReportWithLocks($report);
        $isOwner = $report->locked_by !== null && $report->locked_by === $analystId;
        $phase = $report->isReadingPhase() ? 'reading' : 'monitoring';
        $readonly = $previewOnly || ! $isOwner;
        $revision = $this->revisionState($report, $phase, $readonly, $analystId);

        return array_merge($revision, [
            'report' => $report,
            'reportId' => (string) $report->id,
            'template' => $report->reportTemplate,
            'hasSwab' => $report->reportTemplate?->hasSwab() ?? false,
            'instrumentEntries' => $this->instrumentEntries($report),
            'mediumEntries' => $this->mediumEntries($report),
            'incubators' => $this->incubators($report),
            'readonly' => $readonly,
            'previewOnly' => $previewOnly,
            'phase' => $phase,
            'sectionInstances' => $bundle['instances'],
            'lockMap' => $bundle['locks'],
        ]);
    }

    private function instrumentEntries(Report $report): Collection
    {
        $instrumentEntries = $report->instrumentEntries
            ->sortBy(fn ($instrument) => $instrument->tool_name === 'Swab Kit' ? 1 : 0)
            ->values();

        if ($instrumentEntries->isNotEmpty()) {
            return $instrumentEntries;
        }

        return collect([
            new Fluent([
                'id' => null,
                'tool_name' => 'Air Sampler',
                'no_id' => null,
                'calibration_date' => null,
                'due_date' => null,
            ]),
        ]);
    }

    private function mediumEntries(Report $report): Collection
    {
        $mediumEntries = $report->mediumEntries
            ->sortBy(fn ($medium) => $medium->is_swab ? 1 : 0)
            ->values();

        if ($mediumEntries->isNotEmpty() || $report->reportTemplate === null) {
            return $mediumEntries;
        }

        return $report->reportTemplate->mediumTemplates
            ->map(function ($template) {
                return new Fluent([
                    'id' => 'preview-'.$template->id,
                    'name' => $template->name,
                    'is_swab' => str_contains(strtolower($template->name), 'swab'),
                    'batch_number' => null,
                    'gpt_number' => null,
                    'expiration_date' => null,
                ]);
            })
            ->sortBy(fn ($medium) => $medium->is_swab ? 1 : 0)
            ->values();
    }

    private function incubators(Report $report): Collection
    {
        if ($report->incubators->isNotEmpty() || $report->reportTemplate === null) {
            return $report->incubators;
        }

        $hasSwab = $report->reportTemplate->hasSwab();

        return $report->reportTemplate->incubatorTemplates->map(function ($template) use ($hasSwab) {
            $entries = collect([
                $this->previewIncubatorEntry((string) $template->id, 'monitoring'),
            ]);

            if ($hasSwab) {
                $entries->push($this->previewIncubatorEntry((string) $template->id, 'swab'));
            }

            return new Fluent([
                'id' => 'preview-'.$template->id,
                'template' => $template,
                'entries' => $entries,
                'no_id' => null,
                'calibration_date' => null,
                'due_date_calibration' => null,
            ]);
        });
    }

    private function previewIncubatorEntry(string $templateId, string $mediumType): Fluent
    {
        return new Fluent([
            'id' => 'preview-'.$mediumType.'-'.$templateId,
            'medium_type' => $mediumType,
            'date_in' => null,
            'time_in' => null,
            'date_out' => null,
            'time_out' => null,
            'incubated_by' => null,
            'removed_by' => null,
            'incubatedBy' => null,
            'removedBy' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function revisionState(Report $report, string $phase, bool $readonly, string $currentUserId): array
    {
        $returnedApproval = $report->approvals
            ->where('status', self::APPROVAL_STATUS_RETURNED)
            ->filter(fn ($approval) => (string) $approval->returned_to_user_id === $currentUserId)
            ->sortByDesc(fn ($approval) => $approval->updated_at?->getTimestamp() ?? 0)
            ->first();

        $isRevisionForMe = $returnedApproval !== null;
        $hasMonitoringRole = $report->analysts->contains(
            fn ($analyst) => $analyst->type === self::ANALYST_TYPE_MONITORING
                && (string) $analyst->user_id === $currentUserId
        );
        $hasReadingRole = $report->analysts->contains(
            fn ($analyst) => $analyst->type === self::ANALYST_TYPE_READING
                && (string) $analyst->user_id === $currentUserId
        );

        return [
            'returnedApproval' => $returnedApproval,
            'isRevisionForMe' => $isRevisionForMe,
            'isDualRoleRevision' => $isRevisionForMe && $hasMonitoringRole && $hasReadingRole,
            'isMonitoringRevisionMode' => ! $readonly
                && $phase === 'monitoring'
                && $isRevisionForMe
                && $hasMonitoringRole,
            'isReadingRevisionSendOnlyMode' => ! $readonly
                && $isRevisionForMe
                && $phase === 'reading',
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
