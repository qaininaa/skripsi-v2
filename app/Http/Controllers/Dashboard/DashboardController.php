<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {
    }

    public function index(): View
    {
        $user = Auth::user();

        try {
            $view = $this->dashboardService->resolveViewByRole($user?->role);
        } catch (InvalidArgumentException) {
            abort(403, 'Role tidak memiliki halaman dashboard.');
        }

        return view($view, [
            'userName' => $this->dashboardService->resolveUserName($user),
        ]);
    }
}
