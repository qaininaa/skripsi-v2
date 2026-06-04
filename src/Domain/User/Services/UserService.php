<?php

namespace Domain\User\Services;

use Domain\User\Dtos\CreateUserDto;
use Domain\User\Dtos\GetUsersFilterDto;
use Domain\User\Dtos\UpdateUserDto;
use Domain\User\Interfaces\UserRepositoryInterface;
use Domain\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * Handles business logic for user management (CRUD operations).
 *
 * Delegates all data access to UserRepositoryInterface.
 * Password-related operations are handled separately by ChangePasswordService.
 */
class UserService
{
    protected UserRepositoryInterface $repository;

    public function __construct(UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Retrieve a paginated, filtered list of users.
     *
     * @param  GetUsersFilterDto  $dto  Filter parameters (search, role).
     * @return LengthAwarePaginator
     */
    public function getDataUsers(GetUsersFilterDto $dto)
    {
        try {
            return $this->repository->getUsers($dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Create a new user.
     *
     * Password hashing is handled inside the repository.
     * The new user's password_changed_at is set to null, requiring a password change on first login.
     *
     * @param  CreateUserDto  $dto  Data for the new user.
     * @return User The newly created user model.
     */
    public function createUser(CreateUserDto $dto): User
    {
        try {
            return $this->repository->createUser($dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Update an existing user's profile data.
     *
     * If the DTO includes a password reset, the password is updated and
     * password_changed_at is reset to null.
     *
     * @param  User  $user  The user model to update.
     * @param  UpdateUserDto  $dto  New data to apply.
     */
    public function updateUser(User $user, UpdateUserDto $dto): void
    {
        try {
            $this->repository->updateUser($user, $dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Find a user by ID.
     *
     * @throws \RuntimeException
     */
    public function findUserById(string $userId): User
    {
        $user = $this->repository->findById($userId);
        if ($user === null) {
            throw (new ModelNotFoundException)->setModel(User::class, [$userId]);
        }

        return $user;
    }

    public function updateUserById(string $userId, UpdateUserDto $dto): void
    {
        $this->updateUser($this->findUserById($userId), $dto);
    }

    /**
     * Delete a user.
     *
     * @param  User  $user  The user model to delete.
     */
    public function deleteUser(User $user): void
    {
        try {
            $this->repository->deleteUser($user);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function deleteUserById(string $userId): void
    {
        $this->deleteUser($this->findUserById($userId));
    }

    /**
     * List supervisor users for report handoff selection.
     */
    public function listSupervisors(): Collection
    {
        return $this->repository->listByRoles(['supervisor']);
    }
}
