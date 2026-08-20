<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'admin@reathi.test')->firstOrFail());
    }

    public function test_product_list_has_explicit_view_edit_and_delete_actions(): void
    {
        $product = Product::where('sku', 'FAB-COT-001')->firstOrFail();

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Actions')
            ->assertSee('View')
            ->assertSee('Edit')
            ->assertSee('Delete')
            ->assertSee(route('products.show', $product), false)
            ->assertSee(route('products.destroy', $product), false);
    }

    public function test_product_details_show_stock_in_and_out_ledger(): void
    {
        $product = Product::where('sku', 'FAB-COT-001')->firstOrFail();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Total Stock In')
            ->assertSee('Total Stock Out')
            ->assertSee('Stock In & Out History', false)
            ->assertSee('Opening Stock')
            ->assertSee('Main Store')
            ->assertSee('+85.750 m');
    }

    public function test_unused_product_can_be_deleted(): void
    {
        $unit = Unit::where('symbol', 'm')->firstOrFail();
        $product = Product::create([
            'name' => 'Unused Product',
            'sku' => 'UNUSED-DELETE-1',
            'base_unit_id' => $unit->id,
            'average_cost' => 0,
            'main_selling_price' => 0,
            'remnant_selling_price' => 0,
            'minimum_stock' => 0,
            'reorder_level' => 0,
            'active' => true,
        ]);
        $product->productUnits()->create([
            'unit_id' => $unit->id,
            'base_quantity' => 1,
            'unit_quantity' => 1,
            'conversion_rate' => 1,
            'can_purchase' => true,
            'can_sell' => true,
        ]);

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Product deleted successfully.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_units', ['product_id' => $product->id]);
    }

    public function test_product_with_inventory_history_is_protected_from_deletion(): void
    {
        $product = Product::where('sku', 'FAB-COT-001')->firstOrFail();

        $this->from(route('products.index'))
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('error', 'Cannot delete product. It may be linked to existing sales or inventory.');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('product_units', ['product_id' => $product->id]);
    }

    public function test_product_barcode_is_trimmed_and_must_be_unique(): void
    {
        $unit = Unit::where('symbol', 'm')->firstOrFail();
        Product::where('sku', 'FAB-COT-001')->firstOrFail()->update(['barcode' => 'SCAN-100']);

        $this->from(route('products.create'))->post(route('products.store'), [
            'name' => 'Duplicate Barcode Product',
            'sku' => 'BARCODE-DUPLICATE-1',
            'barcode' => '  SCAN-100  ',
            'base_unit_id' => $unit->id,
            'average_cost' => 10,
            'main_selling_price' => 20,
            'remnant_selling_price' => 15,
            'minimum_stock' => 0,
            'reorder_level' => 0,
        ])->assertRedirect(route('products.create'))
            ->assertSessionHasErrors('barcode');

        $this->assertDatabaseMissing('products', ['sku' => 'BARCODE-DUPLICATE-1']);
    }

    public function test_barcode_cannot_duplicate_another_product_sku(): void
    {
        $unit = Unit::where('symbol', 'm')->firstOrFail();

        $this->from(route('products.create'))->post(route('products.store'), [
            'name' => 'Ambiguous Identifier Product',
            'sku' => 'AMBIGUOUS-NEW-1',
            'barcode' => 'FAB-COT-001',
            'base_unit_id' => $unit->id,
            'average_cost' => 10,
            'main_selling_price' => 20,
            'remnant_selling_price' => 15,
            'minimum_stock' => 0,
            'reorder_level' => 0,
        ])->assertRedirect(route('products.create'))
            ->assertSessionHasErrors([
                'barcode' => 'This barcode is already used as another product SKU.',
            ]);

        $this->assertDatabaseMissing('products', ['sku' => 'AMBIGUOUS-NEW-1']);
    }

    public function test_pos_search_only_auto_adds_unambiguous_matches(): void
    {
        $this->get(route('pos.main'))
            ->assertOk()
            ->assertSee('Search name, SKU or scan barcode…')
            ->assertSee('@input.debounce.300ms="autoAddUnique()"', false)
            ->assertSee('@keydown.enter.prevent="addUniqueSearch()"', false)
            ->assertSee('if(matches.length!==1)return', false)
            ->assertSee("autoAddUnique(){if(!this.search.trim())return;this.addUniqueSearch()}", false)
            ->assertDontSee('addFirst()', false);
    }
}
