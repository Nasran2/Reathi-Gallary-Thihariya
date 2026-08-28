<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\{Sale, SaleItem, SalePayment, Expense, ExpenseCategory, Customer, Product, User, Store, PaymentMethod};
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

$admin = User::first();
$customer = Customer::first();
$product = Product::first();
$store = Store::first();
$cash = PaymentMethod::where('code', 'cash')->first();
$card = PaymentMethod::where('code', 'card')->first();
$bank = PaymentMethod::where('code', 'bank_transfer')->first();
$expenseCategory = ExpenseCategory::first();

$dates = [
    now(),
    now()->subDays(1),
    now()->subDays(2),
];

foreach ($dates as $index => $date) {
    // 1. Create a Sale (Fully Paid in Cash)
    $sale1 = Sale::create([
        'uuid' => (string) Str::uuid(),
        'idempotency_key' => (string) Str::uuid(),
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'user_id' => $admin->id,
        'invoice_no' => 'INV-TEST-'.rand(1000, 9999),
        'sale_type' => 'main',
        'status' => 'completed',
        'subtotal' => 1500,
        'discount_total' => 0,
        'tax_total' => 0,
        'grand_total' => 1500,
        'cost_total' => 1000,
        'profit_total' => 500,
        'paid_total' => 1500,
        'pending_total' => 0,
        'due_total' => 0,
        'sold_at' => $date->copy()->setHour(10)->setMinute(30),
    ]);
    
    SaleItem::create([
        'sale_id' => $sale1->id,
        'product_id' => $product->id,
        'unit_id' => $product->base_unit_id,
        'quantity' => 2,
        'conversion_rate' => 1,
        'base_quantity' => 2,
        'unit_price' => 750,
        'net_revenue' => 1500,
        'cost_at_sale' => 500,
        'cost_total' => 1000,
        'discount_amount' => 0,
        'profit' => 500,
    ]);
    
    SalePayment::create([
        'sale_id' => $sale1->id,
        'payment_method_id' => $cash->id,
        'amount' => 1500,
    ]);

    // 2. Create a Sale (Partially Paid with Bank/Card, leaving Due)
    $sale2 = Sale::create([
        'uuid' => (string) Str::uuid(),
        'idempotency_key' => (string) Str::uuid(),
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'user_id' => $admin->id,
        'invoice_no' => 'INV-TEST-'.rand(1000, 9999),
        'sale_type' => 'main',
        'status' => 'completed',
        'subtotal' => 5000,
        'discount_total' => 0,
        'tax_total' => 0,
        'grand_total' => 5000,
        'cost_total' => 3000,
        'profit_total' => 2000,
        'paid_total' => 3000,
        'pending_total' => 0,
        'due_total' => 2000,
        'sold_at' => $date->copy()->setHour(14)->setMinute(15),
    ]);
    
    SaleItem::create([
        'sale_id' => $sale2->id,
        'product_id' => $product->id,
        'unit_id' => $product->base_unit_id,
        'quantity' => 1,
        'conversion_rate' => 1,
        'base_quantity' => 1,
        'unit_price' => 5000,
        'net_revenue' => 5000,
        'cost_at_sale' => 3000,
        'cost_total' => 3000,
        'discount_amount' => 0,
        'profit' => 2000,
    ]);
    
    SalePayment::create([
        'sale_id' => $sale2->id,
        'payment_method_id' => $bank->id, // Bank Transfer
        'amount' => 3000,
        'reference' => 'TXN'.rand(10000,99999),
    ]);
    
    // 3. Create an Expense
    Expense::create([
        'store_id' => $store->id,
        'expense_category_id' => $expenseCategory->id,
        'payment_method_id' => $cash->id,
        'user_id' => $admin->id,
        'amount' => rand(500, 1200),
        'expense_date' => $date->toDateString(),
        'description' => 'Sample test expense for ' . $expenseCategory->name,
    ]);
}

echo "Seeded 6 sales and 3 expenses for testing.\n";
