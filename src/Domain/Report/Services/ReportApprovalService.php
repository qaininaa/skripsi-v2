<?php

namespace Domain\Report\Services;

use Domain\Report\Dtos\ApproveReportDto;
use Domain\Report\Dtos\GetApprovalReportsFilterDto;
use Domain\Report\Dtos\GetInProgressReportsFilterDto;
use Domain\Report\Dtos\ReturnReportDto;
use Domain\Report\Interfaces\AnalystRepositoryInterface;
use Domain\Report\Interfaces\ReportApprovalRepositoryInterface;
use Domain\Report\Interfaces\ReportRepositoryInterface;
use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
use Domain\Report\Interfaces\SectionSignatureRepositoryInterface;
use Domain\Report\Models\Analyst;
use Domain\Report\Models\Report;
use Domain\Report\Models\ReportApproval;
use Domain\Report\Models\SectionInstance;
use Domain\Report\Models\SectionSignature;
use Domain\User\Interfaces\UserRepositoryInterface;
use Domain\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Coordinates the supervisor → manager approval pipeline.
 *
 *  Lifecycle (after analyst finalizeReading):
 *    Report.status = pending_review
 *      └─ ReportApproval(step=2, supervisor)         — created here
 *
 *    Supervisor approves:
 *      ReportApproval(step=2).status = approved + signed_at
 *      Report.status = pending_approval
 *      Section signatures (role=review) auto-stamped on every section instance
 *        the analyst signed off on.
 *      ReportApproval(step=3, manager) created.
 *
 *    Manager approves:
 *      ReportApproval(step=3).status = approved + signed_at
 *      Report.status = completed
 *      Section signatures (role=approval) auto-stamped on those same instances.
 *
 *    Either supervisor or manager may return to an analyst:
 *      ReportApproval(current step).status = returned + notes + returned_to_user_id
 *      Analyst signatures are kept until the returned analyst edits a section.
 *      Any later (downstream) approval rows are removed.
 *      Report.status returns to the analyst's phase + locked_by = returnedToUserId
 *
 * Auto-approve scope:
 *   Both review and approval signatures are stamped only on section instances
 *   that already have a `reading` signature — i.e. that an analyst actually
 *   filled in. Empty / not-touched sections are skipped.
 */
class ReportApprovalService
{
    public const STEP_SUPERVISOR = ReportApproval::STEP_SUPERVISOR;

    public const STEP_MANAGER = ReportApproval::STEP_MANAGER;

    public const STATUS_PENDING = ReportApproval::STATUS_PENDING;

    public function __construct(
        protected ReportApprovalRepositoryInterface $approvals,
        protected ReportRepositoryInterface $reports,
        protected AnalystRepositoryInterface $analysts,
        protected SectionInstanceRepositoryInterface $sectionInstances,
        protected SectionSignatureRepositoryInterface $sectionSignatures,
        protected UserRepositoryInterface $users,
    ) {}

    /**
     * Bootstrap the supervisor approval row when a report transitions into
     * pending_review. Idempotent.
     *
     * Returns null if no supervisor user exists in the system; the caller may
     * decide whether that's an error or just leaves the report in a
     * "pending_review without assignee" state.
     */
    public function ensureSupervisorAssignment(Report $report, ?string $supervisorId = null): ?ReportApproval
    {
        $supervisor = $supervisorId === null
            ? $this->users->findFirstByRole('supervisor')
            : $this->users->findByIdAndRole($supervisorId, 'supervisor');

        if ($supervisor === null) {
            if ($supervisorId !== null) {
                throw new \RuntimeException('Supervisor tujuan tidak valid.');
            }

            return null;
        }

        return $this->upsertPendingAssignment(
            $report->id,
            ReportApproval::STEP_SUPERVISOR,
            ReportApproval::ROLE_SUPERVISOR,
            (string) $supervisor->id,
        );
    }

