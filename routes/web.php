<?php

use App\Http\Controllers\Analyst\AnalystReportController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\PasswordPolicy\PasswordPolicyController;
use App\Http\Controllers\Location\LocationController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\ReportTemplate\ReportTemplateController;
use App\Http\Controllers\ReportTemplate\SectionController;
use App\Http\Controllers\Room\RoomController;
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
    Route::get('/password/change-initial', [ChangePasswordController::class, 'edit'])->name('password.change.form');
    Route::put('/password/change-initial', [ChangePasswordController::class, 'update'])->name('password.change.update');
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
        Route::get('/settings/password', [PasswordPolicyController::class, 'index'])->name('settings.index');
        Route::put('/settings/password', [PasswordPolicyController::class, 'update'])->name('settings.update');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
        Route::get('/rooms/create', [RoomController::class, 'create'])->name('rooms.create');
        Route::post('/rooms/store', [RoomController::class, 'store'])->name('rooms.store');
        Route::get('/rooms/{room}/edit', [RoomController::class, 'edit'])->name('rooms.edit');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
        Route::get('/locations', [LocationController::class, 'index'])->name('location.index');
        Route::get('/locations/create', [LocationController::class, 'create'])->name('location.create');
        Route::post('/locations/store', [LocationController::class, 'store'])->name('location.store');
        Route::get('/locations/{location}/edit', [LocationController::class, 'edit'])->name('location.edit');
        Route::put('/locations/{location}', [LocationController::class, 'update'])->name('location.update');
        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('location.destroy');
        Route::get('/report-templates', [ReportTemplateController::class, 'index'])->name('report-templates.index');
        Route::get('/report-templates/create', [ReportTemplateController::class, 'create'])->name('report-templates.create');
        Route::post('/report-templates/store', [ReportTemplateController::class, 'store'])->name('report-templates.store');
        Route::get('/report-templates/{reportTemplate}', [SectionController::class, 'show'])->name('report-templates.show');
        Route::get('/report-templates/{reportTemplate}/edit', [ReportTemplateController::class, 'edit'])->name('report-templates.edit');
        Route::put('/report-templates/{reportTemplate}', [ReportTemplateController::class, 'update'])->name('report-templates.update');
        Route::delete('/report-templates/{reportTemplate}', [ReportTemplateController::class, 'destroy'])->name('report-templates.destroy');

        // Section routes (nested under report-templates)
        Route::post('/report-templates/{reportTemplate}/sections', [SectionController::class, 'store'])->name('report-templates.sections.store');
        Route::put('/report-templates/{reportTemplate}/sections/{section}', [SectionController::class, 'update'])->name('report-templates.sections.update');
        Route::delete('/report-templates/{reportTemplate}/sections/{section}', [SectionController::class, 'destroy'])->name('report-templates.sections.destroy');
        Route::post('/report-templates/{reportTemplate}/sections/{section}/locations', [SectionController::class, 'assignLocation'])->name('report-templates.sections.locations.assign');
        Route::delete('/report-templates/{reportTemplate}/sections/{section}/locations/{location}', [SectionController::class, 'removeLocation'])->name('report-templates.sections.locations.remove');

        // Report assignment (Tugas Pelaporan)
        Route::get('/report-assignment', [ReportController::class, 'index'])->name('report-assignment.index');
        Route::get('/report-assignment/create', [ReportController::class, 'create'])->name('report-assignment.create');
        Route::post('/report-assignment/store', [ReportController::class, 'store'])->name('report-assignment.store');
        Route::get('/report-assignment/{report}/edit', [ReportController::class, 'edit'])->name('report-assignment.edit');
        Route::put('/report-assignment/{report}', [ReportController::class, 'update'])->name('report-assignment.update');
        Route::delete('/report-assignment/{report}', [ReportController::class, 'destroy'])->name('report-assignment.destroy');
    });

    Route::middleware('role:analyst')->prefix('analyst')->name('analyst.')->group(function () {
        Route::get('/reports', [AnalystReportController::class, 'index'])->name('reports.index');
        Route::post('/reports/{report}/start', [AnalystReportController::class, 'start'])->name('reports.start');
        Route::get('/reports/{report}', [AnalystReportController::class, 'show'])->name('reports.show');
        Route::get('/reports/{report}/fill', [AnalystReportController::class, 'fill'])->name('reports.fill');
        Route::put('/reports/{report}/fill', [AnalystReportController::class, 'save'])->name('reports.save');
    });
});
