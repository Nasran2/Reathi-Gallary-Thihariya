<?php

namespace Tests\Feature;

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
            route('inventory.adjust', absolute: false), route('remnants.index', absolute: false), route('remnants.transfer', absolute: false),
            route('purchases.index', absolute: false), route('purchases.create', absolute: false), route('sales.index', absolute: false),
            route('customers.index', absolute: false), route('suppliers.index', absolute: false), route('expenses.index', absolute: false),
            route('reports.profit', absolute: false), route('reports.valuation', absolute: false), route('reports.dead', absolute: false), route('settings.index', absolute: false),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
