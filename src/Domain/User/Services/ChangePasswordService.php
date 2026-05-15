<?php

namespace Domain\User\Services;

use Domain\PasswordPolicy\Services\PasswordPolicyService;
use Domain\User\Dtos\ChangeInitialPasswordDto;
use Domain\User\Dtos\CreatePasswordHistoryDto;
use Domain\User\Dtos\GetRecentPasswordHistoriesDto;
use Domain\User\Dtos\TrimPasswordHistoriesDto;
use Domain\User\Dtos\UpdateUserPasswordDto;
use Domain\User\Interfaces\PasswordHistoryRepositoryInterface;
use Domain\User\Interfaces\UserRepositoryInterface;
use Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Handles the password change flow for authenticated users.
 *
 * Enforces:
 * - Old password verification
 * - Password history check (cannot reuse recent N passwords)
 * - Atomic update of password + history within a DB transaction
 * - Trimming of excess password history entries
 */
class ChangePasswordService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected PasswordHistoryRepositoryInterface $passwordHistoryRepository,
        protected PasswordPolicyService $passwordPolicyService
    ) {
    }

    /**
     * Change a user's password.
     *
     * Validates the old password, checks the new password against recent history,
     * then atomically updates the password, records it in history, and trims
     * excess history entries — all within a single database transaction.
     *
     * @param  User                      $user  The user whose password is being changed.
     * @param  ChangeInitialPasswordDto  $dto   DTO containing oldPassword and newPassword.
     * @return void
     *
     * @throws ValidationException  If the old password is incorrect or the new password
     *                              matches one of the recent password history entries.
     */
    public function changePassword(User $user, ChangeInitialPasswordDto $dto): void
    {
        if (!Hash::check($dto->oldPassword, $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        $historyLimit = $this->passwordPolicyService->getPasswordHistoryCount();

        DB::transaction(function () use ($user, $dto, $historyLimit): void {
            $this->validateNotUsingRecentPasswords($user, $dto->newPassword, $historyLimit);

            $updatePasswordDto = new UpdateUserPasswordDto($user, $dto->newPassword, now());

            $this->userRepository->updatePassword($updatePasswordDto);

            $this->passwordHistoryRepository->create(
                new CreatePasswordHistoryDto($user, $user->password)
            );

            $this->passwordHistoryRepository->trimExceedingByUser(
                new TrimPasswordHistoriesDto($user, $historyLimit)
            );
        });
    }

    /**
     * Validate that the new password does not match any of the user's recent passwords.
     *
     * Fetches the most recent N password history entries and checks each one.
     *
     * @param  User    $user          The user to check history for.
     * @param  string  $newPassword   The plain-text new password to validate.
     * @param  int     $historyLimit  Number of recent passwords to check against.
     * @return void
     *
     * @throws ValidationException  If the new password matches a recent history entry.
     */
    private function validateNotUsingRecentPasswords(User $user, string $newPassword, int $historyLimit): void
    {
        $recentPasswordHistories = $this->passwordHistoryRepository->getRecentByUser(
            new GetRecentPasswordHistoriesDto($user, $historyLimit)
        );

        foreach ($recentPasswordHistories as $history) {
            if (Hash::check($newPassword, $history->password)) {
                throw ValidationException::withMessages([
                    'new_password' => [
                        sprintf('Password baru tidak boleh sama dengan %d password terakhir.', $historyLimit),
                    ],
                ]);
            }
        }
    }
}
