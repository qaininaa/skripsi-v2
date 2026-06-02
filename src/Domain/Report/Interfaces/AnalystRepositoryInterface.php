<?php

namespace Domain\Report\Interfaces;

use Domain\Report\Models\Analyst;

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
}
