<?php

namespace Domain\ReportTemplate\Interfaces;

use Domain\ReportTemplate\Dtos\CreateReportTemplateDto;
use Domain\ReportTemplate\Dtos\GetReportTemplatesFilterDto;
use Domain\ReportTemplate\Dtos\UpdateReportTemplateDto;
use Domain\ReportTemplate\Models\ReportTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Contract for ReportTemplate data access.
 */
interface ReportTemplateRepositoryInterface
{
    /**
     * Retrieve a paginated, filtered list of report templates.
     *
     * @return LengthAwarePaginator
     */
    public function getReportTemplates(GetReportTemplatesFilterDto $data);

    /**
     * Find a report template by its unique combination of annex_number, sop_code, sop_version.
     * Optionally exclude a specific ID (for update uniqueness check).
     */
    public function findByUniqueCombination(
        int $annexNumber,
        string $sopCode,
        string $sopVersion,
        ?string $excludeId = null
    ): ?ReportTemplate;

    /**
     * Find a report template by id.
     */
    public function findById(string $id): ?ReportTemplate;

    /**
     * Find a report template by id with relations.
     *
     * @param  array<int, string>  $with
     */
    public function findByIdWithRelations(string $id, array $with): ?ReportTemplate;

    /**
     * Persist a new report template along with its medium and incubator templates.
     */
    public function createReportTemplate(CreateReportTemplateDto $data): ReportTemplate;

    /**
     * Update an existing report template and replace its children.
     */
    public function updateReportTemplate(ReportTemplate $reportTemplate, UpdateReportTemplateDto $data): void;

    /**
     * Delete a report template (cascades to medium and incubator templates).
     */
    public function deleteReportTemplate(ReportTemplate $reportTemplate): void;
}
