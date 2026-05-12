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
}