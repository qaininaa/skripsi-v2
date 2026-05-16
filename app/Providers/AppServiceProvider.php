<?php

namespace App\Providers;

use App\View\Composers\SidebarComposer;
use Domain\Location\Interfaces\LocationRepositoryInterface;
use Domain\Location\Repositories\LocationRepository;
use Domain\PasswordPolicy\Interfaces\PasswordPolicyRepositoryInterface;
use Domain\PasswordPolicy\Repositories\PasswordPolicyRepository;
use Domain\PasswordPolicy\Services\PasswordPolicyService;
use Domain\Report\Interfaces\ReportRepositoryInterface;
use Domain\Report\Repositories\ReportRepository;
use Domain\ReportTemplate\Interfaces\ReportTemplateRepositoryInterface;
use Domain\ReportTemplate\Interfaces\SectionRepositoryInterface;
use Domain\ReportTemplate\Repositories\ReportTemplateRepository;
use Domain\ReportTemplate\Repositories\SectionRepository;
use Domain\Room\Interfaces\RoomRepositoryInterface;
use Domain\Room\Models\Room;
use Domain\Room\Observers\RoomCacheObserver;
use Domain\Room\Repositories\RoomRepository;
use Domain\User\Interfaces\PasswordHistoryRepositoryInterface;
use Domain\User\Interfaces\UserRepositoryInterface;
use Domain\User\Repositories\PasswordHistoryRepository;
use Domain\User\Repositories\UserRepository;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $bindings = [
            UserRepositoryInterface::class               => UserRepository::class,
            PasswordPolicyRepositoryInterface::class     => PasswordPolicyRepository::class,
            PasswordHistoryRepositoryInterface::class    => PasswordHistoryRepository::class,
            RoomRepositoryInterface::class               => RoomRepository::class,
            LocationRepositoryInterface::class           => LocationRepository::class,
            ReportTemplateRepositoryInterface::class     => ReportTemplateRepository::class,
            SectionRepositoryInterface::class            => SectionRepository::class,
            ReportRepositoryInterface::class             => ReportRepository::class,
        ];

        foreach ($bindings as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        // Bind Services
        $this->app->singleton(PasswordPolicyService::class, function ($app) {
            return new PasswordPolicyService(
                $app->make(PasswordPolicyRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('components.pagination.default');

        View::composer('components.sidebar.sidebar', SidebarComposer::class);

        // Cache Observers
        Room::observe(RoomCacheObserver::class);
    }
}
