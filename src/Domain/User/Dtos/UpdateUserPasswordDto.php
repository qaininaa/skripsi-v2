<?php

namespace Domain\User\Dtos;

use Domain\User\Models\User;
use Illuminate\Support\Carbon;

class UpdateUserPasswordDto
{
    public User $user;
    public string $newPassword;
    public Carbon $changedAt;

    public function __construct(User $user, string $newPassword, Carbon $changedAt)
    {
        $this->user = $user;
        $this->newPassword = $newPassword;
        $this->changedAt = $changedAt;
    }
}
