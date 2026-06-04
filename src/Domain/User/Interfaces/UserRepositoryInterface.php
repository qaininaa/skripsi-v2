<?php

namespace Domain\User\Interfaces;

use Domain\User\Dtos\CreateUserDto;
use Domain\User\Dtos\GetUserDto;
use Domain\User\Dtos\GetUsersFilterDto;
use Domain\User\Dtos\UpdateUserDto;
use Domain\User\Dtos\UpdateUserPasswordDto;
use Domain\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Contract for User data access.
 */
interface UserRepositoryInterface
{
    /**
     * Retrieve a paginated, filtered list of users.
     *
     * @param  GetUsersFilterDto  $data  Filter parameters (search, role).
     * @return LengthAwarePaginator
     */
    public function getUsers(GetUsersFilterDto $data);

    /**
     * Persist a new user to the database.
     *
     * Password must be hashed by the implementation. password_changed_at
     * must be set to null to force a password change on first login.
     */
    public function createUser(CreateUserDto $data): User;

    /**
     * Find a user by their username.
     *
     * @param  GetUserDto  $data  DTO containing the username to look up.
     * @return User|null The matching user, or null if not found.
     */
    public function getUserByUsername(GetUserDto $data): ?User;

    /**
     * Update an existing user's profile data.
     *
     * If the DTO includes a password reset, the password must be updated
     * and password_changed_at must be reset to null.
     */
    public function updateUser(User $user, UpdateUserDto $data): void;

    /**
     * Delete a user from the database.
     */
    public function deleteUser(User $user): void;

    /**
     * Update a user's password and record the timestamp of the change.
     *
     * Called exclusively by ChangePasswordService within a DB transaction.
     */
    public function updatePassword(UpdateUserPasswordDto $data): void;

    /**
     * Find the first user with the given role, ordered by creation date.
     *
     * Used by the approval pipeline to assign supervisor / manager rows
     * when there's no explicit per-report assignment.
     */
    public function findFirstByRole(string $role): ?User;

    /**
     * Find a user by id and role.
     */
    public function findByIdAndRole(string $id, string $role): ?User;

    /**
     * Find a user by id.
     */
    public function findById(string $id): ?User;

    /**
     * List users with one of the given roles. Used by the inbox dropdowns.
     *
     * @param  array<int, string>  $roles
     * @return Collection<int, User>
     */
    public function listByRoles(array $roles): Collection;
}
