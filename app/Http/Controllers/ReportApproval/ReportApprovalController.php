<?php

namespace App\Http\Controllers\ReportApproval;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\ApproveReportRequest;
use App\Http\Requests\Approval\ReturnReportRequest;
use Domain\Report\Dtos\GetApprovalReportsFilterDto;
use Domain\Report\Interfaces\ReportApprovalRepositoryInterface;
use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
use Domain\Report\Models\Report;
use Domain\Report\Models\ReportApproval;
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

    public function inProgress(string $step): View
    {
        $config = $this->resolveConfig($step);

        $reports = $this->approvals->getInProgressReportsForAssignee(
            $config['stepConstant'],
            (string) Auth::id(),
        );

        return view('report-approval.in-progress', [
            'reports'   => $reports,
            'showRoute' => $config['showRoute'],
            'roleLabel' => $config['roleLabel'],
        ]);
    }

    public function show(string $step, Report $report): View
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
            'sectionInstances' => $bundle['instances'],
            'lockMap'          => $bundle['locks'],
            'returnTargets'    => $returnTargets,
            'approveRoute'     => $config['approveRoute'],
            'returnRoute'      => $config['returnRoute'],
            'backRoute'        => $config['inboxRoute'],
            'roleLabel'        => $config['roleLabel'],
        ]);
    }

    public function approve(ApproveReportRequest $request, string $step, Report $report): RedirectResponse
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

    public function return(ReturnReportRequest $request, string $step, Report $report): RedirectResponse
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
     * @return array{stepConstant: int, roleLabel: string, inboxRoute: string, showRoute: string, approveRoute: string, returnRoute: string}
     */
    private function resolveConfig(string $step): array
    {
        return match ($step) {
            'manager' => [
                'stepConstant' => ReportApproval::STEP_MANAGER,
                'roleLabel'    => 'Manajer',
                'inboxRoute'   => 'manager.inbox',
                'showRoute'    => 'manager.reports.show',
                'approveRoute' => 'manager.reports.approve',
                'returnRoute'  => 'manager.reports.return',
            ],
            default => [
                'stepConstant' => ReportApproval::STEP_SUPERVISOR,
                'roleLabel'    => 'Supervisor',
                'inboxRoute'   => 'supervisor.inbox',
                'showRoute'    => 'supervisor.reports.show',
                'approveRoute' => 'supervisor.reports.approve',
                'returnRoute'  => 'supervisor.reports.return',
            ],
        };
    }
}
