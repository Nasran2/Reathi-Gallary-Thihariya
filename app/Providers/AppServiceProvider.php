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
        Gate::before(fn (User $user) => in_array($user->role, ['super_admin', 'admin'], true) ? true : null);
        Gate::define('manage-products', fn (User $u) => in_array($u->role, ['manager', 'storekeeper'], true));
        Gate::define('manage-purchases', fn (User $u) => in_array($u->role, ['manager', 'storekeeper'], true));
        Gate::define('adjust-stock', fn (User $u) => in_array($u->role, ['manager', 'storekeeper'], true));
        Gate::define('transfer-remnant', fn (User $u) => in_array($u->role, ['manager', 'storekeeper'], true));
        Gate::define('view-cost', fn (User $u) => $u->role === 'manager');
        Gate::define('view-reports', fn (User $u) => $u->role === 'manager');
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
