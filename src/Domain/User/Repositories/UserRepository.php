<?php

namespace Domain\User\Repositories;

use Domain\User\Dtos\CreateUserDto;
use Domain\User\Dtos\GetUserDto;
use Domain\User\Dtos\UpdateUserPasswordDto;
use Domain\User\Models\User;
use Domain\User\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{

    public function getUsers()
    {
        $users = User::all();
        return $users;
    }

    public function createUser(CreateUserDto $data)
    {
        $user = new User();
        $user->name = $data->name;
        $user->username = $data->username;
        $user->password = bcrypt($data->password);
        $user->role = $data->role;
        $user->password_changed_at = null;
        $user->save();
        return $user;
    }

    public function getUserByUsername(GetUserDto $data)
    {
        $user = User::where('username', $data->username)->first();
        return $user;
    }

    public function updatePassword(UpdateUserPasswordDto $data): void
    {
        $data->user->password = $data->newPassword;
        $data->user->password_changed_at = $data->changedAt;
        $data->user->save();
    }
}
