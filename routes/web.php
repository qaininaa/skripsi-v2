<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::get('/users', [UserController::class, 'index'])->middleware('role:super')->name('users.index');
Route::get('/users/create', [UserController::class, 'create'])->middleware('role:super')->name('users.create');
Route::post('/users/store', [UserController::class, 'store'])->middleware('role:super')->name('users.store');
