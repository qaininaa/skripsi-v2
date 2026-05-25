<?php

namespace App\Http\Controllers\ReportAssignment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ReportIndexRequest;
use App\Http\Requests\Report\ReportStoreRequest;
use App\Http\Requests\Report\ReportUpdateRequest;
use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
use Domain\Report\Models\Report;
use Domain\Report\Services\ReportService;
use Domain\ReportTemplate\Dtos\GetReportTemplatesFilterDto;
use Domain\ReportTemplate\Services\ReportTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Handles the report assignment process (admin creates/edits/deletes report tasks).
 */
class ReportAssignmentController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected ReportTemplateService $reportTemplateService,
        protected SectionInstanceRepositoryInterface $sectionInstanceRepository,
    ) {
    }

    public function index(ReportIndexRequest $request): View
    {
        $reports = $this->reportService->getReports($request->toDTO());

        return view('report-assignment.index', compact('reports'));
    }

    public function create(): View
    {
        $reportTemplates = $this->reportTemplateService->getReportTemplates(new GetReportTemplatesFilterDto());

        return view('report-assignment.create', compact('reportTemplates'));
    }

    public function store(ReportStoreRequest $request): RedirectResponse
    {
        $this->reportService->createReport($request->toDTO());

        return redirect()->route('report-assignment.index')->with('success', 'Berhasil menambahkan tugas pelaporan baru.');
    }

    public function edit(Report $report): View
    {
        $reportTemplates = $this->reportTemplateService->getReportTemplates(new GetReportTemplatesFilterDto());

        return view('report-assignment.edit', compact('report', 'reportTemplates'));
    }

    /**
     * Show: detail laporan + daftar section instances dengan opsi duplikasi.
     */
    public function show(Report $report): View
    {
        $instances = $this->sectionInstanceRepository->getInstancesForReport($report);

        return view('report-assignment.show', compact('report', 'instances'));
    }

    public function update(ReportUpdateRequest $request, Report $report): RedirectResponse
    {
        try {
            $this->reportService->updateReport($report, $request->toDTO());
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('report-assignment.index')->with('success', 'Berhasil memperbarui tugas pelaporan.');
    }

    public function destroy(Report $report): RedirectResponse
    {
        try {
            $this->reportService->deleteReport($report);
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('report-assignment.index')->with('success', 'Berhasil menghapus tugas pelaporan.');
    }
}
