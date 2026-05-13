<?php

namespace Domain\User\Services;

use Domain\User\Dtos\CheckPasswordExpirationDto;
use Domain\User\Dtos\UpdatePasswordSettingDto;
use Domain\User\Interfaces\PasswordSettingRepositoryInterface;
use InvalidArgumentException;

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

    public function getPasswordExpirationDays(): int
    {
        return max(1, (int) $this->repository->getValue('password_expiration_days', 90));
    }

    public function isPasswordExpired(CheckPasswordExpirationDto $dto): bool
    {
        if ($dto->passwordChangedAt === null) {
            return false;
        }

        $this->validateExpirationCheckDto($dto);

        $currentDate = ($dto->checkedAt ?? now())->copy()->startOfDay();
        $passwordChangedDate = $dto->passwordChangedAt->copy()->startOfDay();
        $expiredDate = $passwordChangedDate->addDays($this->getPasswordExpirationDays());

        return $currentDate->greaterThanOrEqualTo($expiredDate);
    }

    public function updateSettings(UpdatePasswordSettingDto $dto): void
    {
        $this->validateUpdateSettingsDto($dto);

        $this->repository->setValue('password_expiration_days', (string) $dto->passwordExpirationDays);
        $this->repository->setValue('password_history_count', (string) $dto->passwordHistoryCount);
    }

    private function validateExpirationCheckDto(CheckPasswordExpirationDto $dto): void
    {
        if ($dto->checkedAt !== null && $dto->passwordChangedAt !== null && $dto->checkedAt->lessThan($dto->passwordChangedAt)) {
            throw new InvalidArgumentException('Tanggal pengecekan tidak boleh lebih kecil dari tanggal perubahan password.');
        }
    }

    private function validateUpdateSettingsDto(UpdatePasswordSettingDto $dto): void
    {
        if ($dto->passwordExpirationDays < 1 || $dto->passwordExpirationDays > 3650) {
            throw new InvalidArgumentException('Password expiration days harus di antara 1 sampai 3650.');
        }

        if ($dto->passwordHistoryCount < 1 || $dto->passwordHistoryCount > 50) {
            throw new InvalidArgumentException('Password history count harus di antara 1 sampai 50.');
        }
    }
}
