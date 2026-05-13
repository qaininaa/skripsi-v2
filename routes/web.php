<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('/welcome', function () {
    return view('welcome');
})->middleware('auth')->name('welcome');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/users', [UserController::class, 'index'])->middleware(['auth', 'role:super'])->name('users.index');
Route::get('/users/create', [UserController::class, 'create'])->middleware(['auth', 'role:super'])->name('users.create');
Route::post('/users/store', [UserController::class, 'store'])->middleware(['auth', 'role:super'])->name('users.store');
