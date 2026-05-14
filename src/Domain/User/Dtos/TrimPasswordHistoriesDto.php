<?php

namespace Domain\User\Dtos;

use Domain\User\Models\User;

class TrimPasswordHistoriesDto
{
    public User $user;
    public int $limit;

    public function __construct(User $user, int $limit)
    {
        $this->user = $user;
        $this->limit = $limit;
    }
}
