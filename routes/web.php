<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\InitialPasswordController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\User\PasswordSettingController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/password/change-initial', [InitialPasswordController::class, 'edit'])->name('password.change.form');
    Route::put('/password/change-initial', [InitialPasswordController::class, 'update'])->name('password.change.update');
});

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:super')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users/store', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/settings/password', [PasswordSettingController::class, 'index'])->name('settings.index');
        Route::put('/settings/password', [PasswordSettingController::class, 'update'])->name('settings.update');
    });
});
