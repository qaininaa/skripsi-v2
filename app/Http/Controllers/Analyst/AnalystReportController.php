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
 * Analyst inbox + report fill-in entry point.
 *
 * The fill-in form is shared between monitoring and reading phases — the same
 * blade view is reused with the readonly flag flipped based on ownership.
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

        return view('report.index', [
            'reports'   => $reports,
            'counts'    => $counts,
            'activeTab' => $dto->tab,
        ]);
    }

    /**
     * Click "Mulai" → lock the report to current analyst, bootstrap entries,
     * then redirect to the fill-in form.
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

        return redirect()->route('analyst.reports.fill', $report);
    }

    /**
     * Read-only "Lihat" page for analysts who don't own the report.
     */
    public function show(Report $report): View
    {
        $report->load($this->fillRelations());

        return view('report.fill', [
            'report'   => $report,
            'readonly' => true,
        ]);
    }

    /**
     * Fill-in form (sections 1–4). Editable when current user is the
     * locking analyst, read-only otherwise.
     */
    public function fill(Report $report): View
    {
        $report->load($this->fillRelations());

        $isOwner = $report->locked_by !== null && $report->locked_by === $this->currentAnalystId();

        return view('report.fill', [
            'report'   => $report,
            'readonly' => ! $isOwner,
        ]);
    }

    /**
     * Save the fill-in form (draft or final).
     */
    public function save(SaveMonitoringRequest $request, Report $report): RedirectResponse
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
            ->route('analyst.reports.fill', $report)
            ->with('success', 'Data laporan berhasil disimpan.');
    }

    /**
     * Relations needed by the fill-in view to render the full form.
     *
     * @return array<int, string>
     */
    private function fillRelations(): array
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

    private function currentAnalystId(): string
    {
        return (string) $this->user()->id;
    }

    private function user()
    {
        return request()->user();
    }
}
