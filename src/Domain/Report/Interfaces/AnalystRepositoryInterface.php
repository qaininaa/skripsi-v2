<?php

namespace Domain\Report\Interfaces;

use Domain\Report\Models\Analyst;
use Domain\User\Models\User;
use Illuminate\Support\Collection;

/**
 * Contract for the analyst pivot table data access.
 */
interface AnalystRepositoryInterface
{
    /**
     * Attach an analyst to a report under a specific role.
     * Idempotent: returns the existing pivot row when already attached.
     */
    public function attach(string $reportId, string $userId, string $type): Analyst;

    /**
     * Check whether a user is attached to a report under a role.
     */
    public function existsForReport(string $reportId, string $userId, string $type): bool;

    /**
     * Return analysts for a report, filtered by role type.
     *
     * @param  array<int, string>  $types
     * @return Collection<int, Analyst>
     */
    public function getForReportByTypes(string $reportId, array $types): Collection;

    /**
     * Return distinct users attached to a report through the given analyst roles.
     *
     * @param  array<int, string>  $types
     * @return Collection<int, User>
     */
    public function getUsersForReportByTypes(string $reportId, array $types): Collection;
}
