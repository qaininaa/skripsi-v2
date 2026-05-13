<?php

namespace Domain\User\Repositories;

use Domain\User\Dtos\CreateUserDto;
use Domain\User\Dtos\GetUserDto;
use Domain\User\Dtos\GetUsersFilterDto;
use Domain\User\Dtos\UpdateUserDto;
use Domain\User\Dtos\UpdateUserPasswordDto;
use Domain\User\Models\User;
use Domain\User\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{

    public function getUsers(GetUsersFilterDto $data)
    {
        return User::query()
            ->when($data->search !== null, function ($query) use ($data) {
                $query->where(function ($subQuery) use ($data) {
                    $subQuery->where('name', 'like', '%' . $data->search . '%')
                        ->orWhere('username', 'like', '%' . $data->search . '%');
                });
            })
            ->when($data->role !== null, function ($query) use ($data) {
                $query->where('role', $data->role);
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();
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

    public function updateUser(User $user, UpdateUserDto $data): void
    {
        $user->name = $data->name;
        $user->username = $data->username;
        $user->role = $data->role;
        if ($data->hasPasswordReset() && $data->password !== null) {
            $user->password = $data->password;
            $user->password_changed_at = null;
        }

        $user->save();
    }

    public function deleteUser(User $user): void
    {
        $user->delete();
    }

    public function updatePassword(UpdateUserPasswordDto $data): void
    {
        $data->user->password = $data->newPassword;
        $data->user->password_changed_at = $data->changedAt;
        $data->user->save();
    }
}
