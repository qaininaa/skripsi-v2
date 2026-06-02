<?php

namespace App\Http\Controllers\ReportTemplate;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportTemplate\ReportTemplateIndexRequest;
use App\Http\Requests\ReportTemplate\ReportTemplateStoreRequest;
use App\Http\Requests\ReportTemplate\ReportTemplateUpdateRequest;
use Domain\ReportTemplate\Models\ReportTemplate;
use Domain\ReportTemplate\Services\ReportTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReportTemplateController extends Controller
{
    protected ReportTemplateService $reportTemplateService;

    public function __construct(ReportTemplateService $reportTemplateService)
    {
        $this->reportTemplateService = $reportTemplateService;
    }

    public function index(ReportTemplateIndexRequest $request): View
    {
        $reportTemplates = $this->reportTemplateService->getReportTemplates($request->toDTO());

        return view('report-management.index', compact('reportTemplates'));
    }

    public function create(): View
    {
        return view('report-management.create');
    }

    public function store(ReportTemplateStoreRequest $request): RedirectResponse
    {
        try {
            $this->reportTemplateService->createReportTemplate($request->toDTO());
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('report-templates.index')
            ->with('success', 'Berhasil membuat template laporan baru.');
    }

    public function edit(ReportTemplate $reportTemplate): View
    {
        $reportTemplate->load(['mediumTemplates', 'incubatorTemplates']);

        return view('report-management.edit', compact('reportTemplate'));
    }

    public function update(ReportTemplateUpdateRequest $request, ReportTemplate $reportTemplate): RedirectResponse
    {
        try {
            $this->reportTemplateService->updateReportTemplate($reportTemplate, $request->toDTO());
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('report-templates.index')
            ->with('success', 'Berhasil memperbarui template laporan.');
    }

    public function destroy(ReportTemplate $reportTemplate): RedirectResponse
    {
        $this->reportTemplateService->deleteReportTemplate($reportTemplate);

        return redirect()
            ->route('report-templates.index')
            ->with('success', 'Berhasil menghapus template laporan.');
    }
}
