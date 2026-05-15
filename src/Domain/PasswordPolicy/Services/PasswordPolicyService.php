<?php

namespace Domain\PasswordPolicy\Services;

use Domain\User\Dtos\CheckPasswordExpirationDto;
use Domain\PasswordPolicy\Dtos\UpdatePasswordPolicyDto;
use Domain\PasswordPolicy\Interfaces\PasswordPolicyRepositoryInterface;
use InvalidArgumentException;

/**
 * Handles business logic for password policy settings.
 *
 * Manages retrieval and update of password_expiration_days and password_history_count,
 * and provides password expiration checking logic.
 */
class PasswordPolicyService
{
    public function __construct(
        protected PasswordPolicyRepositoryInterface $repository
    ) {
    }

    /**
     * Retrieve all password policy settings as a key-value array.
     *
     * @return array{password_expiration_days: int, password_history_count: int}
     */
    public function getSettings(): array
    {
        return [
            'password_expiration_days' => $this->getPasswordExpirationDays(),
            'password_history_count' => $this->getPasswordHistoryCount(),
        ];
    }

    /**
     * Get the configured password expiration period in days.
     *
     * Falls back to 90 days if not configured. Minimum value is 1.
     *
     * @return int  Number of days before a password expires.
     */
    public function getPasswordExpirationDays(): int
    {
        return max(1, (int) $this->repository->getValue('password_expiration_days', 90));
    }

    /**
     * Get the configured password history count.
     *
     * Determines how many previous passwords a user cannot reuse.
     * Falls back to 3 if not configured. Minimum value is 1.
     *
     * @return int  Number of recent passwords to retain in history.
     */
    public function getPasswordHistoryCount(): int
    {
        return max(1, (int) $this->repository->getValue('password_history_count', 3));
    }

    /**
     * Check whether a user's password has expired.
     *
     * Returns false if passwordChangedAt is null (e.g. new user who has never changed password).
     * Compares dates at day granularity (time component is ignored).
     *
     * @param  CheckPasswordExpirationDto  $dto  DTO with passwordChangedAt and optional checkedAt.
     * @return bool                               True if the password has expired, false otherwise.
     *
     * @throws InvalidArgumentException  If checkedAt is earlier than passwordChangedAt.
     */
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

    /**
     * Update password policy settings.
     *
     * Validates that expiration days is between 1–3650 and history count is between 1–50
     * before persisting.
     *
     * @param  UpdatePasswordPolicyDto  $dto  New policy values.
     * @return void
     *
     * @throws InvalidArgumentException  If any value is out of the allowed range.
     */
    public function updateSettings(UpdatePasswordPolicyDto $dto): void
    {
        $this->validateUpdateSettingsDto($dto);

        $this->repository->setValue('password_expiration_days', (string) $dto->passwordExpirationDays);
        $this->repository->setValue('password_history_count', (string) $dto->passwordHistoryCount);
    }

    /**
     * Validate that checkedAt is not earlier than passwordChangedAt.
     *
     * @param  CheckPasswordExpirationDto  $dto
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function validateExpirationCheckDto(CheckPasswordExpirationDto $dto): void
    {
        if ($dto->checkedAt !== null && $dto->passwordChangedAt !== null && $dto->checkedAt->lessThan($dto->passwordChangedAt)) {
            throw new InvalidArgumentException('Tanggal pengecekan tidak boleh lebih kecil dari tanggal perubahan password.');
        }
    }

    /**
     * Validate that the update DTO values are within allowed ranges.
     *
     * @param  UpdatePasswordPolicyDto  $dto
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function validateUpdateSettingsDto(UpdatePasswordPolicyDto $dto): void
    {
        if ($dto->passwordExpirationDays < 1 || $dto->passwordExpirationDays > 3650) {
            throw new InvalidArgumentException('Password expiration days harus di antara 1 sampai 3650.');
        }

        if ($dto->passwordHistoryCount < 1 || $dto->passwordHistoryCount > 50) {
            throw new InvalidArgumentException('Password history count harus di antara 1 sampai 50.');
        }
    }
}
