<?php

namespace App\Services;

use Domain\User\Dtos\GetUserDto;
use Domain\User\Interfaces\UserRepositoryInterface;
use Domain\User\Models\User;
use Illuminate\Http\Request;
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
     * @param  GetUserDto  $request  DTO containing username and plain-text password.
     * @return User                  The authenticated user model.
     *
     * @throws ValidationException  If username is not found or password is incorrect.
     */
    public function login(GetUserDto $request): User
    {
        try {
            $user = $this->repository->getUserByUsername($request);

            if (!$user) {
                throw ValidationException::withMessages([
                    'login' => ['Pengguna tidak ditemukan.'],
                ]);
            }

            $checkPassword = Hash::check($request->password, $user->password);

            if (!$checkPassword) {
                throw ValidationException::withMessages([
                    'login' => ['Username atau password salah.'],
                ]);
            }

            return $user;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Log out the current user and invalidate their session.
     *
     * Calls Auth::logout(), invalidates the session, and regenerates the CSRF token.
     *
     * @param  Request  $request  The current HTTP request.
     * @return void
     */
    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
