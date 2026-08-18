<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
        
        Gate::define('manage-products', fn (User $u) => $u->hasAnyRole(['manager', 'storekeeper']));
        Gate::define('manage-purchases', fn (User $u) => $u->hasAnyRole(['manager', 'storekeeper']));
        Gate::define('adjust-stock', fn (User $u) => $u->hasAnyRole(['manager', 'storekeeper']));
        Gate::define('transfer-remnant', fn (User $u) => $u->hasAnyRole(['manager', 'storekeeper']));
        Gate::define('view-cost', fn (User $u) => $u->hasRole('manager'));
        Gate::define('view-reports', fn (User $u) => $u->hasRole('manager'));
        Gate::define('manage-settings', fn (User $u) => false);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
