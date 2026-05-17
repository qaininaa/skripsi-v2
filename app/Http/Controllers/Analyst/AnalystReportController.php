<?php

namespace App\Http\Controllers\Analyst;

use App\Http\Controllers\Controller;
use App\Http\Requests\Analyst\AnalystReportIndexRequest;
use App\Http\Requests\Analyst\SaveMonitoringRequest;
use App\Http\Requests\Analyst\SaveReadingRequest;
use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
use Domain\Report\Models\Report;
use Domain\Report\Services\MonitoringService;
use Domain\Report\Services\ReadingService;
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
        protected ReadingService $readingService,
        protected SectionInstanceRepositoryInterface $sectionInstances,
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
     * Click "Mulai" → lock the report to the current analyst.
     *
     * Branches by current report status:
     *   pending / in_progress_monitoring → enter monitoring phase
     *   in_progress_reading             → enter reading phase
     */
    public function start(Report $report): RedirectResponse
    {
        try {
            if ($report->status === Report::STATUS_IN_PROGRESS_READING) {
                $this->readingService->startReading($report, $this->currentAnalystId());
            } else {
                $this->monitoringService->startMonitoring($report, $this->currentAnalystId());
            }
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
            'report'         => $report,
            'readonly'       => true,
            'phase'          => $this->currentPhase($report),
            'sectionInstances' => $this->sectionInstances->getInstancesForReport($report),
        ]);
    }

    /**
     * Fill-in form. Editable when current user is the locking analyst,
     * read-only otherwise.
     */
    public function fill(Report $report): View
    {
        $report->load($this->fillRelations());

        $isOwner = $report->locked_by !== null && $report->locked_by === $this->currentAnalystId();

        return view('report.fill', [
            'report'           => $report,
            'readonly'         => ! $isOwner,
            'phase'            => $this->currentPhase($report),
            'sectionInstances' => $this->sectionInstances->getInstancesForReport($report),
        ]);
    }

    /**
     * Save the monitoring form (draft or finalize → reading).
     */
    public function saveMonitoring(SaveMonitoringRequest $request, Report $report): RedirectResponse
    {
        try {
            $this->monitoringService->saveMonitoring(
                $report,
                $this->currentAnalystId(),
                $request->toDTO(),
                $request->action(),
            );
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('analyst.reports.index')
            ->with('success', 'Data monitoring berhasil disimpan.');
    }

    /**
     * Save the reading form (draft or finalize → review).
     */
    public function saveReading(SaveReadingRequest $request, Report $report): RedirectResponse
    {
        try {
            $this->readingService->saveReading(
                $report,
                $this->currentAnalystId(),
                $request->toDTO(),
                $request->action(),
            );
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('analyst.reports.index')
            ->with('success', 'Data pembacaan berhasil disimpan.');
    }

    /**
     * Tells the view which phase to render: 'monitoring' or 'reading'.
     */
    private function currentPhase(Report $report): string
    {
        return $report->isReadingPhase() ? 'reading' : 'monitoring';
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
