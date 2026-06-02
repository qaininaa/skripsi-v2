<?php

namespace Domain\Report\Services;

use Domain\Report\Dtos\ApproveReportDto;
use Domain\Report\Dtos\ReturnReportDto;
use Domain\Report\Interfaces\ReportApprovalRepositoryInterface;
use Domain\Report\Interfaces\ReportRepositoryInterface;
use Domain\Report\Models\Analyst;
use Domain\Report\Models\Report;
use Domain\Report\Models\ReportApproval;
use Domain\Report\Models\SectionInstance;
use Domain\Report\Models\SectionSignature;
use Domain\User\Interfaces\UserRepositoryInterface;
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
    public function __construct(
        protected ReportApprovalRepositoryInterface $approvals,
        protected ReportRepositoryInterface $reports,
        protected UserRepositoryInterface $users,
    ) {
    }

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
                'status'    => ReportApproval::STATUS_APPROVED,
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
                $report->status = Report::STATUS_PENDING_APPROVAL;
            } else {
                // No manager exists — short-circuit to completed.
                $report->status = Report::STATUS_COMPLETED;
            }
            $report->save();
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
                'status'    => ReportApproval::STATUS_APPROVED,
                'signed_at' => $now,
            ]);

            $this->stampSignatures(
                $report,
                SectionSignature::ROLE_APPROVAL,
                $dto->actorId,
                $now,
            );

            $report->status = Report::STATUS_COMPLETED;
            $report->save();
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
                'status'              => ReportApproval::STATUS_RETURNED,
                'notes'               => $dto->notes,
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
                'status'              => ReportApproval::STATUS_RETURNED,
                'notes'               => $dto->notes,
                'returned_to_user_id' => $dto->returnedToUserId,
            ]);

            // Discard the supervisor approval row entirely; on resubmit
            // ensureSupervisorAssignment() will recreate a fresh one.
            $supervisorApproval = $this->approvals->findByReportAndStep(
                $report->id,
                ReportApproval::STEP_SUPERVISOR,
            );
            if ($supervisorApproval !== null) {
                $supervisorApproval->delete();
            }

            $this->resetForRevision($report, $dto->returnedToUserId);
        });
    }

    /**
     * List analysts (monitoring + reading) for the inbox return-to dropdown.
     *
     * @return Collection<int, \Domain\User\Models\User>
     */
    public function returnTargetsForReport(Report $report): Collection
    {
        $report->loadMissing('analysts.user');

        return $report->analysts
            ->whereIn('type', [Analyst::TYPE_MONITORING, Analyst::TYPE_READING])
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();
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
        $report->loadMissing(['sectionInstances.signatures']);

        foreach ($report->sectionInstances as $instance) {
            if (! $this->sectionWasSignedByAnalyst($instance)) {
                continue;
            }

            SectionSignature::firstOrCreate(
                [
                    'section_instance_id' => $instance->id,
                    'role'                => $role,
                ],
                [
                    'signed_by' => $userId,
                    'signed_at' => $when,
                ],
            );
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
        $report->loadMissing('analysts');

        $allowed = $report->analysts
            ->whereIn('type', [Analyst::TYPE_MONITORING, Analyst::TYPE_READING])
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
        $report->load(['sectionInstances', 'analysts']);

        $nextStatus = $this->resolveRevisionStatusForAnalyst($report, $analystId);

        $instanceIds = $report->sectionInstances->pluck('id')->all();
        if (! empty($instanceIds)) {
            $rolesToDelete = [
                SectionSignature::ROLE_REVIEW,
                SectionSignature::ROLE_APPROVAL,
            ];

            SectionSignature::query()
                ->whereIn('section_instance_id', $instanceIds)
                ->whereIn('role', $rolesToDelete)
                ->delete();
        }

        $this->reports->updateMeta($report, [
            'status'    => $nextStatus,
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
        $hasMonitoringRole = $report->analysts->contains(
            fn ($analyst) => $analyst->type === Analyst::TYPE_MONITORING
                && (string) $analyst->user_id === $analystId
        );
        $hasReadingRole = $report->analysts->contains(
            fn ($analyst) => $analyst->type === Analyst::TYPE_READING
                && (string) $analyst->user_id === $analystId
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
                'role_label'          => $roleLabel,
                'user_id'             => $userId,
                'status'              => ReportApproval::STATUS_PENDING,
                'signed_at'           => null,
                'notes'               => null,
                'returned_to_user_id' => null,
            ]);

            return $existing->fresh() ?? $existing;
        }

        return $this->approvals->createOrSkip([
            'report_id'  => $reportId,
            'step'       => $step,
            'role_label' => $roleLabel,
            'user_id'    => $userId,
            'status'     => ReportApproval::STATUS_PENDING,
        ]);
    }
}
