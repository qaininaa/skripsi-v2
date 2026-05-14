<?php

namespace Domain\PasswordPolicy\Dtos;

class UpdatePasswordSettingDto
{
    public int $passwordExpirationDays;
    public int $passwordHistoryCount;

    public function __construct(array $data)
    {
        $this->passwordExpirationDays = (int) $data['password_expiration_days'];
        $this->passwordHistoryCount = (int) $data['password_history_count'];
    }
}
