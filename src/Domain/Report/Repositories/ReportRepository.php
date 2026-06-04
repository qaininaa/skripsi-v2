<?php

namespace Domain\Report\Repositories;

use Domain\Report\Dtos\CreateReportDto;
use Domain\Report\Dtos\GetAnalystReportsFilterDto;
use Domain\Report\Dtos\GetArchiveReportsFilterDto;
use Domain\Report\Dtos\GetReportsFilterDto;
use Domain\Report\Dtos\UpdateReportDto;
use Domain\Report\Interfaces\ReportRepositoryInterface;
use Domain\Report\Models\Report;
use Domain\Report\Models\ReportApproval;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent implementation of ReportRepositoryInterface.
 *
 * All database access for the Report domain goes through this class.
 */
class ReportRepository implements ReportRepositoryInterface
{
    /**
     * Retrieve a paginated, filtered list of reports (admin scope).
     *
     * @return LengthAwarePaginator
     */
    public function getReports(GetReportsFilterDto $data)
    {
        return Report::query()
            ->with([
                'reportTemplate',
                'createdByUser',
                'lockedByUser',
                'analysts.user',
                'approvals.user',
            ])
            ->when($data->search !== null, function ($query) use ($data) {
                $query->where(function ($sub) use ($data) {
                    $sub->where('product_name', 'like', '%'.$data->search.'%')
                        ->orWhere('batch_number', 'like', '%'.$data->search.'%');
                });
            })
            ->when($data->status !== null, function ($query) use ($data) {
                $query->where('status', $data->status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function getArchivedReports(GetArchiveReportsFilterDto $data, array $annexNumbers)
    {
        return $this->archivedBaseQuery()
            ->with(['reportTemplate'])
            ->whereHas('reportTemplate', function ($query) use ($annexNumbers) {
                $query->whereIn('annex_number', $annexNumbers);
            })
            ->when($data->search !== null, function ($query) use ($data) {
                $query->where(function ($sub) use ($data) {
                    $sub->where('product_name', 'like', '%'.$data->search.'%')
                        ->orWhere('batch_number', 'like', '%'.$data->search.'%')
                        ->orWhereHas('reportTemplate', function ($templateQuery) use ($data) {
                            $templateQuery->where('name', 'like', '%'.$data->search.'%');
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function countArchivedReports(array $annexNumbers): int
    {
        return $this->archivedBaseQuery()
            ->whereHas('reportTemplate', function ($query) use ($annexNumbers) {
                $query->whereIn('annex_number', $annexNumbers);
            })
            ->count();
    }

    /**
     * {@inheritDoc}
     */
    public function findArchivedReportById(string $id): ?Report
    {
        return $this->archivedBaseQuery()
            ->with([
                'reportTemplate',
                'createdByUser',
                'lockedByUser',
                'analysts.user',
                'approvals.user',
                'approvals.returnedToUser',
                'instrumentEntries',
                'mediumEntries.template',
                'incubators.template',
                'incubators.entries.incubatedBy',
                'incubators.entries.removedBy',
            ])
            ->where('id', $id)
            ->first();
    }

    /**
     * Retrieve reports filtered for the analyst inbox.
     *
     * Tab semantics:
     *   - all: every report visible to analysts (everything except archived)
     *   - belum_dikerjakan: status = pending
     *   - sedang_dimonitoring: status = in_progress_monitoring
     *   - sedang_dibaca: status = in_progress_reading
     *   - dikirim: status = completed
     *   - dikembalikan: returned approvals targeted to current analyst
     *
     * @return LengthAwarePaginator
     */
    public function getReportsForAnalyst(GetAnalystReportsFilterDto $data)
    {
        return Report::query()
            ->with(['reportTemplate', 'analysts.user', 'lockedByUser', 'approvals.user'])
            ->where(fn (Builder $q) => $this->applyAnalystTab($q, $data->tab, $data->analyst_id))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function countByAnalystTab(?string $analystId = null): array
    {
        $tabs = ['all', 'belum_dikerjakan', 'sedang_dimonitoring', 'sedang_dibaca', 'dikirim', 'dikembalikan'];

        $result = [];
        foreach ($tabs as $tab) {
            $result[$tab] = Report::query()
                ->where(fn (Builder $q) => $this->applyAnalystTab($q, $tab, $analystId))
                ->count();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function countReturnedForAnalyst(string $analystId): int
    {
        return Report::query()
            ->where(fn (Builder $query) => $this->applyAnalystTab($query, 'dikembalikan', $analystId))
            ->count();
    }

    /**
     * {@inheritDoc}
     */
    public function countPendingReviewPipeline(): int
    {
        return Report::query()
            ->whereIn('status', [
                Report::STATUS_PENDING_REVIEW,
                Report::STATUS_PENDING_APPROVAL,
            ])
            ->count();
    }

    /**
     * Apply a tab filter to a query builder, in place.
     */
    private function applyAnalystTab(Builder $query, string $tab, ?string $analystId = null): Builder
    {
        return match ($tab) {
            'belum_dikerjakan' => $query->where('status', Report::STATUS_PENDING),
            'sedang_dimonitoring' => $query->where('status', Report::STATUS_IN_PROGRESS_MONITORING),
            'sedang_dibaca' => $query->where('status', Report::STATUS_IN_PROGRESS_READING),
            'dikirim' => $query->whereIn('status', [
                Report::STATUS_PENDING_REVIEW,
                Report::STATUS_PENDING_APPROVAL,
                Report::STATUS_COMPLETED,
            ]),
            'dikembalikan' => $query
                ->whereIn('status', [
                    Report::STATUS_IN_PROGRESS_MONITORING,
                    Report::STATUS_IN_PROGRESS_READING,
                ])
                ->whereHas('approvals', function (Builder $q) use ($analystId) {
                    $q->where('status', ReportApproval::STATUS_RETURNED)
                        ->when($analystId !== null, fn (Builder $sq) => $sq->where('returned_to_user_id', $analystId));
                }),
            default => $query->whereIn('status', [
                Report::STATUS_PENDING,
                Report::STATUS_IN_PROGRESS_MONITORING,
                Report::STATUS_IN_PROGRESS_READING,
                Report::STATUS_PENDING_REVIEW,
                Report::STATUS_PENDING_APPROVAL,
                Report::STATUS_COMPLETED,
            ]),
        };
    }

    /**
     * Persist a new report to the database.
     */
    public function createReport(CreateReportDto $data): Report
    {
        $report = new Report;
        $report->report_template_id = $data->report_template_id;
        $report->product_name = $data->product_name;
        $report->batch_number = $data->batch_number;
        $report->status = $data->status;
        $report->created_by = $data->created_by;
        $report->save();

        return $report;
    }

    /**
     * Update an existing report with new data.
     */
    public function updateReport(Report $report, UpdateReportDto $data): void
    {
        $report->report_template_id = $data->report_template_id;
        $report->product_name = $data->product_name;
        $report->batch_number = $data->batch_number;
        $report->save();
    }

    /**
     * Delete a report from the database.
     */
    public function deleteReport(Report $report): void
    {
        $report->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?Report
    {
        return Report::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByIdWithRelations(string $id, array $with): ?Report
    {
        return Report::query()
            ->with($with)
            ->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function refresh(Report $report, array $with = []): Report
    {
        return $report->fresh($with) ?? $report;
    }

    /**
     * {@inheritDoc}
     */
    public function loadRelations(Report $report, array $relations): Report
    {
        return $report->load($relations);
    }

    /**
     * {@inheritDoc}
     */
    public function updateMeta(Report $report, array $attributes): void
    {
        $report->fill($attributes)->save();
    }

    /**
     * Build the base archive query:
     * - only finalized statuses
     * - only reports approved by manager
     */
    private function archivedBaseQuery(): Builder
    {
        return Report::query()
            ->whereIn('status', [
                Report::STATUS_COMPLETED,
                Report::STATUS_ARCHIVED,
            ])
            ->whereHas('approvals', function ($query) {
                $query
                    ->where('step', ReportApproval::STEP_MANAGER)
                    ->where('status', ReportApproval::STATUS_APPROVED);
            });
    }
}
