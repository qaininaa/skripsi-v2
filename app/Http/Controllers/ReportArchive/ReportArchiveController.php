<?php

namespace App\Http\Controllers\ReportArchive;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportArchive\ReportArchiveIndexRequest;
use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
use Domain\Report\Services\ReportArchiveService;
use Illuminate\View\View;

class ReportArchiveController extends Controller
{
    public function __construct(
        protected ReportArchiveService $archiveService,
        protected SectionInstanceRepositoryInterface $sectionInstances,
    ) {
    }

    public function index(ReportArchiveIndexRequest $request): View
    {
        $dto = $request->toDTO();
        $folders = $this->archiveService->getFoldersWithCounts();
        $activeFolder = $this->archiveService->findFolderBySlug($dto->folder);
        $reports = $activeFolder !== null
            ? $this->archiveService->getReportsForFolder($dto)
            : null;

        return view('report-archive.index', [
            'folders'      => $folders,
            'activeFolder' => $activeFolder,
            'reports'      => $reports,
        ]);
    }

    public function show(string $reportId): View
    {
        $report = $this->archiveService->findArchivedReportById($reportId);
        abort_if($report === null, 404);

        $activeFolder = $this->archiveService->resolveFolderForReport($report);
        $bundle = $this->sectionInstances->getInstancesForReportWithLocks($report);

        return view('report-archive.show', [
            'report'           => $report,
            'activeFolder'     => $activeFolder,
            'sectionInstances' => $bundle['instances'],
            'lockMap'          => $bundle['locks'],
        ]);
    }
}
