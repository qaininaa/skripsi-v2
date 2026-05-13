<?php

namespace Domain\User\Services;

use Domain\User\Dtos\CreateUserDto;
use Domain\User\Dtos\GetUsersFilterDto;
use Domain\User\Dtos\UpdateUserDto;
use \Domain\User\Interfaces\UserRepositoryInterface;
use Domain\User\Models\User;

class UserService
{
    public $repository;
    public function __construct(UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getDataUsers(GetUsersFilterDto $request)
    {
        try {
            $users = $this->repository->getUsers($request);
            return $users;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function createUser(CreateUserDto $request)
    {
        try {
            $user = $this->repository->createUser($request);
            return $user;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updateUser(User $user, UpdateUserDto $request): void
    {
        try {
            $this->repository->updateUser($user, $request);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function deleteUser(User $user): void
    {
        try {
            $this->repository->deleteUser($user);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
