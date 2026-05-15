<?php

namespace Domain\ReportTemplate\Services;

use Domain\ReportTemplate\Dtos\CreateReportTemplateDto;
use Domain\ReportTemplate\Dtos\GetReportTemplatesFilterDto;
use Domain\ReportTemplate\Dtos\UpdateReportTemplateDto;
use Domain\ReportTemplate\Interfaces\ReportTemplateRepositoryInterface;
use Domain\ReportTemplate\Models\ReportTemplate;

/**
 * Handles business logic for the ReportTemplate domain.
 *
 * Enforces uniqueness of the annex_number + sop_code + sop_version combination
 * and delegates all data access to the ReportTemplateRepositoryInterface.
 */
class ReportTemplateService
{
    protected ReportTemplateRepositoryInterface $repository;

    public function __construct(ReportTemplateRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Retrieve a paginated, filtered list of report templates.
     *
     * @param  GetReportTemplatesFilterDto  $dto
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getReportTemplates(GetReportTemplatesFilterDto $dto)
    {
        return $this->repository->getReportTemplates($dto);
    }

    /**
     * Create a new report template.
     *
     * Validates that the annex_number + sop_code + sop_version combination is unique.
     *
     * @param  CreateReportTemplateDto  $dto
     * @return ReportTemplate
     *
     * @throws \RuntimeException  If the combination already exists.
     */
    public function createReportTemplate(CreateReportTemplateDto $dto): ReportTemplate
    {
        $existing = $this->repository->findByUniqueCombination(
            $dto->annex_number,
            $dto->sop_code,
            $dto->sop_version,
        );

        if ($existing !== null) {
            throw new \RuntimeException(
                'Kombinasi Nomor Annex, Kode SOP, dan Versi SOP sudah digunakan oleh template lain.'
            );
        }

        return $this->repository->createReportTemplate($dto);
    }

    /**
     * Update an existing report template.
     *
     * Validates uniqueness while excluding the current template from the check.
     *
     * @param  ReportTemplate           $reportTemplate
     * @param  UpdateReportTemplateDto  $dto
     * @return void
     *
     * @throws \RuntimeException  If the combination is taken by another template.
     */
    public function updateReportTemplate(ReportTemplate $reportTemplate, UpdateReportTemplateDto $dto): void
    {
        $existing = $this->repository->findByUniqueCombination(
            $dto->annex_number,
            $dto->sop_code,
            $dto->sop_version,
            $reportTemplate->id,
        );

        if ($existing !== null) {
            throw new \RuntimeException(
                'Kombinasi Nomor Annex, Kode SOP, dan Versi SOP sudah digunakan oleh template lain.'
            );
        }

        $this->repository->updateReportTemplate($reportTemplate, $dto);
    }

    /**
     * Delete a report template.
     *
     * @param  ReportTemplate  $reportTemplate
     * @return void
     */
    public function deleteReportTemplate(ReportTemplate $reportTemplate): void
    {
        $this->repository->deleteReportTemplate($reportTemplate);
    }
}
