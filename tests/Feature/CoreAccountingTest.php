<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\InventoryBalance;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\Unit;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use App\Services\RemnantService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CoreAccountingTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Supplier $supplier;

    private PaymentMethod $cash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = Store::create(['name' => 'Main', 'code' => 'MAIN', 'is_default' => true]);
        $this->supplier = Supplier::create(['name' => 'Supplier A', 'opening_balance' => 0]);
        $this->cash = PaymentMethod::create(['name' => 'Cash', 'code' => 'cash', 'sort_order' => 1]);
        BusinessSetting::write('allow_negative_stock', false);
        BusinessSetting::write('remnant_partial_sale', true);
    }

    private function product(string $name, Unit $base, array $units, string $average = '0', string $mainPrice = '0', string $remnantPrice = '0'): Product
    {
        $p = Product::create(['name' => $name, 'sku' => Str::upper(Str::random(8)), 'base_unit_id' => $base->id, 'default_purchase_unit_id' => $base->id, 'default_selling_unit_id' => $base->id, 'average_cost' => $average, 'main_selling_price' => $mainPrice, 'remnant_selling_price' => $remnantPrice]);
        foreach ($units as [$unit,$rate]) {
            $p->productUnits()->create(['unit_id' => $unit->id, 'conversion_rate' => $rate, 'can_purchase' => true, 'can_sell' => true]);
        }

        return $p;
    }

    private function sale(Product $p, Unit $unit, string $qty, string $price, string $type = 'main', ?int $remnantId = null): Sale
    {
        return app(SaleService::class)->checkout(['sale_type' => $type, 'store_id' => $this->store->id, 'idempotency_key' => (string) Str::uuid(), 'items' => [['product_id' => $p->id, 'unit_id' => $unit->id, 'remnant_id' => $remnantId, 'quantity' => $qty, 'unit_price' => $price, 'discount_amount' => 0, 'tax_amount' => 0]], 'payments' => [['payment_method_id' => $this->cash->id, 'amount' => (string) ((float) $qty * (float) $price)]]]);
    }

    public function test_piece_dozen_conversion_keeps_decimal_stock_correct(): void
    {
        $piece = Unit::create(['name' => 'Piece', 'symbol' => 'pc']);
        $dozen = Unit::create(['name' => 'Dozen', 'symbol' => 'doz']);
        $p = $this->product('Buttons', $piece, [[$piece, 1], [$dozen, 12]], '0', '100', '80');
        app(PurchaseService::class)->receive(['supplier_id' => $this->supplier->id, 'store_id' => $this->store->id, 'purchase_date' => now()->toDateString(), 'extra_cost_total' => 0, 'items' => [['product_id' => $p->id, 'unit_id' => $dozen->id, 'quantity' => 10, 'supplier_unit_cost' => 600, 'discount_amount' => 0, 'tax_amount' => 0]]]);
        $this->assertSame('120.000000', InventoryBalance::first()->quantity);
        $this->sale($p, $dozen, '1.5', '1100');
        $this->assertSame('102.000000', InventoryBalance::first()->fresh()->quantity);
    }

    public function test_meter_yard_conversion_deducts_9_144_meters(): void
    {
        $meter = Unit::create(['name' => 'Meter', 'symbol' => 'm']);
        $yard = Unit::create(['name' => 'Yard', 'symbol' => 'yd']);
        $p = $this->product('Fabric', $meter, [[$meter, 1], [$yard, '.9144']], '500', '850', '600');
        app(InventoryService::class)->move($p, $this->store->id, 'main', 'opening_stock', 100, 0, 500);
        $this->sale($p, $yard, '10', '800');
        $this->assertSame('90.856000', InventoryBalance::first()->fresh()->quantity);
    }

    public function test_weighted_average_cost_is_calculated_at_high_precision(): void
    {
        $meter = Unit::create(['name' => 'Meter', 'symbol' => 'm']);
        $p = $this->product('Fabric', $meter, [[$meter, 1]], '500', '800', '500');
        app(InventoryService::class)->move($p, $this->store->id, 'main', 'opening_stock', 100, 0, 500);
        app(PurchaseService::class)->receive(['supplier_id' => $this->supplier->id, 'store_id' => $this->store->id, 'purchase_date' => now()->toDateString(), 'extra_cost_total' => 0, 'items' => [['product_id' => $p->id, 'unit_id' => $meter->id, 'quantity' => 50, 'supplier_unit_cost' => 600, 'discount_amount' => 0, 'tax_amount' => 0]]]);
        $this->assertSame('533.33333333', $p->fresh()->average_cost);
    }

    public function test_historical_profit_does_not_change_when_average_cost_changes(): void
    {
        $meter = Unit::create(['name' => 'Meter', 'symbol' => 'm']);
        $p = $this->product('Fabric', $meter, [[$meter, 1]], '500', '800', '400');
        app(InventoryService::class)->move($p, $this->store->id, 'main', 'opening_stock', 10, 0, 500);
        $sale = $this->sale($p, $meter, '1', '800');
        $p->update(['average_cost' => 600]);
        $this->assertSame('500.00000000', $sale->items->first()->fresh()->cost_at_sale);
        $this->assertSame('300.0000', $sale->items->first()->fresh()->profit);
    }

    public function test_remnant_transfer_preserves_combined_physical_stock(): void
    {
        $meter = Unit::create(['name' => 'Meter', 'symbol' => 'm']);
        $p = $this->product('Fabric', $meter, [[$meter, 1]], '500', '800', '300');
        app(InventoryService::class)->move($p, $this->store->id, 'main', 'opening_stock', 2, 0, 500);
        $r = app(RemnantService::class)->transfer(['product_id' => $p->id, 'store_id' => $this->store->id, 'unit_id' => $meter->id, 'quantity' => '1.25', 'remnant_price' => 300, 'reason' => 'Remainder']);
        $this->assertSame('0.750000', InventoryBalance::where('inventory_type', 'main')->value('quantity'));
        $this->assertSame('1.250000', InventoryBalance::where('inventory_type', 'remnant')->value('quantity'));
        $this->assertEquals(2, InventoryBalance::sum('quantity'));
        $this->assertSame('1.250000', $r->remaining_base_quantity);
    }

    public function test_remnant_below_cost_sale_reports_a_real_loss(): void
    {
        $meter = Unit::create(['name' => 'Meter', 'symbol' => 'm']);
        $p = $this->product('Fabric', $meter, [[$meter, 1]], '500', '800', '300');
        app(InventoryService::class)->move($p, $this->store->id, 'main', 'opening_stock', 2, 0, 500);
        $r = app(RemnantService::class)->transfer(['product_id' => $p->id, 'store_id' => $this->store->id, 'unit_id' => $meter->id, 'quantity' => 1, 'remnant_price' => 300, 'reason' => 'Remainder']);
        $sale = $this->sale($p, $meter, '1', '300', 'remnant', $r->id);
        $this->assertSame('-200.0000', $sale->profit_total);
        $this->assertSame('sold', $r->fresh()->status);
    }

    public function test_supplier_due_uses_invoice_cost_not_landed_cost(): void
    {
        $meter = Unit::create(['name' => 'Meter', 'symbol' => 'm']);
        $p = $this->product('Fabric', $meter, [[$meter, 1]], '0', '1500', '900');
        app(PurchaseService::class)->receive(['supplier_id' => $this->supplier->id, 'store_id' => $this->store->id, 'purchase_date' => now()->toDateString(), 'extra_cost_total' => 5000, 'items' => [['product_id' => $p->id, 'unit_id' => $meter->id, 'quantity' => 100, 'supplier_unit_cost' => 1000, 'discount_amount' => 0, 'tax_amount' => 0]]]);
        $this->assertSame('100000.0000', SupplierLedger::latest()->value('balance_after'));
        $this->assertSame('1050.00000000', $p->fresh()->average_cost);
    }

    public function test_checkout_idempotency_prevents_double_stock_deduction(): void
    {
        $meter = Unit::create(['name' => 'Meter', 'symbol' => 'm']);
        $p = $this->product('Fabric', $meter, [[$meter, 1]], '500', '800', '300');
        app(InventoryService::class)->move($p, $this->store->id, 'main', 'opening_stock', 10, 0, 500);
        $payload = ['sale_type' => 'main', 'store_id' => $this->store->id, 'idempotency_key' => 'stable-key', 'items' => [['product_id' => $p->id, 'unit_id' => $meter->id, 'quantity' => 2, 'unit_price' => 800]], 'payments' => [['payment_method_id' => $this->cash->id, 'amount' => 1600]]];
        $a = app(SaleService::class)->checkout($payload);
        $b = app(SaleService::class)->checkout($payload);
        $this->assertSame($a->id,$b->id);
        $this->assertSame('8.000000',InventoryBalance::first()->fresh()->quantity);
    }

    public function test_invoice_pdf_renders_the_new_layout_and_powered_by_footer(): void
    {
        $meter = Unit::create(['name' => 'Meter', 'symbol' => 'm']);
        $product = $this->product('Invoice Fabric', $meter, [[$meter, 1]], '500', '800', '300');
        app(InventoryService::class)->move($product, $this->store->id, 'main', 'opening_stock', 10, 0, 500);
        $sale = $this->sale($product, $meter, '1', '800');
        $user = User::forceCreate([
            'name' => 'Invoice Tester',
            'username' => 'invoice-tester',
            'email' => 'invoice@example.test',
            'password' => bcrypt('password'),
            'active' => true,
        ]);
        $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('sales.view', 'web'));
        $sale->update(['user_id' => $user->id]);
        $sale->load('items.product', 'items.unit', 'items.remnant', 'payments.method', 'customer', 'store', 'user');

        $html = view('sales.pdf', compact('sale'))->render();
        $this->assertStringContainsString('Payment summary', $html);
        $this->assertStringContainsString('Software powered by', $html);
        $this->assertStringContainsString('Twinsofte.com', $html);

        $response = $this->actingAs($user)->get(route('sales.pdf', $sale));
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
}
