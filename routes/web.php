<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Location\LocationController;
use App\Http\Controllers\PasswordPolicy\PasswordPolicyController;
use App\Http\Controllers\ReportApproval\ReportApprovalController;
use App\Http\Controllers\ReportArchive\ReportArchiveController;
use App\Http\Controllers\ReportAssignment\ReportAssignmentController;
use App\Http\Controllers\ReportAssignment\SectionInstanceController;
use App\Http\Controllers\ReportFill\ReportFillController;
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

    Route::middleware('role:super,admin,analyst,supervisor,manager')
        ->prefix('arsip-laporan')
        ->name('arsip-laporan.')
        ->group(function () {
            Route::get('/', [ReportArchiveController::class, 'index'])->name('index');
            Route::get('/{reportId}', [ReportArchiveController::class, 'show'])->name('show');
        });

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
        Route::get('/report-assignment', [ReportAssignmentController::class, 'index'])->name('report-assignment.index');
        Route::get('/report-assignment/create', [ReportAssignmentController::class, 'create'])->name('report-assignment.create');
        Route::post('/report-assignment/store', [ReportAssignmentController::class, 'store'])->name('report-assignment.store');
        Route::get('/report-assignment/{report}/preview', [ReportAssignmentController::class, 'preview'])->name('report-assignment.preview');
        Route::get('/report-assignment/{report}', [ReportAssignmentController::class, 'show'])->name('report-assignment.show');
        Route::get('/report-assignment/{report}/edit', [ReportAssignmentController::class, 'edit'])->name('report-assignment.edit');
        Route::put('/report-assignment/{report}', [ReportAssignmentController::class, 'update'])->name('report-assignment.update');
        Route::delete('/report-assignment/{report}', [ReportAssignmentController::class, 'destroy'])->name('report-assignment.destroy');

        // Per-report section instance actions (duplicate)
        Route::post(
            '/report-assignment/{report}/sections/{instance}/duplicate',
            [SectionInstanceController::class, 'duplicate'],
        )->name('report-assignment.sections.duplicate');
        Route::delete(
            '/report-assignment/{report}/sections/{instance}/duplicate',
            [SectionInstanceController::class, 'destroyDuplicate'],
        )->name('report-assignment.sections.duplicate.destroy');
    });

    // Report Fill — Analyst report fill-in process
    Route::middleware('role:analyst')->prefix('report-fill')->name('report-fill.')->group(function () {
        Route::get('/', [ReportFillController::class, 'index'])->name('index');
        Route::post('/{report}/start', [ReportFillController::class, 'start'])->name('start');
        Route::get('/{report}/preview', [ReportFillController::class, 'preview'])->name('preview');
        Route::get('/{report}', [ReportFillController::class, 'show'])->name('show');
        Route::get('/{report}/fill', [ReportFillController::class, 'fill'])->name('fill');
        Route::put('/{report}/monitoring', [ReportFillController::class, 'saveMonitoring'])->name('save-monitoring');
        Route::put('/{report}/reading', [ReportFillController::class, 'saveReading'])->name('save-reading');
    });

    // Report Approval — Supervisor approval step
    Route::middleware('role:supervisor')->prefix('supervisor')->name('supervisor.')->group(function () {
        Route::get('/inbox', [ReportApprovalController::class, 'inbox'])->defaults('step', 'supervisor')->name('inbox');
        Route::get('/in-progress', [ReportApprovalController::class, 'inProgress'])->defaults('step', 'supervisor')->name('in-progress');
        Route::get('/reports/{report}/preview', [ReportApprovalController::class, 'preview'])->defaults('step', 'supervisor')->name('reports.preview');
        Route::get('/reports/{report}', [ReportApprovalController::class, 'show'])->defaults('step', 'supervisor')->name('reports.show');
        Route::post('/reports/{report}/approve', [ReportApprovalController::class, 'approve'])->defaults('step', 'supervisor')->name('reports.approve');
        Route::post('/reports/{report}/return', [ReportApprovalController::class, 'return'])->defaults('step', 'supervisor')->name('reports.return');
    });

    // Report Approval — Manager approval step
    Route::middleware('role:manager')->prefix('manager')->name('manager.')->group(function () {
        Route::get('/inbox', [ReportApprovalController::class, 'inbox'])->defaults('step', 'manager')->name('inbox');
        Route::get('/in-progress', [ReportApprovalController::class, 'inProgress'])->defaults('step', 'manager')->name('in-progress');
        Route::get('/reports/{report}/preview', [ReportApprovalController::class, 'preview'])->defaults('step', 'manager')->name('reports.preview');
        Route::get('/reports/{report}', [ReportApprovalController::class, 'show'])->defaults('step', 'manager')->name('reports.show');
        Route::post('/reports/{report}/approve', [ReportApprovalController::class, 'approve'])->defaults('step', 'manager')->name('reports.approve');
        Route::post('/reports/{report}/return', [ReportApprovalController::class, 'return'])->defaults('step', 'manager')->name('reports.return');
    });
});
