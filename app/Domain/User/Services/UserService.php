<?php

namespace Domain\User\Services;

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
}