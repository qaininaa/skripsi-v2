<?php

namespace Domain\User\Services;

use Domain\User\Dtos\ChangeInitialPasswordDto;
use Domain\User\Dtos\UpdateUserPasswordDto;
use Domain\User\Interfaces\UserRepositoryInterface;
use Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangePasswordService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {
    }

    public function changePassword(User $user, ChangeInitialPasswordDto $dto): void
    {
        if (!Hash::check($dto->oldPassword, $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        $updatePasswordDto = new UpdateUserPasswordDto($user, $dto->newPassword, now());

        $this->userRepository->updatePassword($updatePasswordDto);
    }
}
