<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\ChequeService;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use App\Services\ReturnService;
use App\Services\SaleService;
use App\Services\StockTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosOperationsTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Customer $customer;

    private Supplier $supplier;

    private Product $product;

    private Unit $unit;

    private PaymentMethod $cash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = Store::create(['name' => 'Main', 'code' => 'MAIN', 'is_default' => true]);
        $this->customer = Customer::create(['name' => 'Customer A']);
        $this->supplier = Supplier::create(['name' => 'Supplier A']);
        $this->unit = Unit::create(['name' => 'Piece', 'symbol' => 'pc']);
        $this->cash = PaymentMethod::create(['name' => 'Cash', 'code' => 'cash']);
        PaymentMethod::firstOrCreate(['code' => 'cheque'], ['name' => 'Cheque', 'requires_reference' => true]);
        BusinessSetting::write('allow_negative_stock', false);
        $this->product = Product::create(['name' => 'Fabric', 'sku' => 'FAB-1', 'base_unit_id' => $this->unit->id, 'default_purchase_unit_id' => $this->unit->id, 'default_selling_unit_id' => $this->unit->id, 'average_cost' => 100]);
        $this->product->productUnits()->create(['unit_id' => $this->unit->id, 'conversion_rate' => 1]);
        app(InventoryService::class)->move($this->product, $this->store->id, 'main', 'opening', 1000, 0, 100);
    }

    private function sale(float $total, float $cash = 0)
    {
        return app(SaleService::class)->checkout(['sale_type' => 'main', 'store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'idempotency_key' => (string) Str::uuid(), 'items' => [['product_id' => $this->product->id, 'unit_id' => $this->unit->id, 'quantity' => 1, 'unit_price' => $total]], 'payments' => $cash ? [['payment_method_id' => $this->cash->id, 'amount' => $cash]] : []]);
    }

    private function purchase(float $total)
    {
        return app(PurchaseService::class)->receive(['supplier_id' => $this->supplier->id, 'store_id' => $this->store->id, 'purchase_date' => today()->format('Y-m-d'), 'items' => [['product_id' => $this->product->id, 'unit_id' => $this->unit->id, 'quantity' => 1, 'supplier_unit_cost' => $total]]]);
    }

    private function receive(float $amount, string $number): Cheque
    {
        return app(ChequeService::class)->receive(['customer_id' => $this->customer->id, 'cheque_number' => $number, 'bank' => 'Test Bank', 'cheque_date' => today()->format('Y-m-d'), 'received_date' => today()->format('Y-m-d'), 'amount' => $amount, 'allocation_mode' => 'automatic']);
    }

    public function test_cash_due_pending_cheque_pass_and_double_pass_are_consistent(): void
    {
        $sale = $this->sale(10000, 4000);
        $this->assertSame('6000.0000', $sale->due_total);
        $this->assertSame('6000.0000', $this->customer->fresh()->balance);

        $cheque = $this->receive(6000, 'RC-1');
        $sale->refresh();
        $this->assertSame('4000.0000', $sale->paid_total);
        $this->assertSame('6000.0000', $sale->pending_total);
        $this->assertSame('0.0000', $sale->due_total);
        $this->assertSame('6000.0000', $this->customer->fresh()->balance);

        app(ChequeService::class)->pass($cheque);
        $sale->refresh();
        $this->assertSame('10000.0000', $sale->paid_total);
        $this->assertSame('0.0000', $sale->pending_total);
        $this->assertSame('0.0000', $this->customer->fresh()->balance);
        $ledgerCount = CustomerLedger::count();
        app(ChequeService::class)->pass($cheque->fresh());
        $this->assertSame($ledgerCount, CustomerLedger::count());
    }

    public function test_returned_customer_cheque_restores_invoice_due_without_counting_as_paid(): void
    {
        $sale = $this->sale(6000);
        $cheque = $this->receive(6000, 'RC-2');
        app(ChequeService::class)->returnCheque($cheque, ['return_date' => today()->format('Y-m-d'), 'return_reason' => 'Insufficient funds']);
        $sale->refresh();
        $this->assertSame('0.0000', $sale->pending_total);
        $this->assertSame('6000.0000', $sale->due_total);
        $this->assertSame('0.0000', $sale->paid_total);
        $this->assertSame('6000.0000', $this->customer->fresh()->balance);
    }

    public function test_endorsed_cheque_pass_clears_both_parties_from_one_cheque(): void
    {
        $sale = $this->sale(20000);
        $purchase = $this->purchase(20000);
        $cheque = $this->receive(20000, 'RC-3');
        app(ChequeService::class)->endorse($cheque, ['supplier_id' => $this->supplier->id, 'amount' => 20000, 'transfer_date' => today()->format('Y-m-d'), 'allocation_mode' => 'automatic']);
        $this->assertSame('20000.0000', $sale->fresh()->pending_total);
        $this->assertSame('20000.0000', $purchase->fresh()->pending_total);
        app(ChequeService::class)->pass($cheque->fresh());
        $this->assertSame('0.0000', $this->customer->fresh()->balance);
        $this->assertSame('0.0000', $this->supplier->fresh()->balance);
        $this->assertSame('20000.0000', $sale->fresh()->paid_total);
        $this->assertSame('20000.0000', $purchase->fresh()->paid_total);
    }

    public function test_endorsed_and_own_cheque_returns_restore_only_the_correct_dues(): void
    {
        $sale = $this->sale(20000);
        $purchase = $this->purchase(50000);
        $endorsed = $this->receive(20000, 'RC-4');
        app(ChequeService::class)->endorse($endorsed, ['supplier_id' => $this->supplier->id, 'amount' => 20000, 'transfer_date' => today()->format('Y-m-d'), 'allocation_mode' => 'automatic']);
        app(ChequeService::class)->returnCheque($endorsed->fresh(), ['return_date' => today()->format('Y-m-d')]);
        $this->assertSame('20000.0000', $sale->fresh()->due_total);
        $this->assertSame('50000.0000', $purchase->fresh()->due_total);

        $own = app(ChequeService::class)->issue(['supplier_id' => $this->supplier->id, 'cheque_number' => 'OC-1', 'bank' => 'Business Bank', 'business_bank_account' => 'Main', 'cheque_date' => today()->format('Y-m-d'), 'issue_date' => today()->format('Y-m-d'), 'amount' => 30000, 'allocation_mode' => 'automatic']);
        app(ChequeService::class)->returnCheque($own, ['return_date' => today()->format('Y-m-d')]);
        $this->assertSame('50000.0000', $purchase->fresh()->due_total);
        $this->assertSame('20000.0000', $this->customer->fresh()->balance);
        $this->assertSame('50000.0000', $this->supplier->fresh()->balance);
    }

    public function test_sales_and_purchase_returns_reverse_stock_and_ledgers(): void
    {
        $sale = $this->sale(1000);
        $saleReturn = app(ReturnService::class)->sale(['sale_id' => $sale->id, 'return_date' => today()->format('Y-m-d'), 'settlement' => 'customer_credit', 'reason' => 'Wrong item', 'items' => [$sale->items->first()->id => 1]]);
        $this->assertSame('1000.0000', $saleReturn->return_total);
        $this->assertSame('0.0000', $this->customer->fresh()->balance);
        $this->assertSame('1000.000000', $this->product->balances()->where('inventory_type', 'main')->value('quantity'));

        $purchase = $this->purchase(500);
        $purchaseReturn = app(ReturnService::class)->purchase(['purchase_id' => $purchase->id, 'return_date' => today()->format('Y-m-d'), 'reason' => 'Damaged', 'items' => [$purchase->items->first()->id => 1]]);
        $this->assertSame('500.0000', $purchaseReturn->return_total);
        $this->assertSame('0.0000', $this->supplier->fresh()->balance);
        $this->assertSame('1000.000000', $this->product->balances()->where('inventory_type', 'main')->value('quantity'));
    }

    public function test_stock_transfer_preserves_combined_quantity(): void
    {
        $transfer = app(StockTransferService::class)->transfer(['product_id' => $this->product->id, 'unit_id' => $this->unit->id, 'from_store_id' => $this->store->id, 'to_store_id' => $this->store->id, 'from_type' => 'main', 'to_type' => 'remnant', 'quantity' => 2, 'transfer_date' => today()->format('Y-m-d'), 'reason' => 'Short length']);
        $this->assertSame('2.000000', $transfer->base_quantity);
        $this->assertSame('998.000000', $this->product->balances()->where('inventory_type', 'main')->value('quantity'));
        $this->assertSame('2.000000', $this->product->balances()->where('inventory_type', 'remnant')->value('quantity'));
        $this->assertEquals(1000, $this->product->balances()->sum('quantity'));
    }

    public function test_return_during_pending_cheque_restores_only_net_invoice_due(): void
    {
        $sale = $this->sale(6000);
        $cheque = $this->receive(6000, 'RC-RETURN');
        app(ReturnService::class)->sale(['sale_id' => $sale->id, 'return_date' => today()->format('Y-m-d'), 'settlement' => 'customer_credit', 'reason' => 'Partial return', 'items' => [$sale->items->first()->id => 0.5]]);
        $this->assertSame('3000.0000', $sale->fresh()->pending_total);
        app(ChequeService::class)->returnCheque($cheque->fresh(), ['return_date' => today()->format('Y-m-d')]);
        $this->assertSame('3000.0000', $sale->fresh()->due_total);
        $this->assertSame('3000.0000', $this->customer->fresh()->balance);
    }
}
