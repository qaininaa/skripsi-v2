<?php

namespace Domain\User\Services;

use App\Domain\User\Dtos\CreateUserDto;
use \Domain\User\Interfaces\UserRepositoryInterface;

class UserService
{
    public $repository;
    public function __construct(UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getDataUsers()
    {
        try {
            $users = $this->repository->getUsers();
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
}