<?php

namespace Domain\Report\Interfaces;

use Domain\Report\Models\SectionSignature;

/**
 * Contract for SectionSignature data access.
 */
interface SectionSignatureRepositoryInterface
{
    /**
     * Sign a section instance with the given role. Idempotent — returns the
     * existing signature if one already exists for that role.
     */
    public function sign(string $sectionInstanceId, string $role, string $userId, \DateTimeInterface $when): SectionSignature;
}
