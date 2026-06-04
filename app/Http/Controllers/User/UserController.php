<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserIndexRequest;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use Domain\User\Dtos\GetUsersFilterDto;
use Domain\User\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(UserIndexRequest $request): View
    {
        $users = $this->userService->getDataUsers($request->toDTO());

        return view('user-management.index', compact('users'));
    }

    public function create(): View
    {
        return view('user-management.create');
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $this->userService->createUser($request->toDTO());

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    public function edit(string $user): View
    {
        $user = $this->userService->findUserById($user);

        return view('user-management.edit', compact('user'));
    }

    public function update(UserUpdateRequest $request, string $user): RedirectResponse
    {
        $this->userService->updateUserById($user, $request->toDTO());

        return redirect()->route('users.index')->with('success', 'User updated successfully');
    }

    public function destroy(string $user): RedirectResponse
    {
        $this->userService->deleteUserById($user);

        return redirect()->route('users.index')->with('success', 'User deleted successfully');
    }

    public function getUsers()
    {
        $users = $this->userService->getDataUsers(new GetUsersFilterDto);

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Users retrieved successfully',
        ]);
    }
}
