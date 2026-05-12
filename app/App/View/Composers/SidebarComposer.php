<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Domain\User\Repositories\UserRepository;
use Domain\Report\Repositories\ReportRepository;

class SidebarComposer
{
    // public function __construct(
    //     protected UserRepository $userRepo,
    //     protected ReportRepository $reportRepo
    // ) {}

    // public function compose(View $view)
    // {
    //     $view->with([
    //         'analysts' => $this->userRepo->getActiveAnalysts(),
    //         'pendingReports' => $this->reportRepo->countPending(),
    //     ]);
    // }
}