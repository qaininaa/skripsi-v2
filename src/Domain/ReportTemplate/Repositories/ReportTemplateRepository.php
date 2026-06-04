<?php

namespace Domain\ReportTemplate\Repositories;

use Domain\ReportTemplate\Dtos\CreateReportTemplateDto;
use Domain\ReportTemplate\Dtos\GetReportTemplatesFilterDto;
use Domain\ReportTemplate\Dtos\UpdateReportTemplateDto;
use Domain\ReportTemplate\Interfaces\ReportTemplateRepositoryInterface;
use Domain\ReportTemplate\Models\ReportTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReportTemplateRepository implements ReportTemplateRepositoryInterface
{
    /**
     * Retrieve a paginated, filtered list of report templates with child counts.
     *
     * @return LengthAwarePaginator
     */
    public function getReportTemplates(GetReportTemplatesFilterDto $data)
    {
        return ReportTemplate::query()
            ->withCount(['mediumTemplates', 'incubatorTemplates'])
            ->when($data->search !== null, function ($query) use ($data) {
                $query->where(function ($sub) use ($data) {
                    $sub->where('name', 'like', '%'.$data->search.'%')
                        ->orWhere('sop_code', 'like', '%'.$data->search.'%');
                });
            })
            ->orderBy('annex_number')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Find a report template by its unique combination.
     */
    public function findByUniqueCombination(
        int $annexNumber,
        string $sopCode,
        string $sopVersion,
        ?string $excludeId = null
    ): ?ReportTemplate {
        return ReportTemplate::query()
            ->where('annex_number', $annexNumber)
            ->whereRaw('LOWER(sop_code) = ?', [strtolower(trim($sopCode))])
            ->whereRaw('LOWER(sop_version) = ?', [strtolower(trim($sopVersion))])
            ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?ReportTemplate
    {
        return ReportTemplate::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByIdWithRelations(string $id, array $with): ?ReportTemplate
    {
        return ReportTemplate::query()
            ->with($with)
            ->find($id);
    }

    /**
     * Persist a new report template with its medium and incubator templates in a transaction.
     */
    public function createReportTemplate(CreateReportTemplateDto $data): ReportTemplate
    {
        return DB::transaction(function () use ($data) {
            $reportTemplate = new ReportTemplate;
            $reportTemplate->name = $data->name;
            $reportTemplate->annex_number = $data->annex_number;
            $reportTemplate->sop_code = $data->sop_code;
            $reportTemplate->sop_version = $data->sop_version;
            $reportTemplate->save();

            foreach ($data->medium_templates as $medium) {
                $reportTemplate->mediumTemplates()->create([
                    'name' => $medium['name'],
                ]);
            }

            foreach ($data->incubator_templates as $incubator) {
                $reportTemplate->incubatorTemplates()->create([
                    'label' => $incubator['label'],
                    'min_day' => $incubator['min_day'],
                ]);
            }

            return $reportTemplate->load(['mediumTemplates', 'incubatorTemplates']);
        });
    }

    /**
     * Update an existing report template and replace all its children.
     *
     * Children are deleted and re-created to keep the logic simple and consistent.
     */
    public function updateReportTemplate(ReportTemplate $reportTemplate, UpdateReportTemplateDto $data): void
    {
        DB::transaction(function () use ($reportTemplate, $data) {
            $reportTemplate->name = $data->name;
            $reportTemplate->annex_number = $data->annex_number;
            $reportTemplate->sop_code = $data->sop_code;
            $reportTemplate->sop_version = $data->sop_version;
            $reportTemplate->save();

            $reportTemplate->mediumTemplates()->delete();
            foreach ($data->medium_templates as $medium) {
                $reportTemplate->mediumTemplates()->create([
                    'name' => $medium['name'],
                ]);
            }

            $reportTemplate->incubatorTemplates()->delete();
            foreach ($data->incubator_templates as $incubator) {
                $reportTemplate->incubatorTemplates()->create([
                    'label' => $incubator['label'],
                    'min_day' => $incubator['min_day'],
                ]);
            }
        });
    }

    /**
     * Delete a report template. Cascade handles children via DB constraint.
     */
    public function deleteReportTemplate(ReportTemplate $reportTemplate): void
    {
        $reportTemplate->delete();
    }
}
