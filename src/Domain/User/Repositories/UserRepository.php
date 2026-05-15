<?php

namespace Domain\User\Repositories;

use Domain\User\Dtos\CreateUserDto;
use Domain\User\Dtos\GetUserDto;
use Domain\User\Dtos\GetUsersFilterDto;
use Domain\User\Dtos\UpdateUserDto;
use Domain\User\Dtos\UpdateUserPasswordDto;
use Domain\User\Models\User;
use Domain\User\Interfaces\UserRepositoryInterface;

/**
 * Eloquent implementation of UserRepositoryInterface.
 *
 * All database access for the User domain goes through this class.
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * Retrieve a paginated list of users with optional search and role filter.
     *
     * Results are ordered by creation date descending (newest first).
     *
     * @param  GetUsersFilterDto  $data  Filter parameters (search, role).
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
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

    /**
     * Persist a new user to the database.
     *
     * Password is hashed with bcrypt. password_changed_at is set to null
     * to force a password change on first login.
     *
     * @param  CreateUserDto  $data  Data for the new user.
     * @return User                  The newly created user model.
     */
    public function createUser(CreateUserDto $data): User
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

    /**
     * Find a user by their username.
     *
     * Returns null if no user with the given username exists.
     *
     * @param  GetUserDto  $data  DTO containing the username to look up.
     * @return User|null          The matching user, or null if not found.
     */
    public function getUserByUsername(GetUserDto $data): ?User
    {
        return User::where('username', $data->username)->first();
    }

    /**
     * Update an existing user's profile data.
     *
     * If the DTO indicates a password reset (hasPasswordReset() === true),
     * the password is updated and password_changed_at is reset to null.
     *
     * @param  User           $user  The user model to update.
     * @param  UpdateUserDto  $data  New values to apply.
     * @return void
     */
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

    /**
     * Delete a user from the database.
     *
     * @param  User  $user  The user model to delete.
     * @return void
     */
    public function deleteUser(User $user): void
    {
        $user->delete();
    }

    /**
     * Update a user's password and record the timestamp of the change.
     *
     * Called exclusively by ChangePasswordService within a DB transaction.
     *
     * @param  UpdateUserPasswordDto  $data  DTO containing the user, new hashed password, and changedAt timestamp.
     * @return void
     */
    public function updatePassword(UpdateUserPasswordDto $data): void
    {
        $data->user->password = $data->newPassword;
        $data->user->password_changed_at = $data->changedAt;
        $data->user->save();
    }
}
