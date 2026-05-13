<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {
    }

    public function index(): View|RedirectResponse
    {
        $view = $this->dashboardService->resolveViewByRole(
            Auth::user()?->role
        );

        if ($view === null) {
            return redirect('/');
        }

        return view($view);
    }
}
