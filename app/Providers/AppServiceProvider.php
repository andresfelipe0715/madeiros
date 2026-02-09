<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::define('view-orders', function (\App\Models\User $user) {
            return (new \App\Services\OrderPermissionService)->canView($user);
        });

        \Illuminate\Support\Facades\Gate::define('edit-orders', function (\App\Models\User $user) {
            return (new \App\Services\OrderPermissionService)->canEdit($user);
        });

        \Illuminate\Support\Facades\Gate::define('create-orders', function (\App\Models\User $user) {
            return (new \App\Services\OrderPermissionService)->canCreate($user);
        });
    }
}
