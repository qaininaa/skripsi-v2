<?php

namespace Domain\Report\Repositories;

use Domain\Report\Dtos\CreateReportDto;
use Domain\Report\Dtos\GetReportsFilterDto;
use Domain\Report\Dtos\UpdateReportDto;
use Domain\Report\Interfaces\ReportRepositoryInterface;
use Domain\Report\Models\Report;

/**
 * Eloquent implementation of ReportRepositoryInterface.
 *
 * All database access for the Report domain goes through this class.
 */
class ReportRepository implements ReportRepositoryInterface
{
    /**
     * Retrieve a paginated, filtered list of reports.
     *
     * Results are eager-loaded with reportTemplate and lockedByUser.
     * Ordered by created_at descending (newest first).
     *
     * @param  GetReportsFilterDto  $data  Filter parameters (search, status).
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getReports(GetReportsFilterDto $data)
    {
        return Report::query()
            ->with(['reportTemplate', 'lockedByUser'])
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
        $report->locked_by          = $data->locked_by;
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
}
