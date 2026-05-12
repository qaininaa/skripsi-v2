<?php

namespace Domain\User\Repositories;

use App\Domain\User\Dtos\CreateUserDto;
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
        $user->save();
        return $user;
    }
}