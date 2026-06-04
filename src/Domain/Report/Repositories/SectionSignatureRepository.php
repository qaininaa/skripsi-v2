<?php

namespace Domain\Report\Repositories;

use Domain\Report\Interfaces\SectionSignatureRepositoryInterface;
use Domain\Report\Models\SectionSignature;

class SectionSignatureRepository implements SectionSignatureRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function sign(string $sectionInstanceId, string $role, string $userId, \DateTimeInterface $when): SectionSignature
    {
        return SectionSignature::firstOrCreate(
            [
                'section_instance_id' => $sectionInstanceId,
                'role' => $role,
                'signed_by' => $userId,
            ],
            [
                'signed_at' => $when,
            ],
        );
    }

    /**
     * {@inheritDoc}
     */
    public function signRole(string $sectionInstanceId, string $role, string $userId, \DateTimeInterface $when): SectionSignature
    {
        return SectionSignature::firstOrCreate(
            [
                'section_instance_id' => $sectionInstanceId,
                'role' => $role,
            ],
            [
                'signed_by' => $userId,
                'signed_at' => $when,
            ],
        );
    }

    /**
     * {@inheritDoc}
     */
    public function deleteBySectionAndRoles(string $sectionInstanceId, array $roles): void
    {
        SectionSignature::query()
            ->where('section_instance_id', $sectionInstanceId)
            ->whereIn('role', $roles)
            ->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteBySectionIdsAndRoles(array $sectionInstanceIds, array $roles): void
    {
        if (empty($sectionInstanceIds)) {
            return;
        }

        SectionSignature::query()
            ->whereIn('section_instance_id', $sectionInstanceIds)
            ->whereIn('role', $roles)
            ->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteForEditedMonitoringSection(string $sectionInstanceId, string $analystId): void
    {
        SectionSignature::query()
            ->where('section_instance_id', $sectionInstanceId)
            ->where(function ($query) use ($analystId) {
                $query->whereIn('role', [
                    SectionSignature::ROLE_REVIEW,
                    SectionSignature::ROLE_APPROVAL,
                ])->orWhere(function ($query) use ($analystId) {
                    $query->where('role', SectionSignature::ROLE_MONITORING)
                        ->where('signed_by', $analystId);
                });
            })
            ->delete();
    }
}
