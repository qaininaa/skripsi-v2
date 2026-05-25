<?php

namespace Domain\Report\Repositories;

use Domain\Report\Dtos\CreateReportDto;
use Domain\Report\Dtos\GetAnalystReportsFilterDto;
use Domain\Report\Dtos\GetReportsFilterDto;
use Domain\Report\Dtos\UpdateReportDto;
use Domain\Report\Interfaces\ReportRepositoryInterface;
use Domain\Report\Models\Report;
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
     * @param  GetReportsFilterDto  $data
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
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
                    $sub->where('product_name', 'like', '%' . $data->search . '%')
                        ->orWhere('batch_number', 'like', '%' . $data->search . '%');
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
     * Retrieve reports filtered for the analyst inbox.
     *
     * Tab semantics:
     *   - all: every report visible to analysts (everything except archived)
     *   - belum_dikerjakan: status = pending
     *   - sedang_dimonitoring: status = in_progress (someone owns it)
     *   - sedang_dibaca: placeholder bucket for the read phase
     *   - dikirim: status = completed
     *   - dikembalikan: placeholder for revisions
     *
     * @param  GetAnalystReportsFilterDto  $data
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getReportsForAnalyst(GetAnalystReportsFilterDto $data)
    {
        return Report::query()
            ->with(['reportTemplate', 'analysts.user', 'lockedByUser'])
            ->where(fn (Builder $q) => $this->applyAnalystTab($q, $data->tab))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function countByAnalystTab(): array
    {
        $tabs = ['all', 'belum_dikerjakan', 'sedang_dimonitoring', 'sedang_dibaca', 'dikirim', 'dikembalikan'];

        $result = [];
        foreach ($tabs as $tab) {
            $result[$tab] = Report::query()
                ->where(fn (Builder $q) => $this->applyAnalystTab($q, $tab))
                ->count();
        }

        return $result;
    }

    /**
     * Apply a tab filter to a query builder, in place.
     */
    private function applyAnalystTab(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'belum_dikerjakan'    => $query->where('status', Report::STATUS_PENDING),
            'sedang_dimonitoring' => $query->where('status', Report::STATUS_IN_PROGRESS_MONITORING),
            'sedang_dibaca'       => $query->where('status', Report::STATUS_IN_PROGRESS_READING),
            'dikirim'             => $query->whereIn('status', [
                Report::STATUS_PENDING_REVIEW,
                Report::STATUS_PENDING_APPROVAL,
                Report::STATUS_COMPLETED,
            ]),
            'dikembalikan'        => $query->whereRaw('1 = 0'), // placeholder for revision flow
            default               => $query->whereIn('status', [
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
     *
     * @param  CreateReportDto  $data
     * @return Report
     */
    public function createReport(CreateReportDto $data): Report
    {
        $report = new Report();
        $report->report_template_id = $data->report_template_id;
        $report->product_name       = $data->product_name;
        $report->batch_number       = $data->batch_number;
        $report->status             = $data->status;
        $report->created_by         = $data->created_by;
        $report->save();

        return $report;
    }

    /**
     * Update an existing report with new data.
     *
     * @param  Report           $report
     * @param  UpdateReportDto  $data
     * @return void
     */
    public function updateReport(Report $report, UpdateReportDto $data): void
    {
        $report->report_template_id = $data->report_template_id;
        $report->product_name       = $data->product_name;
        $report->batch_number       = $data->batch_number;
        $report->save();
    }

    /**
     * Delete a report from the database.
     *
     * @param  Report  $report
     * @return void
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
    public function updateMeta(Report $report, array $attributes): void
    {
        $report->fill($attributes)->save();
    }
}
