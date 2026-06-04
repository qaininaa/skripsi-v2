<?php

namespace Domain\Report\Repositories;

use Domain\Report\Interfaces\AnalystRepositoryInterface;
use Domain\Report\Models\Analyst;
use Illuminate\Support\Collection;

class AnalystRepository implements AnalystRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function attach(string $reportId, string $userId, string $type): Analyst
    {
        return Analyst::firstOrCreate([
            'report_id' => $reportId,
            'user_id' => $userId,
            'type' => $type,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function existsForReport(string $reportId, string $userId, string $type): bool
    {
        return Analyst::query()
            ->where('report_id', $reportId)
            ->where('user_id', $userId)
            ->where('type', $type)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getForReportByTypes(string $reportId, array $types): Collection
    {
        return Analyst::query()
            ->where('report_id', $reportId)
            ->whereIn('type', $types)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getUsersForReportByTypes(string $reportId, array $types): Collection
    {
        return Analyst::query()
            ->with('user')
            ->where('report_id', $reportId)
            ->whereIn('type', $types)
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();
    }
}
