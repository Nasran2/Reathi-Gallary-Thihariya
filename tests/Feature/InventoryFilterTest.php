<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $permissions): User
    {
        $user = User::factory()->create(['active' => true]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        return $user;
    }

    public function test_inventory_filters_work_together_and_cost_is_permission_protected(): void
    {
        $store = Store::create(['name' => 'Main', 'code' => 'MAIN', 'active' => true]);
        $unit = Unit::create(['name' => 'Meter', 'symbol' => 'm', 'active' => true]);
        $category = Category::create(['name' => 'Cotton', 'slug' => 'cotton', 'active' => true]);
        $brand = Brand::create(['name' => 'Loom', 'active' => true]);
        $supplier = Supplier::create(['name' => 'Mill', 'active' => true]);
        $matching = Product::create(['name' => 'Blue Cotton', 'sku' => 'BLUE-1', 'barcode' => '9988', 'category_id' => $category->id, 'brand_id' => $brand->id, 'default_supplier_id' => $supplier->id, 'base_unit_id' => $unit->id, 'average_cost' => 100, 'main_selling_price' => 180, 'minimum_stock' => 5, 'reorder_level' => 10, 'active' => true]);
        $matching->suppliers()->attach($supplier->id);
        $other = Product::create(['name' => 'Red Linen', 'sku' => 'RED-1', 'base_unit_id' => $unit->id, 'average_cost' => 200, 'main_selling_price' => 300, 'active' => false]);
        InventoryBalance::create(['product_id' => $matching->id, 'store_id' => $store->id, 'inventory_type' => 'main', 'quantity' => 7]);
        InventoryBalance::create(['product_id' => $other->id, 'store_id' => $store->id, 'inventory_type' => 'main', 'quantity' => 0]);
        $sale = Sale::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(), 'invoice_no' => 'INV-FILTER-1',
            'idempotency_key' => 'inventory-filter-1', 'sale_type' => 'main', 'store_id' => $store->id,
            'status' => 'completed', 'subtotal' => 180, 'grand_total' => 180, 'sold_at' => now()->subDay(),
        ]);
        SaleItem::create([
            'sale_id' => $sale->id, 'product_id' => $matching->id, 'unit_id' => $unit->id,
            'quantity' => 1, 'conversion_rate' => 1, 'base_quantity' => 1, 'unit_price' => 180,
            'net_revenue' => 180, 'cost_at_sale' => 100, 'cost_total' => 100, 'profit' => 80,
        ]);

        $restricted = $this->user(['inventory.view', 'products.view']);
        $this->actingAs($restricted)->get(route('inventory.index', ['q' => '9988', 'category_id' => $category->id, 'brand_id' => $brand->id, 'supplier_id' => $supplier->id, 'stock_status' => 'low_stock', 'product_status' => 'active']))
            ->assertOk()->assertSee('Blue Cotton')->assertDontSee('Red Linen')->assertDontSee('Minimum cost')->assertDontSee('Average cost');

        $this->actingAs($restricted)->get(route('inventory.index', ['export' => 'csv']))
            ->assertOk()->assertDownload()->assertDontSee('Average Cost');

        $costUser = $this->user(['inventory.view', 'inventory.view_cost', 'products.view']);
        $this->actingAs($costUser)->get(route('inventory.index', ['min_cost' => 150]))
            ->assertOk()->assertDontSee('Blue Cotton')->assertSee('Red Linen')->assertSee('Average cost');
        $this->actingAs($costUser)->get(route('inventory.index', ['export' => 'pdf']))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}
