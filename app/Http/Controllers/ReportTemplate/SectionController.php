<?php

namespace App\Http\Controllers\ReportTemplate;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportTemplate\SectionAssignLocationRequest;
use App\Http\Requests\ReportTemplate\SectionStoreRequest;
use App\Http\Requests\ReportTemplate\SectionUpdateRequest;
use Domain\Location\Models\Location;
use Domain\ReportTemplate\Models\ReportTemplate;
use Domain\ReportTemplate\Models\Section;
use Domain\ReportTemplate\Services\SectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function __construct(
        protected SectionService $sectionService,
    ) {
    }

    /**
     * Show the detail page for a report template (includes section management).
     */
    public function show(ReportTemplate $reportTemplate): View
    {
        $reportTemplate = $this->sectionService->getTemplateWithSections($reportTemplate);

        // Pre-load available locations per section for the inline select
        $sectionAvailable = $reportTemplate->sections->mapWithKeys(
            fn ($section) => [$section->id => $this->sectionService->getAvailableLocations($section)]
        );

        return view('report-template-management.show', compact('reportTemplate', 'sectionAvailable'));
    }

    /**
     * Store a new section for the given report template.
     */
    public function store(SectionStoreRequest $request, ReportTemplate $reportTemplate): RedirectResponse
    {
        try {
            $this->sectionService->createSection($request->toDTO($reportTemplate->id));
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
    public function update(SectionUpdateRequest $request, ReportTemplate $reportTemplate, Section $section): RedirectResponse
    {
        try {
            $this->sectionService->updateSection($section, $request->toDTO());
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
    public function destroy(ReportTemplate $reportTemplate, Section $section): RedirectResponse
    {
        $this->sectionService->deleteSection($section);

        return redirect()
            ->route('report-templates.show', $reportTemplate)
            ->with('success', 'Berhasil menghapus section.');
    }

    /**
     * Assign a location to a section via inline form POST.
     */
    public function assignLocation(SectionAssignLocationRequest $request, ReportTemplate $reportTemplate, Section $section): RedirectResponse
    {
        try {
            $this->sectionService->assignLocation($section, $request->validated('location_id'));
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
    public function removeLocation(ReportTemplate $reportTemplate, Section $section, Location $location): RedirectResponse
    {
        try {
            $this->sectionService->removeLocation($section, $location->id);
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