    /**
     * Inbox listing and tab counts for a role assignee.
     *
     * @return array{reports: mixed, counts: array<string, int>}
     */
    public function getInboxData(GetApprovalReportsFilterDto $dto): array
    {
        return [
            'reports' => $this->approvals->getReportsForAssignee($dto),
            'counts' => $this->approvals->countByAssigneeTab($dto->step, $dto->userId),
        ];
    }

    /**
     * Ongoing report listing and stage counts.
     *
     * @return array{reports: mixed, counts: array<string, int>}
     */
    public function getInProgressData(GetInProgressReportsFilterDto $dto): array
    {
        return [
            'reports' => $this->approvals->getInProgressReportsForAssignee($dto->step, $dto->userId, $dto->stage),
            'counts' => $this->approvals->countInProgressByStage($dto->step, $dto->userId),
        ];
    }

    /**
     * Find an approval assigned to the actor for display pages.
     */
    public function findApprovalForAssignee(string $reportId, int $step, string $userId): ?ReportApproval
    {
        $approval = $this->approvals->findByReportAndStep($reportId, $step);

        if ($approval === null || (string) $approval->user_id !== $userId) {
            return null;
        }

        return $approval;
    }

    /**
     * Build report approval detail page data.
     *
     * @return array{report: Report, approval: ReportApproval|null, sectionInstances: mixed, lockMap: array, returnTargets: Collection}
     */
    public function getApprovalDetailData(string $reportId, ?ReportApproval $approval, bool $previewOnly): array
    {
        $report = $this->reports->findByIdWithRelations($reportId, $this->approvalDetailRelations());
        if ($report === null) {
            throw new \RuntimeException('Laporan tidak ditemukan.');
        }

        $bundle = $this->sectionInstances->getInstancesForReportWithLocks($report);

        return [
            'report' => $report,
            'approval' => $approval,
            'sectionInstances' => $bundle['instances'],
            'lockMap' => $bundle['locks'],
            'returnTargets' => $previewOnly ? collect() : $this->returnTargetsForReport($report),
        ];
    }

    /**
     * Supervisor approves: stamp review signatures on signed sections,
     * create manager approval row, transition report to pending_approval.
     *
     * @throws \RuntimeException
     */
    public function approveBySupervisor(Report $report, ApproveReportDto $dto): void
    {
        $approval = $this->getOwnedPendingApproval(
            $report,
            ReportApproval::STEP_SUPERVISOR,
            $dto->actorId,
        );

        DB::transaction(function () use ($report, $approval, $dto) {
            $now = now();

            $this->approvals->update($approval, [
                'status' => ReportApproval::STATUS_APPROVED,
                'signed_at' => $now,
            ]);

            $this->stampSignatures(
                $report,
                SectionSignature::ROLE_REVIEW,
                $dto->actorId,
                $now,
            );

            // Hand off to manager.
            $manager = $this->users->findFirstByRole('manager');
            if ($manager !== null) {
                $this->upsertPendingAssignment(
                    $report->id,
                    ReportApproval::STEP_MANAGER,
                    ReportApproval::ROLE_MANAGER,
                    (string) $manager->id,
                );
                $this->reports->updateMeta($report, [
                    'status' => Report::STATUS_PENDING_APPROVAL,
                ]);
            } else {
                // No manager exists — short-circuit to completed.
                $this->reports->updateMeta($report, [
                    'status' => Report::STATUS_COMPLETED,
                ]);
            }
        });
    }

    /**
     * Manager approves: stamp approval signatures on signed sections,
     * mark report completed.
     *
     * @throws \RuntimeException
     */
    public function approveByManager(Report $report, ApproveReportDto $dto): void
    {
        $approval = $this->getOwnedPendingApproval(
            $report,
            ReportApproval::STEP_MANAGER,
            $dto->actorId,
        );

        DB::transaction(function () use ($report, $approval, $dto) {
            $now = now();

            $this->approvals->update($approval, [
                'status' => ReportApproval::STATUS_APPROVED,
                'signed_at' => $now,
            ]);

            $this->stampSignatures(
                $report,
                SectionSignature::ROLE_APPROVAL,
                $dto->actorId,
                $now,
            );

            $this->reports->updateMeta($report, [
                'status' => Report::STATUS_COMPLETED,
            ]);
        });
    }

