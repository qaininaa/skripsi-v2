<?php

namespace App\Http\Controllers\ReportTemplate;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportTemplate\SectionAssignLocationRequest;
use App\Http\Requests\ReportTemplate\SectionStoreRequest;
use App\Http\Requests\ReportTemplate\SectionUpdateRequest;
use Domain\ReportTemplate\Services\SectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function __construct(
        protected SectionService $sectionService,
    ) {}

    /**
     * Show the detail page for a report template (includes section management).
     */
    public function show(string $reportTemplate): View
    {
        $data = $this->sectionService->getTemplateSectionData($reportTemplate);

        return view('report-management.show', [
            'reportTemplate' => $data['reportTemplate'],
            'sectionAvailable' => $data['sectionAvailable'],
        ]);
    }

    /**
     * Store a new section for the given report template.
     */
    public function store(SectionStoreRequest $request, string $reportTemplate): RedirectResponse
    {
        try {
            $this->sectionService->createSection($request->toDTO($reportTemplate));
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('report-templates.show', $reportTemplate)
            ->with('success', 'Berhasil menambahkan section baru.');
    }

    /**
     * Update an existing section.
     */
    public function update(SectionUpdateRequest $request, string $reportTemplate, string $section): RedirectResponse
    {
        try {
            $this->sectionService->updateSectionById($reportTemplate, $section, $request->toDTO());
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('report-templates.show', $reportTemplate)
            ->with('success', 'Berhasil memperbarui section.');
    }

    /**
     * Delete a section.
     */
    public function destroy(string $reportTemplate, string $section): RedirectResponse
    {
        $this->sectionService->deleteSectionById($reportTemplate, $section);

        return redirect()
            ->route('report-templates.show', $reportTemplate)
            ->with('success', 'Berhasil menghapus section.');
    }

    /**
     * Assign a location to a section via inline form POST.
     */
    public function assignLocation(SectionAssignLocationRequest $request, string $reportTemplate, string $section): RedirectResponse
    {
        try {
            $this->sectionService->assignLocationById($reportTemplate, $section, $request->toDTO()->location_id);
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('report-templates.show', $reportTemplate)
            ->with('success', 'Berhasil menambahkan lokasi ke section.');
    }

    /**
     * Remove a location from a section.
     */
    public function removeLocation(string $reportTemplate, string $section, string $location): RedirectResponse
    {
        try {
            $this->sectionService->removeLocationById($reportTemplate, $section, $location);
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('report-templates.show', $reportTemplate)
            ->with('success', 'Berhasil menghapus lokasi dari section.');
    }
}
