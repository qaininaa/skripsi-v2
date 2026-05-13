<?php

namespace App\Services;

use Domain\User\Dtos\GetUserDto;
use Domain\User\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public $repository;
    public function __construct(UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function login(GetUserDto $request)
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
}