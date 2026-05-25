<?php

namespace Domain\Report\Interfaces;

use Domain\Report\Models\SectionSignature;

/**
 * Contract for SectionSignature data access.
 */
interface SectionSignatureRepositoryInterface
{
    /**
     * Sign a section instance with the given role.
     * Idempotent per signer: returns the existing signature if this user
     * already signed the same section and role.
     */
    public function sign(string $sectionInstanceId, string $role, string $userId, \DateTimeInterface $when): SectionSignature;
}
