<?php

namespace App\Http\Controllers\Analyst;

use App\Http\Controllers\Controller;
use App\Http\Requests\Analyst\AnalystReportIndexRequest;
use App\Http\Requests\Analyst\SaveMonitoringRequest;
use Domain\Report\Models\Report;
use Domain\Report\Services\MonitoringService;
use Domain\Report\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Analyst inbox + monitoring entry point.
 *
 * Routes here belong to the analyst role. Admin-side report management lives
 * in App\Http\Controllers\Report\ReportController.
 */
class AnalystReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected MonitoringService $monitoringService,
    ) {
    }

    /**
     * Inbox: tabs (Semua, Belum Dikerjakan, ...) + table of reports.
     */
    public function index(AnalystReportIndexRequest $request): View
    {
        $dto     = $request->toDTO();
        $reports = $this->reportService->getReportsForAnalyst($dto);
        $counts  = $this->reportService->countByAnalystTab();

        return view('analyst.report-inbox.index', [
            'reports'   => $reports,
            'counts'    => $counts,
            'activeTab' => $dto->tab,
        ]);
    }

    /**
     * Click "Mulai" → lock the report to current analyst, bootstrap entries,
     * then redirect to the monitoring form.
     */
    public function start(Report $report): RedirectResponse
    {
        try {
            $this->monitoringService->startMonitoring($report, $this->currentAnalystId());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('analyst.reports.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('analyst.reports.monitoring.edit', $report);
    }

    /**
     * Read-only "Lihat" page for analysts who don't own the report.
     */
    public function show(Report $report): View
    {
        $report->load([
            'reportTemplate.mediumTemplates',
            'reportTemplate.incubatorTemplates',
            'lockedByUser',
            'analysts.user',
            'instrumentEntries',
            'mediumEntries.template',
            'incubators.template',
            'incubators.entries.incubatedBy',
            'incubators.entries.removedBy',
        ]);

        return view('analyst.report-inbox.monitoring', [
            'report'   => $report,
            'readonly' => true,
        ]);
    }

    /**
     * Monitoring form (Section 1–4). Read-only when the current user is not
     * the locking analyst.
     */
    public function editMonitoring(Report $report): View
    {
        $report->load([
            'reportTemplate.mediumTemplates',
            'reportTemplate.incubatorTemplates',
            'lockedByUser',
            'analysts.user',
            'instrumentEntries',
            'mediumEntries.template',
            'incubators.template',
            'incubators.entries.incubatedBy',
            'incubators.entries.removedBy',
        ]);

        $isOwner = $report->locked_by !== null && $report->locked_by === $this->currentAnalystId();

        return view('analyst.report-inbox.monitoring', [
            'report'   => $report,
            'readonly' => ! $isOwner,
        ]);
    }

    /**
     * Save monitoring form (draft or final).
     */
    public function saveMonitoring(SaveMonitoringRequest $request, Report $report): RedirectResponse
    {
        try {
            $this->monitoringService->saveMonitoring(
                $report,
                $this->currentAnalystId(),
                $request->toDTO(),
            );
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('analyst.reports.monitoring.edit', $report)
            ->with('success', 'Data monitoring berhasil disimpan.');
    }

    private function currentAnalystId(): string
    {
        return (string) $this->user()->id;
    }

    private function user()
    {
        return request()->user();
    }
}
