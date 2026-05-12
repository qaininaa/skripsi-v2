<?php

namespace Domain\User\Interfaces;

use Domain\User\Dtos\CreateUserDto;
use Domain\User\Dtos\GetUserDto;

interface UserRepositoryInterface
{
    public function getUsers();
    public function createUser(CreateUserDto $data);
    public function getUserByUsername(GetUserDto $data);
}