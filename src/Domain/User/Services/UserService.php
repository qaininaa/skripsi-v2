<?php

namespace Domain\User\Services;

use Domain\User\Dtos\CreateUserDto;
use Domain\User\Dtos\GetUsersFilterDto;
use Domain\User\Dtos\UpdateUserDto;
use Domain\User\Interfaces\UserRepositoryInterface;
use Domain\User\Models\User;

class UserService
{
    protected UserRepositoryInterface $repository;

    public function __construct(UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getDataUsers(GetUsersFilterDto $dto)
    {
        try {
            return $this->repository->getUsers($dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function createUser(CreateUserDto $dto): User
    {
        try {
            return $this->repository->createUser($dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updateUser(User $user, UpdateUserDto $dto): void
    {
        try {
            $this->repository->updateUser($user, $dto);
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
