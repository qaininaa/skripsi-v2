<?php

namespace Domain\User\Repositories;

use Domain\User\Dtos\CreatePasswordHistoryDto;
use Domain\User\Dtos\GetRecentPasswordHistoriesDto;
use Domain\User\Dtos\TrimPasswordHistoriesDto;
use Domain\User\Interfaces\PasswordHistoryRepositoryInterface;
use Domain\User\Models\PasswordHistory;

/**
 * Eloquent implementation of PasswordHistoryRepositoryInterface.
 *
 * Manages the password_histories table, which stores hashed previous passwords
 * to enforce the password reuse policy.
 */
class PasswordHistoryRepository implements PasswordHistoryRepositoryInterface
{
    /**
     * Retrieve the most recent N password history entries for a user.
     *
     * Results are ordered newest first. A limit of 0 returns an empty collection.
     *
     * @param  GetRecentPasswordHistoriesDto  $data  DTO with user and limit.
     * @return \Illuminate\Database\Eloquent\Collection<int, PasswordHistory>
     */
    public function getRecentByUser(GetRecentPasswordHistoriesDto $data)
    {
        return PasswordHistory::query()
            ->where('user_id', $data->user->id)
            ->latest()
            ->limit(max(0, $data->limit))
            ->get();
    }

    /**
     * Record a new password history entry for a user.
     *
     * Stores the already-hashed password value — never plain text.
     *
     * @param  CreatePasswordHistoryDto  $data  DTO with user and hashed password.
     * @return void
     */
    public function create(CreatePasswordHistoryDto $data): void
    {
        $history = new PasswordHistory();
        $history->user_id = $data->user->id;
        $history->password = $data->password;
        $history->save();
    }

    /**
     * Remove password history entries that exceed the configured limit for a user.
     *
     * Keeps the most recent N entries (where N = $data->limit) and deletes the rest.
     * If limit is 0, all history entries for the user are deleted.
     * Does nothing if there are no entries to trim.
     *
     * @param  TrimPasswordHistoriesDto  $data  DTO with user and limit.
     * @return void
     */
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