    /**
     * Supervisor returns the report to an analyst.
     *
     * @throws \RuntimeException
     */
    public function returnBySupervisor(Report $report, ReturnReportDto $dto): void
    {
        $approval = $this->getOwnedPendingApproval(
            $report,
            ReportApproval::STEP_SUPERVISOR,
            $dto->actorId,
        );

        $this->assertReturnTargetIsAnalyst($report, $dto->returnedToUserId);

        DB::transaction(function () use ($report, $approval, $dto) {
            $this->approvals->update($approval, [
                'status' => ReportApproval::STATUS_RETURNED,
                'notes' => $dto->notes,
                'returned_to_user_id' => $dto->returnedToUserId,
            ]);

            $this->resetForRevision($report, $dto->returnedToUserId);
        });
    }

    /**
     * Manager returns the report to an analyst (skipping supervisor).
     *
     * @throws \RuntimeException
     */
    public function returnByManager(Report $report, ReturnReportDto $dto): void
    {
        $approval = $this->getOwnedPendingApproval(
            $report,
            ReportApproval::STEP_MANAGER,
            $dto->actorId,
        );

        $this->assertReturnTargetIsAnalyst($report, $dto->returnedToUserId);

        DB::transaction(function () use ($report, $approval, $dto) {
            $this->approvals->update($approval, [
                'status' => ReportApproval::STATUS_RETURNED,
                'notes' => $dto->notes,
                'returned_to_user_id' => $dto->returnedToUserId,
            ]);

            // Discard the supervisor approval row entirely; on resubmit
            // ensureSupervisorAssignment() will recreate a fresh one.
            $supervisorApproval = $this->approvals->findByReportAndStep(
                $report->id,
                ReportApproval::STEP_SUPERVISOR,
            );
            if ($supervisorApproval !== null) {
                $this->approvals->delete($supervisorApproval);
            }

            $this->resetForRevision($report, $dto->returnedToUserId);
        });
    }

    /**
     * List analysts (monitoring + reading) for the inbox return-to dropdown.
     *
     * @return Collection<int, User>
     */
    public function returnTargetsForReport(Report $report): Collection
    {
        return $this->analysts->getUsersForReportByTypes($report->id, [
            Analyst::TYPE_MONITORING,
            Analyst::TYPE_READING,
        ]);
    }

    /**
     * Locate the assignee's pending approval row, or throw with a friendly
     * message if it doesn't exist or has already been resolved.
     */
    private function getOwnedPendingApproval(Report $report, int $step, string $actorId): ReportApproval
    {
        $approval = $this->approvals->findByReportAndStep($report->id, $step);

        if ($approval === null) {
            throw new \RuntimeException('Laporan ini tidak menunggu tindakan Anda.');
        }
        if ((string) $approval->user_id !== $actorId) {
            throw new \RuntimeException('Laporan ini bukan ditugaskan kepada Anda.');
        }
        if ($approval->status !== ReportApproval::STATUS_PENDING) {
            throw new \RuntimeException('Laporan ini sudah diproses sebelumnya.');
        }

        return $approval;
    }

    /**
     * Auto-stamp review/approval signatures on sections analysts touched.
     *
     * "Touched" = the section already carries a reading signature.
     */
    private function stampSignatures(Report $report, string $role, string $userId, \DateTimeInterface $when): void
    {
        $instances = $this->sectionInstances->getInstancesWithSignaturesForReport($report->id);

        foreach ($instances as $instance) {
            if (! $this->sectionWasSignedByAnalyst($instance)) {
                continue;
            }

            $this->sectionSignatures->signRole($instance->id, $role, $userId, $when);
        }
    }

