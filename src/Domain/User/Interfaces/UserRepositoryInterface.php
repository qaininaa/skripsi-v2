<?php

namespace Domain\User\Interfaces;

use App\Domain\User\Dtos\CreateUserDto;

interface UserRepositoryInterface
{
    public function getUsers();
    public function createUser(CreateUserDto $data);
}