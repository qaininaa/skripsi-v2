<?php

namespace Domain\ReportTemplate\Services;

use Domain\ReportTemplate\Dtos\CreateReportTemplateDto;
use Domain\ReportTemplate\Dtos\GetReportTemplatesFilterDto;
use Domain\ReportTemplate\Dtos\UpdateReportTemplateDto;
use Domain\ReportTemplate\Interfaces\ReportTemplateRepositoryInterface;
use Domain\ReportTemplate\Models\ReportTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
     * @return LengthAwarePaginator
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
     *
     * @throws \RuntimeException If the combination already exists.
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
     *
     * @throws \RuntimeException If the combination is taken by another template.
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
     * Find report template by ID.
     *
     * @throws \RuntimeException
     */
    public function findReportTemplateById(string $reportTemplateId): ReportTemplate
    {
        $reportTemplate = $this->repository->findById($reportTemplateId);
        if ($reportTemplate === null) {
            throw (new ModelNotFoundException)->setModel(ReportTemplate::class, [$reportTemplateId]);
        }

        return $reportTemplate;
    }

    /**
     * Find report template with edit form relations.
     *
     * @throws \RuntimeException
     */
    public function getReportTemplateForEdit(string $reportTemplateId): ReportTemplate
    {
        $reportTemplate = $this->repository->findByIdWithRelations($reportTemplateId, [
            'mediumTemplates',
            'incubatorTemplates',
        ]);
        if ($reportTemplate === null) {
            throw (new ModelNotFoundException)->setModel(ReportTemplate::class, [$reportTemplateId]);
        }

        return $reportTemplate;
    }

    public function updateReportTemplateById(string $reportTemplateId, UpdateReportTemplateDto $dto): void
    {
        $this->updateReportTemplate($this->findReportTemplateById($reportTemplateId), $dto);
    }

    /**
     * Delete a report template.
     */
    public function deleteReportTemplate(ReportTemplate $reportTemplate): void
    {
        $this->repository->deleteReportTemplate($reportTemplate);
    }

    public function deleteReportTemplateById(string $reportTemplateId): void
    {
        $this->deleteReportTemplate($this->findReportTemplateById($reportTemplateId));
    }
}
