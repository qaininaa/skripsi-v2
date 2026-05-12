<?php

namespace App\Domain\User\Http\Controllers;

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
        return view('users.index', compact('users'));
    }
}