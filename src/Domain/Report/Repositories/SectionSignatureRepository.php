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
                'role'                => $role,
            ],
            [
                'signed_by' => $userId,
                'signed_at' => $when,
            ],
        );
    }
}
