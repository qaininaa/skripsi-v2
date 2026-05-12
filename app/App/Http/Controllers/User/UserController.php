<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Domain\User\Services\UserService;

class UserController extends Controller
{
    public $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        $users = $this->userService->getDataUsers();
        return view('user.index', compact('users'));
    }

    public function getUsers()
    {
        $users = $this->userService->getDataUsers();
        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Users retrieved successfully'
        ]);
    }
}