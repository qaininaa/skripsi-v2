<?php

namespace Domain\User\Interfaces;

use Domain\User\Dtos\CreatePasswordHistoryDto;
use Domain\User\Dtos\GetRecentPasswordHistoriesDto;
use Domain\User\Dtos\TrimPasswordHistoriesDto;
use Domain\User\Models\PasswordHistory;

/**
 * Contract for PasswordHistory data access.
 *
 * Manages the password history table used to enforce the password reuse policy.
 */
interface PasswordHistoryRepositoryInterface
{
    /**
     * Retrieve the most recent N password history entries for a user.
     *
     * Results must be ordered newest first.
     *
     * @param  GetRecentPasswordHistoriesDto  $data 
     * @return \Illuminate\Database\Eloquent\Collection<int, PasswordHistory>
     */
    public function getRecentByUser(GetRecentPasswordHistoriesDto $data);

    /**
     * Record a new password history entry for a user.
     *
     * The password value stored must already be hashed.
     *
     * @param  CreatePasswordHistoryDto  $data  
     * @return void
     */
    public function create(CreatePasswordHistoryDto $data): void;

    /**
     * Remove password history entries that exceed the configured limit for a user.
     *
     * Keeps the most recent N entries and deletes the rest.
     * If limit is 0, all history entries for the user are deleted.
     *
     * @param  TrimPasswordHistoriesDto  $data
     * @return void
     */
    public function trimExceedingByUser(TrimPasswordHistoriesDto $data): void;
}
