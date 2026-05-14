<?php

namespace Domain\User\Dtos;

use Domain\User\Models\User;

class CreatePasswordHistoryDto
{
    public User $user;
    public string $password;

    public function __construct(User $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;
    }
}
