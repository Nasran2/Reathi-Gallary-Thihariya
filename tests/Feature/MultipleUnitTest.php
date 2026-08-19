<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Unit;
use App\Models\UnitPreset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultipleUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'admin@reathi.test')->firstOrFail());
    }

    public function test_preset_saves_standard_and_group_conversion_quantities(): void
    {
        $piece = Unit::where('symbol', 'pc')->firstOrFail();
        $dozen = Unit::where('symbol', 'doz')->firstOrFail();

        $this->post(route('unit-presets.store'), [
            'name' => 'Piece / Dozen',
            'base_unit_id' => $piece->id,
            'conversions' => [[
                'base_quantity' => '12',
                'unit_quantity' => '1',
                'unit_id' => $dozen->id,
            ]],
        ])->assertRedirect(route('unit-presets.index'));

        $conversion = UnitPreset::where('name', 'Piece / Dozen')->firstOrFail()->conversions()->firstOrFail();
        $this->assertSame('12.00000000', $conversion->base_quantity);
        $this->assertSame('1.00000000', $conversion->unit_quantity);
    }

    public function test_preset_rejects_base_unit_and_duplicate_converted_units(): void
    {
        $meter = Unit::where('symbol', 'm')->firstOrFail();
        $yard = Unit::where('symbol', 'yd')->firstOrFail();

        $this->from(route('unit-presets.create'))->post(route('unit-presets.store'), [
            'name' => 'Invalid preset',
            'base_unit_id' => $meter->id,
            'conversions' => [
                ['base_quantity' => 1, 'unit_quantity' => 1, 'unit_id' => $meter->id],
                ['base_quantity' => 1, 'unit_quantity' => 1.09361, 'unit_id' => $yard->id],
                ['base_quantity' => 1, 'unit_quantity' => 3, 'unit_id' => $yard->id],
            ],
        ])->assertRedirect(route('unit-presets.create'))
            ->assertSessionHasErrors(['conversions.0.unit_id', 'conversions.2.unit_id']);

        $this->assertDatabaseMissing('unit_presets', ['name' => 'Invalid preset']);
    }

    public function test_product_save_injects_base_unit_and_calculates_exact_conversion_rate(): void
    {
        $meter = Unit::where('symbol', 'm')->firstOrFail();
        $yard = Unit::where('symbol', 'yd')->firstOrFail();

        $this->post(route('products.store'), [
            'name' => 'Conversion Test Fabric',
            'sku' => 'CONV-TEST-1',
            'base_unit_id' => $meter->id,
            'average_cost' => 50,
            'main_selling_price' => 100,
            'remnant_selling_price' => 80,
            'minimum_stock' => 0,
            'reorder_level' => 0,
            'units' => [[
                'unit_id' => $yard->id,
                'base_quantity' => 1,
                'unit_quantity' => '1.09361',
                'can_purchase' => 1,
                'can_sell' => 1,
            ]],
        ])->assertRedirect(route('products.index'));

        $product = Product::where('sku', 'CONV-TEST-1')->firstOrFail();
        $this->assertCount(2, $product->productUnits);

        $base = $product->productUnits->firstWhere('unit_id', $meter->id);
        $converted = $product->productUnits->firstWhere('unit_id', $yard->id);
        $this->assertSame('1.00000000', $base->conversion_rate);
        $this->assertSame('0.91440276', $converted->conversion_rate);
        $this->assertEqualsWithDelta(91.44, 100 * (float) $converted->conversion_rate, 0.001);
    }

    public function test_product_form_contains_csp_safe_deferred_base_filtered_preset_loading(): void
    {
        $meter = Unit::where('symbol', 'm')->firstOrFail();
        $yard = Unit::where('symbol', 'yd')->firstOrFail();
        UnitPreset::create(['name' => 'Meter Standard', 'base_unit_id' => $meter->id])
            ->conversions()->create(['base_quantity' => 1, 'unit_quantity' => '1.09361', 'unit_id' => $yard->id]);

        $this->get(route('products.create'))
            ->assertOk()
            ->assertSee('x-data="productForm"', false)
            ->assertSee('id="product-form-data"', false)
            ->assertSee('Load Multiple Unit Preset')
            ->assertSee('"base_unit_id":', false)
            ->assertSee('x-show="baseUnitId"', false)
            ->assertDontSee('x-model="newCatName" placeholder="E.g. Linen" required', false)
            ->assertSee('Meter Standard');

        $this->get(route('unit-presets.create'))
            ->assertOk()
            ->assertSee('x-data="unitPresetForm"', false)
            ->assertSee('id="unit-preset-form-data"', false);
    }
}
