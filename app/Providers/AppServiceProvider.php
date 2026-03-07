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
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        \Illuminate\Support\Facades\Gate::define('view-orders', function (\App\Models\User $user) {
            return $user->role->hasPermission('orders', 'view');
        });

        \Illuminate\Support\Facades\Gate::define('edit-orders', function (\App\Models\User $user) {
            return $user->role->hasPermission('orders', 'edit');
        });

        \Illuminate\Support\Facades\Gate::define('create-orders', function (\App\Models\User $user) {
            return $user->role->hasPermission('orders', 'create');
        });

        \Illuminate\Support\Facades\Gate::define('view-clients', function (\App\Models\User $user) {
            return $user->role->hasPermission('clients', 'view');
        });

        \Illuminate\Support\Facades\Gate::define('create-clients', function (\App\Models\User $user) {
            return $user->role->hasPermission('clients', 'create');
        });

        \Illuminate\Support\Facades\Gate::define('edit-clients', function (\App\Models\User $user) {
            return $user->role->hasPermission('clients', 'edit');
        });

        \Illuminate\Support\Facades\Gate::define('view-users', function (\App\Models\User $user) {
            return $user->role->hasPermission('users', 'view');
        });

        \Illuminate\Support\Facades\Gate::define('create-users', function (\App\Models\User $user) {
            return $user->role->hasPermission('users', 'create');
        });

        \Illuminate\Support\Facades\Gate::define('edit-users', function (\App\Models\User $user) {
            return $user->role->hasPermission('users', 'edit');
        });

        \Illuminate\Support\Facades\Gate::define('view-performance', function (\App\Models\User $user) {
            return $user->role->hasPermission('performance', 'view');
        });

        \Illuminate\Support\Facades\Gate::define('view-materials', function (\App\Models\User $user) {
            return $user->role->hasPermission('materials', 'view');
        });

        \Illuminate\Support\Facades\Gate::define('create-materials', function (\App\Models\User $user) {
            return $user->role->hasPermission('materials', 'create');
        });

        \Illuminate\Support\Facades\Gate::define('edit-materials', function (\App\Models\User $user) {
            return $user->role->hasPermission('materials', 'edit');
        });

        \Illuminate\Support\Facades\Gate::define('view-special-services', function (\App\Models\User $user) {
            return $user->role->hasPermission('special_services', 'view');
        });

        \Illuminate\Support\Facades\Gate::define('create-special-services', function (\App\Models\User $user) {
            return $user->role->hasPermission('special_services', 'create');
        });

        \Illuminate\Support\Facades\Gate::define('edit-special-services', function (\App\Models\User $user) {
            return $user->role->hasPermission('special_services', 'edit');
        });

        \Illuminate\Support\Facades\Gate::define('view-bodega', function (\App\Models\User $user) {
            return $user->role->hasPermission('bodega', 'view');
        });

        \Illuminate\Support\Facades\Gate::define('create-bodega', function (\App\Models\User $user) {
            return $user->role->hasPermission('bodega', 'create');
        });

        \Illuminate\Support\Facades\Gate::define('edit-bodega', function (\App\Models\User $user) {
            return $user->role->hasPermission('bodega', 'edit');
        });
    }
}
