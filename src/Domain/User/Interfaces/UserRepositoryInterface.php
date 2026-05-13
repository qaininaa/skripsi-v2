<?php

namespace Domain\User\Interfaces;

use Domain\User\Dtos\CreateUserDto;
use Domain\User\Dtos\GetUserDto;
use Domain\User\Dtos\UpdateUserPasswordDto;

interface UserRepositoryInterface
{
    public function getUsers();
    public function createUser(CreateUserDto $data);
    public function getUserByUsername(GetUserDto $data);
    public function updatePassword(UpdateUserPasswordDto $data): void;
}
