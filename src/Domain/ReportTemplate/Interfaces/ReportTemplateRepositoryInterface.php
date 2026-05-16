<?php

namespace Domain\ReportTemplate\Interfaces;

use Domain\ReportTemplate\Dtos\CreateReportTemplateDto;
use Domain\ReportTemplate\Dtos\GetReportTemplatesFilterDto;
use Domain\ReportTemplate\Dtos\UpdateReportTemplateDto;
use Domain\ReportTemplate\Models\ReportTemplate;

/**
 * Contract for ReportTemplate data access.
 */
interface ReportTemplateRepositoryInterface
{
    /**
     * Retrieve a paginated, filtered list of report templates.
     *
     * @param  GetReportTemplatesFilterDto  $data
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getReportTemplates(GetReportTemplatesFilterDto $data);

    /**
     * Find a report template by its unique combination of annex_number, sop_code, sop_version.
     * Optionally exclude a specific ID (for update uniqueness check).
     *
     * @param  int          $annexNumber
     * @param  string       $sopCode
     * @param  string       $sopVersion
     * @param  string|null  $excludeId
     * @return ReportTemplate|null
     */
    public function findByUniqueCombination(
        int $annexNumber,
        string $sopCode,
        string $sopVersion,
        ?string $excludeId = null
    ): ?ReportTemplate;

    /**
     * Persist a new report template along with its medium and incubator templates.
     *
     * @param  CreateReportTemplateDto  $data
     * @return ReportTemplate
     */
    public function createReportTemplate(CreateReportTemplateDto $data): ReportTemplate;

    /**
     * Update an existing report template and replace its children.
     *
     * @param  ReportTemplate           $reportTemplate
     * @param  UpdateReportTemplateDto  $data
     * @return void
     */
    public function updateReportTemplate(ReportTemplate $reportTemplate, UpdateReportTemplateDto $data): void;

    /**
     * Delete a report template (cascades to medium and incubator templates).
     *
     * @param  ReportTemplate  $reportTemplate
     * @return void
     */
    public function deleteReportTemplate(ReportTemplate $reportTemplate): void;
}