    /**
     * Whether the section instance already has the analyst reading signature.
     */
    private function sectionWasSignedByAnalyst(SectionInstance $instance): bool
    {
        return $instance->signatures
            ->contains(fn ($sig) => $sig->role === SectionSignature::ROLE_READING);
    }

    /**
     * Validate the chosen return target is one of the analysts who worked
     * on this report.
     *
     * @throws \RuntimeException
     */
    private function assertReturnTargetIsAnalyst(Report $report, string $userId): void
    {
        $allowed = $this->analysts
            ->getForReportByTypes($report->id, [
                Analyst::TYPE_MONITORING,
                Analyst::TYPE_READING,
            ])
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (! in_array($userId, $allowed, true)) {
            throw new \RuntimeException('Analis tujuan pengembalian tidak valid.');
        }
    }

    /**
     * Reset the report so the analyst can fix it and resubmit.
     *
     * - Status returns to the analyst's editable phase
     * - Locked by the analyst the report was returned to
     * - Supervisor + manager signatures are cleared so downstream roles re-sign.
     *   Analyst signatures are preserved and only invalidated per-section when
     *   that section is actually edited.
     */
    private function resetForRevision(Report $report, string $analystId): void
    {
        $nextStatus = $this->resolveRevisionStatusForAnalyst($report, $analystId);

        $this->sectionSignatures->deleteBySectionIdsAndRoles(
            $this->sectionInstances->getInstanceIdsForReport($report->id),
            [
                SectionSignature::ROLE_REVIEW,
                SectionSignature::ROLE_APPROVAL,
            ],
        );

        $this->reports->updateMeta($report, [
            'status' => $nextStatus,
            'locked_by' => $analystId,
        ]);
    }

    /**
     * Send the revision back to the analyst's responsibility phase.
     * Monitoring owners must return to monitoring, even if they do not also
     * own reading. Reading-only owners return to reading.
     */
    private function resolveRevisionStatusForAnalyst(Report $report, string $analystId): string
    {
        $hasMonitoringRole = $this->analysts->existsForReport(
            $report->id,
            $analystId,
            Analyst::TYPE_MONITORING,
        );
        $hasReadingRole = $this->analysts->existsForReport(
            $report->id,
            $analystId,
            Analyst::TYPE_READING,
        );

        if ($hasMonitoringRole) {
            return Report::STATUS_IN_PROGRESS_MONITORING;
        }

        if ($hasReadingRole) {
            return Report::STATUS_IN_PROGRESS_READING;
        }

        throw new \RuntimeException('Analis tujuan pengembalian tidak memiliki peran pada laporan ini.');
    }

    /**
     * Ensure the assignment row exists and is reset to pending state.
     */
    private function upsertPendingAssignment(
        string $reportId,
        int $step,
        string $roleLabel,
        string $userId,
    ): ReportApproval {
        $existing = $this->approvals->findByReportAndStep($reportId, $step);
        if ($existing !== null) {
            $this->approvals->update($existing, [
                'role_label' => $roleLabel,
                'user_id' => $userId,
                'status' => ReportApproval::STATUS_PENDING,
                'signed_at' => null,
                'notes' => null,
                'returned_to_user_id' => null,
            ]);

            return $this->approvals->refresh($existing);
        }

        return $this->approvals->createOrSkip([
            'report_id' => $reportId,
            'step' => $step,
            'role_label' => $roleLabel,
            'user_id' => $userId,
            'status' => ReportApproval::STATUS_PENDING,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function approvalDetailRelations(): array
    {
        return [
            'reportTemplate',
            'createdByUser',
            'lockedByUser',
            'analysts.user',
            'approvals.user',
            'approvals.returnedToUser',
        ];
    }
}
