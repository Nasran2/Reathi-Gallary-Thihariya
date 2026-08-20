<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreenSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_primary_staff_screens_render(): void
    {
        $this->seed();
        $this->actingAs(User::where('email', 'admin@reathi.test')->first());
        foreach ([
            route('dashboard', absolute: false), route('pos.main', absolute: false), route('pos.remnant', absolute: false), route('products.index', absolute: false),
            route('products.create', absolute: false), route('inventory.index', absolute: false), route('inventory.movements', absolute: false),
            route('categories.index', absolute: false), route('units.index', absolute: false), route('brands.index', absolute: false), route('unit-presets.index', absolute: false),
            route('inventory.adjust', absolute: false), route('remnants.index', absolute: false), route('remnants.transfer', absolute: false),
            route('purchases.index', absolute: false), route('purchases.create', absolute: false), route('sales.index', absolute: false),
            route('customers.index', absolute: false), route('suppliers.index', absolute: false), route('expenses.index', absolute: false),
            route('customers.show', Customer::first(), absolute: false), route('suppliers.show', Supplier::first(), absolute: false),
            route('purchases.returns', absolute: false), route('sales.returns', absolute: false), route('transfers.index', absolute: false), route('expenses.categories', absolute: false),
            route('cheques.dashboard', absolute: false), route('cheques.received', absolute: false), route('cheques.issued', absolute: false), route('cheques.history', absolute: false),
            route('reports.sales', absolute: false), route('reports.purchases', absolute: false), route('reports.stock', absolute: false), route('reports.low-stock', absolute: false),
            route('reports.profit', absolute: false), route('reports.valuation', absolute: false), route('reports.dead', absolute: false), route('reports.expenses', absolute: false),
            route('reports.due-bills', absolute: false), route('reports.customer-due', absolute: false), route('reports.supplier-due', absolute: false), route('reports.daily-closing', absolute: false), route('reports.cheques', absolute: false),
            route('users.index', absolute: false), route('roles.index', absolute: false), route('settings.index', absolute: false),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_powered_by_footer_is_visible_on_login_admin_and_pos_layouts(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Software powered by')
            ->assertSee('Twinsofte.com')
            ->assertDontSee('Default setup:')
            ->assertDontSee('admin@reathi.test');

        $this->seed();
        $this->actingAs(User::where('email', 'admin@reathi.test')->firstOrFail());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Software powered by')
            ->assertSee('https://twinsofte.com', false);

        $this->get(route('pos.main'))
            ->assertOk()
            ->assertSee('Software powered by')
            ->assertSee('Twinsofte.com');
    }
}
