<?php

namespace App\View\Composers;

use Domain\Report\Repositories\ReportRepository;
use Domain\User\Repositories\UserRepository;
use Illuminate\View\View;

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
