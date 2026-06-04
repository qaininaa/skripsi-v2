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

    /**
     * Sign a section once per role, regardless of signer.
     */
    public function signRole(string $sectionInstanceId, string $role, string $userId, \DateTimeInterface $when): SectionSignature;

    /**
     * Delete signatures for one section and a role list.
     *
     * @param  array<int, string>  $roles
     */
    public function deleteBySectionAndRoles(string $sectionInstanceId, array $roles): void;

    /**
     * Delete signatures for many sections and a role list.
     *
     * @param  array<int, string>  $sectionInstanceIds
     * @param  array<int, string>  $roles
     */
    public function deleteBySectionIdsAndRoles(array $sectionInstanceIds, array $roles): void;

    /**
     * Delete this analyst's monitoring signature plus downstream signatures.
     */
    public function deleteForEditedMonitoringSection(string $sectionInstanceId, string $analystId): void;
}
