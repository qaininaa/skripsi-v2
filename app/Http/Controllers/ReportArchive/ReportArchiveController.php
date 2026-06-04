<?php

namespace App\Http\Controllers\ReportArchive;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportArchive\ReportArchiveIndexRequest;
use Domain\Report\Services\ReportArchiveService;
use Illuminate\View\View;

class ReportArchiveController extends Controller
{
    public function __construct(
        protected ReportArchiveService $archiveService,
    ) {}

    public function index(ReportArchiveIndexRequest $request): View
    {
        $dto = $request->toDTO();
        $folders = $this->archiveService->getFoldersWithCounts();
        $activeFolder = $this->archiveService->findFolderBySlug($dto->folder);
        $reports = $activeFolder !== null
            ? $this->archiveService->getReportsForFolder($dto)
            : null;

        return view('report-archive.index', [
            'folders' => $folders,
            'activeFolder' => $activeFolder,
            'reports' => $reports,
        ]);
    }

    public function show(string $reportId): View
    {
        $data = $this->archiveService->getArchivedReportDetailData($reportId);
        abort_if($data === null, 404);

        return view('report-archive.show', [
            'report' => $data['report'],
            'activeFolder' => $data['activeFolder'],
            'sectionInstances' => $data['sectionInstances'],
            'lockMap' => $data['lockMap'],
        ]);
    }
}
