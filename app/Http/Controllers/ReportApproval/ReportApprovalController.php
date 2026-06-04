<?php

namespace App\Http\Controllers\ReportApproval;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\ApprovalInboxRequest;
use App\Http\Requests\Approval\ApprovalInProgressRequest;
use App\Http\Requests\Approval\ApproveReportRequest;
use App\Http\Requests\Approval\ReturnReportRequest;
use App\Http\Requests\Approval\SaveApprovalMonitoringRequest;
use Domain\Report\Models\Report;
use Domain\Report\Services\MonitoringService;
use Domain\Report\Services\ReportApprovalService;
use Domain\Report\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Handles the report approval process for both supervisor and manager steps.
 *
 * The $step parameter ('supervisor' or 'manager') is injected via route defaults
 * and determines which approval step constant, service method, and view config
 * to use — eliminating the need for two separate per-role controllers.
 */
class ReportApprovalController extends Controller
{
    public function __construct(
        protected ReportApprovalService $approvalService,
        protected MonitoringService $monitoringService,
        protected ReportService $reportService,
    ) {}

    public function inbox(ApprovalInboxRequest $request, string $step): View
    {
        $config = $this->resolveConfig($step);
        $dto = $request->toDTO();
        $data = $this->approvalService->getInboxData($dto);

        return view('report-inbox.index', [
            'reports' => $data['reports'],
            'counts' => $data['counts'],
            'activeTab' => $dto->tab,
            'showRoute' => $config['showRoute'],
            'tabRoute' => $config['inboxRoute'],
            'roleLabel' => $config['roleLabel'],
        ]);
    }

    public function inProgress(ApprovalInProgressRequest $request, string $step): View
    {
        $config = $this->resolveConfig($step);
        $dto = $request->toDTO();
        $data = $this->approvalService->getInProgressData($dto);

        return view('report-in-progress.index', [
            'reports' => $data['reports'],
            'showRoute' => $config['showRoute'],
            'previewRoute' => $config['previewRoute'],
            'roleLabel' => $config['roleLabel'],
            'activeStage' => $dto->stage,
            'counts' => $data['counts'],
        ]);
    }

    public function show(string $report, string $step): View
    {
        $config = $this->resolveConfig($step);

        $approval = $this->approvalService->findApprovalForAssignee(
            $report,
            $config['stepConstant'],
            (string) Auth::id(),
        );
        abort_if($approval === null, 404);

        $data = $this->approvalService->getApprovalDetailData($report, $approval, false);

        return view('report-approval.show', [
            'report' => $data['report'],
            'approval' => $data['approval'],
            'previewOnly' => false,
            'hasSwab' => $data['hasSwab'],
            'instrumentEntries' => $data['instrumentEntries'],
            'mediumEntries' => $data['mediumEntries'],
            'incubators' => $data['incubators'],
            'sectionInstances' => $data['sectionInstances'],
            'lockMap' => $data['lockMap'],
            'returnTargets' => $data['returnTargets'],
            'isPending' => $data['isPending'],
            'canEditMonitoring' => $data['isPending'],
            'approveRoute' => $config['approveRoute'],
            'returnRoute' => $config['returnRoute'],
            'saveMonitoringRoute' => $config['saveMonitoringRoute'],
            'backRoute' => $config['inboxRoute'],
            'roleLabel' => $config['roleLabel'],
        ]);
    }

    /**
     * Read-only preview page for supervisor/manager ongoing list.
     * This does not require an approval row assigned to the current actor.
     */
    public function preview(string $report, string $step): View
    {
        $config = $this->resolveConfig($step);
        $data = $this->approvalService->getApprovalDetailData($report, null, true);

        return view('report-approval.show', [
            'report' => $data['report'],
            'approval' => null,
            'previewOnly' => true,
            'hasSwab' => $data['hasSwab'],
            'instrumentEntries' => $data['instrumentEntries'],
            'mediumEntries' => $data['mediumEntries'],
            'incubators' => $data['incubators'],
            'sectionInstances' => $data['sectionInstances'],
            'lockMap' => $data['lockMap'],
            'returnTargets' => $data['returnTargets'],
            'isPending' => $data['isPending'],
            'canEditMonitoring' => false,
            'approveRoute' => $config['approveRoute'],
            'returnRoute' => $config['returnRoute'],
            'saveMonitoringRoute' => $config['saveMonitoringRoute'],
            'backRoute' => $config['inProgressRoute'],
            'roleLabel' => $config['roleLabel'],
        ]);
    }

