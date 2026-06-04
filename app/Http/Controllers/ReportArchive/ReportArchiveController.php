<?php

namespace App\Http\Controllers\ReportArchive;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportArchive\ReportArchiveIndexRequest;
use Domain\Report\Services\ReportArchivePrintService;
use Domain\Report\Services\ReportArchiveService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportArchiveController extends Controller
{
    public function __construct(
        protected ReportArchiveService $archiveService,
        protected ReportArchivePrintService $archivePrintService,
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
            'hasSwab' => $data['hasSwab'],
            'instrumentEntries' => $data['instrumentEntries'],
            'mediumEntries' => $data['mediumEntries'],
            'incubators' => $data['incubators'],
            'sectionInstances' => $data['sectionInstances'],
            'lockMap' => $data['lockMap'],
        ]);
    }

    public function print(Request $request, string $reportId): View
    {
        $data = $this->archivePrintService->getPrintData($reportId);
        abort_if($data === null, 404);

        $folderSlug = $request->query('folder');
        $activeFolder = is_string($folderSlug)
            ? $this->archiveService->findFolderBySlug($folderSlug)
            : $data['activeFolder'];

        return view('report-archive.print', array_merge($data, [
            'activeFolder' => $activeFolder,
        ]));
    }
}
