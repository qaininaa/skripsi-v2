<?php

namespace Domain\User\Interfaces;

use Domain\User\Dtos\CreateUserDto;
use Domain\User\Dtos\GetUserDto;
use Domain\User\Dtos\GetUsersFilterDto;
use Domain\User\Dtos\UpdateUserDto;
use Domain\User\Dtos\UpdateUserPasswordDto;
use Domain\User\Models\User;

interface UserRepositoryInterface
{
    public function getUsers(GetUsersFilterDto $data);
    public function createUser(CreateUserDto $data);
    public function getUserByUsername(GetUserDto $data);
    public function updateUser(User $user, UpdateUserDto $data): void;
    public function deleteUser(User $user): void;
    public function updatePassword(UpdateUserPasswordDto $data): void;
}
