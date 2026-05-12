<?php

namespace Domain\User\Repositories;

use Domain\User\Models\User;
use Domain\User\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{

    public function getUsers()
    {
        $users = User::all();
        return $users;
    }

    public function getUser($id)
    {
        $user = User::find($id);
        return $user;
    }

    public function createUser($data)
    {
        $user = User::create($data);
        return $user;
    }

    public function updateUser($id, $data)
    {
        $user = User::find($id);
        if ($user) {
            $user->update($data);
            return $user;
        }
        return null;
    }
}