<?php

namespace Domain\User\Dtos;

class ChangeInitialPasswordDto
{
    public string $oldPassword;
    public string $newPassword;

    public function __construct(array $data)
    {
        $this->oldPassword = $data['old_password'];
        $this->newPassword = $data['new_password'];
    }
}
