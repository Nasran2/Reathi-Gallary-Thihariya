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
        //
    }

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        // Temporary compatibility aliases for existing controllers/views while all
        // authorization is sourced from database permissions (never role names).
        Gate::define('manage-products', fn (User $u) => $u->can('products.edit'));
        Gate::define('manage-purchases', fn (User $u) => $u->can('purchases.create'));
        Gate::define('adjust-stock', fn (User $u) => $u->can('inventory.adjust'));
        Gate::define('transfer-remnant', fn (User $u) => $u->can('inventory.remnant_transfer'));
        Gate::define('view-cost', fn (User $u) => $u->hasAnyPermission(['products.view_cost', 'inventory.view_cost', 'purchases.view_cost']));
        Gate::define('view-reports', fn (User $u) => $u->hasAnyPermission(collect(config('access.groups.Reports', []))->keys()->all()));
        Gate::define('manage-settings', fn (User $u) => $u->hasAnyPermission(collect(config('access.groups.Settings', []))->keys()->all()));
    }
}
