<?php

namespace App\Providers;

use App\View\Composers\SidebarComposer;
use Domain\User\Interfaces\PasswordSettingRepositoryInterface;
use Domain\User\Interfaces\UserRepositoryInterface;
use Domain\User\Repositories\PasswordSettingRepository;
use Domain\User\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $bindings = [
            UserRepositoryInterface::class => UserRepository::class,
            PasswordSettingRepositoryInterface::class => PasswordSettingRepository::class,
        ];

        foreach ($bindings as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.sidebar.sidebar', SidebarComposer::class);
    }
}
