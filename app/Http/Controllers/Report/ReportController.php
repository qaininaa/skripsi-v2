<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\AnalystReportIndexRequest;
use App\Http\Requests\Report\SaveMonitoringRequest;
use App\Http\Requests\Report\SaveReadingRequest;
use Domain\Report\Services\MonitoringService;
use Domain\Report\Services\ReadingService;
use Domain\Report\Services\ReportService;
use Domain\User\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Handles the report fill-in process for analysts.
 *
 * The fill-in form is shared between monitoring and reading phases — the same
 * blade view is reused with the readonly flag flipped based on ownership.
 */
class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected MonitoringService $monitoringService,
        protected ReadingService $readingService,
        protected UserService $userService,
    ) {}

    /**
     * Inbox: tabs (Semua, Belum Dikerjakan, ...) + table of reports.
     */
    public function index(AnalystReportIndexRequest $request): View
    {
        $dto = $request->toDTO();
        $reports = $this->reportService->getReportsForAnalyst($dto);
        $counts = $this->reportService->countByAnalystTab($this->currentAnalystId());

        return view('report.index', [
            'reports' => $reports,
            'counts' => $counts,
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
    public function start(string $report): RedirectResponse
    {
        try {
            $this->reportService->startAnalystWork($report, $this->currentAnalystId());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('report.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('report.fill', $report);
    }

    /**
     * Read-only "Lihat" page for analysts who don't own the report.
     */
    public function show(string $report): View
    {
        return $this->preview($report);
    }

    /**
     * Read-only preview page for analysts.
     */
    public function preview(string $report): View
    {
        $data = $this->reportService->getFillViewData($report, $this->currentAnalystId(), true);

        return view('report.fill', [
            'report' => $data['report'],
            'readonly' => $data['readonly'],
            'previewOnly' => $data['previewOnly'],
            'phase' => $data['phase'],
            'sectionInstances' => $data['sectionInstances'],
            'lockMap' => $data['lockMap'],
            'supervisors' => $this->userService->listSupervisors(),
        ]);
    }

    /**
     * Fill-in form. Editable when current user is the locking analyst,
     * read-only otherwise.
     */
    public function fill(string $report): View
    {
        $data = $this->reportService->getFillViewData($report, $this->currentAnalystId(), false);

        return view('report.fill', [
            'report' => $data['report'],
            'readonly' => $data['readonly'],
            'previewOnly' => $data['previewOnly'],
            'phase' => $data['phase'],
            'sectionInstances' => $data['sectionInstances'],
            'lockMap' => $data['lockMap'],
            'supervisors' => $this->userService->listSupervisors(),
        ]);
    }

    /**
     * Save the monitoring form (draft / release / finalize).
     *
     * draft    → keep lock, kembali ke fill dengan flash success.
     * release  → lepas lock, ke index.
     * finalize → sign + transition ke reading, ke index.
     */
    public function saveMonitoring(SaveMonitoringRequest $request, string $report): RedirectResponse
    {
        try {
            $this->monitoringService->saveMonitoring(
                $this->reportService->findReportById($report),
                $this->currentAnalystId(),
                $request->toDTO(),
                $request->action(),
                $request->supervisorId(),
            );
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return match ($request->action()) {
            MonitoringService::ACTION_TO_READING => redirect()
                ->route('report.fill', $report)
                ->with('success', 'Monitoring revisi tersimpan. Anda masuk ke tahap pembacaan.'),
            MonitoringService::ACTION_FINALIZE_TO_REVIEW => redirect()
                ->route('report.index')
                ->with('success', 'Revisi monitoring tersimpan dan laporan langsung dikirim ke supervisor.'),
            MonitoringService::ACTION_FINALIZE => redirect()
                ->route('report.index')
                ->with('success', 'Monitoring selesai. Laporan berlanjut ke tahap pembacaan.'),
            MonitoringService::ACTION_RELEASE => redirect()
                ->route('report.index')
                ->with('success', 'Monitoring tersimpan. Analis lain dapat melanjutkan.'),
            default => redirect()
                ->route('report.fill', $report)
                ->with('success', 'Draft monitoring berhasil disimpan.'),
        };
    }

    /**
     * Save the reading form (draft / release / finalize).
     */
    public function saveReading(SaveReadingRequest $request, string $report): RedirectResponse
    {
        try {
            $this->readingService->saveReading(
                $this->reportService->findReportById($report),
                $this->currentAnalystId(),
                $request->toDTO(),
                $request->action(),
                $request->supervisorId(),
            );
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return match ($request->action()) {
            ReadingService::ACTION_FINALIZE => redirect()
                ->route('report.index')
                ->with('success', 'Pembacaan selesai. Laporan dikirim untuk review.'),
            ReadingService::ACTION_RELEASE => redirect()
                ->route('report.index')
                ->with('success', 'Pembacaan tersimpan. Analis lain dapat melanjutkan.'),
            default => redirect()
                ->route('report.fill', $report)
                ->with('success', 'Draft pembacaan berhasil disimpan.'),
        };
    }

    private function currentAnalystId(): string
    {
        return (string) Auth::id();
    }
}
