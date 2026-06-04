<?php

namespace App\Services;

use Domain\User\Dtos\GetUserDto;
use Domain\User\Interfaces\UserRepositoryInterface;
use Domain\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Handles authentication logic: login credential validation and session logout.
 */
class AuthService
{
    public $repository;

    public function __construct(UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Validate login credentials and return the authenticated user.
     *
     * Looks up the user by username, then verifies the password hash.
     * Throws a ValidationException if the user is not found or the password does not match.
     *
     *
     * @throws ValidationException
     */
    public function login(GetUserDto $request): User
    {
        try {
            $user = $this->repository->getUserByUsername($request);

            if (! $user) {
                throw ValidationException::withMessages([
                    'login' => ['Pengguna tidak ditemukan.'],
                ]);
            }

            $checkPassword = Hash::check($request->password, $user->password);

            if (! $checkPassword) {
                throw ValidationException::withMessages([
                    'login' => ['Username atau password salah.'],
                ]);
            }

            Auth::login($user);

            return $user;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Log out the current user from the web guard.
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();
    }
}
