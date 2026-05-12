<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use Domain\User\Services\UserService;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        $users = $this->userService->getDataUsers();

        return view('user-management.index', compact('users'));
    }

    public function create()
    {
        return view('user-management.create');
    }

    public function store(UserStoreRequest $request)
    {
        $this->userService->createUser($request->toDTO());

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    public function getUsers()
    {
        $users = $this->userService->getDataUsers();

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Users retrieved successfully',
        ]);
    }
}
