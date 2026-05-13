<?php

namespace Domain\User\Repositories;

use Domain\User\Dtos\CreatePasswordHistoryDto;
use Domain\User\Dtos\GetRecentPasswordHistoriesDto;
use Domain\User\Dtos\TrimPasswordHistoriesDto;
use Domain\User\Interfaces\PasswordHistoryRepositoryInterface;
use Domain\User\Models\PasswordHistory;

class PasswordHistoryRepository implements PasswordHistoryRepositoryInterface
{
    public function getRecentByUser(GetRecentPasswordHistoriesDto $data)
    {
        return PasswordHistory::query()
            ->where('user_id', $data->user->id)
            ->latest()
            ->limit(max(0, $data->limit))
            ->get();
    }

    public function create(CreatePasswordHistoryDto $data): void
    {
        $history = new PasswordHistory();
        $history->user_id = $data->user->id;
        $history->password = $data->password;
        $history->save();
    }

    public function trimExceedingByUser(TrimPasswordHistoriesDto $data): void
    {
        $limit = max(0, $data->limit);

        if ($limit === 0) {
            PasswordHistory::query()
                ->where('user_id', $data->user->id)
                ->delete();

            return;
        }

        $idsToKeep = PasswordHistory::query()
            ->where('user_id', $data->user->id)
            ->latest()
            ->limit($limit)
            ->pluck('id');

        if ($idsToKeep->isEmpty()) {
            return;
        }

        PasswordHistory::query()
            ->where('user_id', $data->user->id)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }
}