    public function saveMonitoring(
        SaveApprovalMonitoringRequest $request,
        string $report,
        string $step,
    ): RedirectResponse {
        $config = $this->resolveConfig($step);

        try {
            $this->monitoringService->saveMonitoringByApprover(
                $this->reportService->findReportById($report),
                (string) Auth::id(),
                $config['stepConstant'],
                $config['editableMonitoringStatus'],
                $request->toDTO(),
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Perbaikan data monitoring berhasil disimpan.');
    }

    public function approve(ApproveReportRequest $request, string $report, string $step): RedirectResponse
    {
        $config = $this->resolveConfig($step);

        try {
            $report = $this->reportService->findReportById($report);
            if ($step === 'manager') {
                $this->approvalService->approveByManager($report, $request->toDTO());
            } else {
                $this->approvalService->approveBySupervisor($report, $request->toDTO());
            }
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $successMessage = $step === 'manager'
            ? 'Laporan disetujui.'
            : 'Laporan disetujui dan diteruskan ke Manajer.';

        return redirect()
            ->route($config['inboxRoute'])
            ->with('success', $successMessage);
    }

    public function return(ReturnReportRequest $request, string $report, string $step): RedirectResponse
    {
        $config = $this->resolveConfig($step);

        try {
            $report = $this->reportService->findReportById($report);
            if ($step === 'manager') {
                $this->approvalService->returnByManager($report, $request->toDTO());
            } else {
                $this->approvalService->returnBySupervisor($report, $request->toDTO());
            }
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route($config['inboxRoute'])
            ->with('success', 'Laporan dikembalikan ke analis.');
    }

    /**
     * Resolve step-specific configuration for routes, labels, and constants.
     *
     * @return array{
     *   stepConstant: int,
     *   roleLabel: string,
     *   inboxRoute: string,
     *   inProgressRoute: string,
     *   showRoute: string,
     *   previewRoute: string,
     *   approveRoute: string,
     *   returnRoute: string,
     *   saveMonitoringRoute: string,
     *   editableMonitoringStatus: string
     * }
     */
    private function resolveConfig(string $step): array
    {
        return match ($step) {
            'manager' => [
                'stepConstant' => ReportApprovalService::STEP_MANAGER,
                'roleLabel' => 'Manajer',
                'inboxRoute' => 'manager.inbox',
                'inProgressRoute' => 'manager.in-progress',
                'showRoute' => 'manager.reports.show',
                'previewRoute' => 'manager.reports.preview',
                'approveRoute' => 'manager.reports.approve',
                'returnRoute' => 'manager.reports.return',
                'saveMonitoringRoute' => 'manager.reports.save-monitoring',
                'editableMonitoringStatus' => Report::STATUS_PENDING_APPROVAL,
            ],
            default => [
                'stepConstant' => ReportApprovalService::STEP_SUPERVISOR,
                'roleLabel' => 'Supervisor',
                'inboxRoute' => 'supervisor.inbox',
                'inProgressRoute' => 'supervisor.in-progress',
                'showRoute' => 'supervisor.reports.show',
                'previewRoute' => 'supervisor.reports.preview',
                'approveRoute' => 'supervisor.reports.approve',
                'returnRoute' => 'supervisor.reports.return',
                'saveMonitoringRoute' => 'supervisor.reports.save-monitoring',
                'editableMonitoringStatus' => Report::STATUS_PENDING_REVIEW,
            ],
        };
    }
}
