<?php

namespace App\Http\Controllers\ReportAssignment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\DuplicateSectionRequest;
use Domain\Report\Models\Report;
use Domain\Report\Models\SectionInstance;
use Domain\Report\Services\SectionInstanceService;
use Illuminate\Http\RedirectResponse;

/**
 * Admin QC actions on report-level section instances.
 *
 * Currently supports duplication. The route lives under
 * /report-assignment/{report}/sections/{instance}.
 */
class SectionInstanceController extends Controller
{
    public function __construct(
        protected SectionInstanceService $service,
    ) {
    }

    /**
     * Duplicate a section instance into a new sibling row.
     */
    public function duplicate(
        DuplicateSectionRequest $request,
        Report $report,
        SectionInstance $instance,
    ): RedirectResponse {
        if ($instance->report_id !== $report->id) {
            abort(404);
        }

        try {
            $this->service->duplicate($instance, $request->toDTO()->reason);
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->back()
            ->with('success', 'Section berhasil diduplikasi.');
    }

    /**
     * Delete a duplicated section instance.
     */
    public function destroyDuplicate(
        Report $report,
        SectionInstance $instance,
    ): RedirectResponse {
        if ($instance->report_id !== $report->id) {
            abort(404);
        }

        try {
            $this->service->deleteDuplicate($instance);
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->back()
            ->with('success', 'Section duplikat berhasil dihapus.');
    }
}
