<?php

namespace App\Http\Controllers\ReportApproval;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\ApproveReportRequest;
use App\Http\Requests\Approval\ReturnReportRequest;
use App\Http\Requests\Approval\SaveSupervisorMonitoringRequest;
use Domain\Report\Dtos\GetApprovalReportsFilterDto;
use Domain\Report\Interfaces\ReportApprovalRepositoryInterface;
use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
use Domain\Report\Models\Report;
use Domain\Report\Models\ReportApproval;
use Domain\Report\Services\MonitoringService;
use Domain\Report\Services\ReportApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        protected ReportApprovalRepositoryInterface $approvals,
        protected ReportApprovalService $approvalService,
        protected SectionInstanceRepositoryInterface $sectionInstances,
        protected MonitoringService $monitoringService,
    ) {
    }

    public function inbox(Request $request, string $step): View
    {
        $config = $this->resolveConfig($step);
        $tab = (string) $request->query('tab', ReportApproval::STATUS_PENDING);

        $dto = new GetApprovalReportsFilterDto([
            'tab'     => $tab,
            'step'    => $config['stepConstant'],
            'user_id' => (string) Auth::id(),
        ]);

        $reports = $this->approvals->getReportsForAssignee($dto);
        $counts  = $this->approvals->countByAssigneeTab(
            $config['stepConstant'],
            (string) Auth::id(),
        );

        return view('report-approval.inbox', [
            'reports'   => $reports,
            'counts'    => $counts,
            'activeTab' => $tab,
            'showRoute' => $config['showRoute'],
            'tabRoute'  => $config['inboxRoute'],
            'roleLabel' => $config['roleLabel'],
        ]);
    }

    public function inProgress(Request $request, string $step): View
    {
        $config = $this->resolveConfig($step);
        $stage = (string) $request->query('stage', 'all');
        $allowedStages = [
            'all',
            'pending',
            'monitoring',
            'reading',
            'review_supervisor',
            'approval_manager',
            'returned',
        ];
        if (! in_array($stage, $allowedStages, true)) {
            $stage = 'all';
        }

        $reports = $this->approvals->getInProgressReportsForAssignee(
            $config['stepConstant'],
            (string) Auth::id(),
            $stage,
        );
        $counts = $this->approvals->countInProgressByStage(
            $config['stepConstant'],
            (string) Auth::id(),
        );

        return view('report-approval.in-progress', [
            'reports'   => $reports,
            'showRoute' => $config['showRoute'],
            'previewRoute' => $config['previewRoute'],
            'roleLabel' => $config['roleLabel'],
            'activeStage' => $stage,
            'counts'      => $counts,
        ]);
    }

    public function show(Report $report, string $step): View
    {
        $config = $this->resolveConfig($step);

        $approval = $this->approvals->findByReportAndStep(
            $report->id,
            $config['stepConstant'],
        );
        abort_if($approval === null, 404);
        abort_if((string) $approval->user_id !== (string) Auth::id(), 403);

        $report->load([
            'reportTemplate',
            'createdByUser',
            'lockedByUser',
            'analysts.user',
            'approvals.user',
            'approvals.returnedToUser',
        ]);

        $bundle = $this->sectionInstances->getInstancesForReportWithLocks($report);
        $returnTargets = $this->approvalService->returnTargetsForReport($report);

        return view('report-approval.show', [
            'report'           => $report,
            'approval'         => $approval,
            'previewOnly'      => false,
            'sectionInstances' => $bundle['instances'],
            'lockMap'          => $bundle['locks'],
            'returnTargets'    => $returnTargets,
            'approveRoute'     => $config['approveRoute'],
            'returnRoute'      => $config['returnRoute'],
            'saveMonitoringRoute' => $config['saveMonitoringRoute'],
            'backRoute'        => $config['inboxRoute'],
            'roleLabel'        => $config['roleLabel'],
        ]);
    }

    /**
     * Read-only preview page for supervisor/manager ongoing list.
     * This does not require an approval row assigned to the current actor.
     */
    public function preview(Report $report, string $step): View
    {
        $config = $this->resolveConfig($step);

        $report->load([
            'reportTemplate',
            'createdByUser',
            'lockedByUser',
            'analysts.user',
            'approvals.user',
            'approvals.returnedToUser',
        ]);

        $bundle = $this->sectionInstances->getInstancesForReportWithLocks($report);

        return view('report-approval.show', [
            'report'           => $report,
            'approval'         => null,
            'previewOnly'      => true,
            'sectionInstances' => $bundle['instances'],
            'lockMap'          => $bundle['locks'],
            'returnTargets'    => collect(),
            'approveRoute'     => $config['approveRoute'],
            'returnRoute'      => $config['returnRoute'],
            'saveMonitoringRoute' => $config['saveMonitoringRoute'],
            'backRoute'        => $config['inProgressRoute'],
            'roleLabel'        => $config['roleLabel'],
        ]);
    }

    public function saveMonitoring(
        SaveSupervisorMonitoringRequest $request,
        Report $report,
        string $step,
    ): RedirectResponse {
        if ($step !== 'supervisor') {
            abort(404);
        }

        $approval = $this->approvals->findByReportAndStep(
            $report->id,
            ReportApproval::STEP_SUPERVISOR,
        );

        abort_if($approval === null, 404);
        abort_if((string) $approval->user_id !== (string) Auth::id(), 403);

        if ($approval->status !== ReportApproval::STATUS_PENDING) {
            return back()->with('error', 'Laporan ini sudah diproses sebelumnya.');
        }

        try {
            $this->monitoringService->saveMonitoringBySupervisor(
                $report,
                (string) Auth::id(),
                $request->toDTO(),
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Perbaikan data monitoring berhasil disimpan.');
    }

    public function approve(ApproveReportRequest $request, Report $report, string $step): RedirectResponse
    {
        $config = $this->resolveConfig($step);

        try {
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

    public function return(ReturnReportRequest $request, Report $report, string $step): RedirectResponse
    {
        $config = $this->resolveConfig($step);

        try {
            if ($step === 'manager') {
                $this->approvalService->returnByManager($report, $request->toDTO());
            } else {
                $this->approvalService->returnBySupervisor($report, $request->toDTO());
            }
        } catch (\RuntimeException $e) {
            return back()->withInput($request->except('password'))->with('error', $e->getMessage());
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
     *   saveMonitoringRoute: string|null
     * }
     */
    private function resolveConfig(string $step): array
    {
        return match ($step) {
            'manager' => [
                'stepConstant' => ReportApproval::STEP_MANAGER,
                'roleLabel'    => 'Manajer',
                'inboxRoute'   => 'manager.inbox',
                'inProgressRoute' => 'manager.in-progress',
                'showRoute'    => 'manager.reports.show',
                'previewRoute' => 'manager.reports.preview',
                'approveRoute' => 'manager.reports.approve',
                'returnRoute'  => 'manager.reports.return',
                'saveMonitoringRoute' => null,
            ],
            default => [
                'stepConstant' => ReportApproval::STEP_SUPERVISOR,
                'roleLabel'    => 'Supervisor',
                'inboxRoute'   => 'supervisor.inbox',
                'inProgressRoute' => 'supervisor.in-progress',
                'showRoute'    => 'supervisor.reports.show',
                'previewRoute' => 'supervisor.reports.preview',
                'approveRoute' => 'supervisor.reports.approve',
                'returnRoute'  => 'supervisor.reports.return',
                'saveMonitoringRoute' => 'supervisor.reports.save-monitoring',
            ],
        };
    }
}
