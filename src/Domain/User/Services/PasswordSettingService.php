<?php

namespace Domain\User\Services;

use Domain\User\Dtos\UpdatePasswordSettingDto;
use Domain\User\Interfaces\PasswordSettingRepositoryInterface;

class PasswordSettingService
{
    public function __construct(
        protected PasswordSettingRepositoryInterface $repository
    ) {
    }

    public function getSettings(): array
    {
        return [
            'password_expiration_days' => (int) $this->repository->getValue('password_expiration_days', 90),
            'password_history_count' => (int) $this->repository->getValue('password_history_count', 3),
        ];
    }

    public function updateSettings(UpdatePasswordSettingDto $dto): void
    {
        $this->repository->setValue('password_expiration_days', (string) $dto->passwordExpirationDays);
        $this->repository->setValue('password_history_count', (string) $dto->passwordHistoryCount);
    }
}
