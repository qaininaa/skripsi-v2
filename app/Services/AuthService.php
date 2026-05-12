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
            $checkPassword = Hash::check($request->password, $user->password);

            if (!$user) {
                throw ValidationException::withMessages([
                    'message' => ['Akun tidak ditemukan.'],
                ]);
            }

            if (!$checkPassword) {
                throw ValidationException::withMessages([
                    'message' => ['Username atau password salah.'],
                ]);
            }

            return $user;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}