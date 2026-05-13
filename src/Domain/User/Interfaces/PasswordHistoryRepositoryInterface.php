<?php

namespace Domain\User\Interfaces;

use Domain\User\Dtos\CreatePasswordHistoryDto;
use Domain\User\Dtos\GetRecentPasswordHistoriesDto;
use Domain\User\Dtos\TrimPasswordHistoriesDto;

interface PasswordHistoryRepositoryInterface
{
    public function getRecentByUser(GetRecentPasswordHistoriesDto $data);
    public function create(CreatePasswordHistoryDto $data): void;
    public function trimExceedingByUser(TrimPasswordHistoriesDto $data): void;
}
