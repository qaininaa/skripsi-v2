<?php

namespace Domain\Report\Repositories;

use Domain\Report\Interfaces\AnalystRepositoryInterface;
use Domain\Report\Models\Analyst;

class AnalystRepository implements AnalystRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function attach(string $reportId, string $userId, string $type): Analyst
    {
        return Analyst::firstOrCreate([
            'report_id' => $reportId,
            'user_id'   => $userId,
            'type'      => $type,
        ]);
    }
}
