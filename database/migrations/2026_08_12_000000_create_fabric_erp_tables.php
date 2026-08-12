<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('cashier')->after('password');
            $table->boolean('active')->default(true)->after('role');
        });

        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->boolean('encrypted')->default(false);
            $table->timestamps();
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('address')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol', 16)->unique();
            $table->boolean('allows_decimal')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile')->nullable()->index();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('group')->nullable();
            $table->decimal('credit_limit', 18, 4)->default(0);
            $table->decimal('opening_balance', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_walk_in')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('phone')->nullable()->index();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->decimal('opening_balance', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('default_purchase_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('default_selling_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('default_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('image_path')->nullable();
            $table->string('brand')->nullable();
            $table->string('fabric_type')->nullable();
            $table->string('material')->nullable();
            $table->string('colour')->nullable();
            $table->string('pattern')->nullable();
            $table->string('width')->nullable();
            $table->text('description')->nullable();
            $table->decimal('minimum_stock', 18, 6)->default(0);
            $table->decimal('reorder_level', 18, 6)->default(0);
            $table->decimal('tax_rate', 9, 4)->default(0);
            $table->decimal('average_cost', 18, 8)->default(0);
            $table->boolean('track_rolls')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->decimal('conversion_rate', 18, 8);
            $table->decimal('main_selling_price', 18, 4)->default(0);
            $table->decimal('remnant_selling_price', 18, 4)->default(0);
            $table->boolean('can_purchase')->default(true);
            $table->boolean('can_sell')->default(true);
            $table->timestamps();
            $table->unique(['product_id', 'unit_id']);
        });

        Schema::create('product_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->decimal('last_cost', 18, 8)->default(0);
            $table->decimal('lowest_cost', 18, 8)->default(0);
            $table->decimal('highest_cost', 18, 8)->default(0);
            $table->dateTime('last_purchased_at')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'supplier_id']);
        });

        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('inventory_type', 16); // main, remnant
            $table->decimal('quantity', 18, 6)->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'store_id', 'inventory_type']);
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('purchase_no')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('supplier_invoice_no')->nullable();
            $table->string('reference_no')->nullable();
            $table->date('purchase_date');
            $table->date('due_date')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('supplier_total', 18, 4)->default(0);
            $table->decimal('extra_cost_total', 18, 4)->default(0);
            $table->decimal('paid_total', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 6);
            $table->decimal('conversion_rate', 18, 8);
            $table->decimal('base_quantity', 18, 6);
            $table->decimal('supplier_unit_cost', 18, 8);
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('supplier_line_total', 18, 4);
            $table->decimal('allocated_extra_cost', 18, 4)->default(0);
            $table->decimal('landed_unit_cost', 18, 8);
            $table->decimal('previous_average_cost', 18, 8)->default(0);
            $table->decimal('new_average_cost', 18, 8)->default(0);
            $table->timestamps();
        });

        Schema::create('fabric_rolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('roll_number');
            $table->string('batch_number')->nullable();
            $table->decimal('original_quantity', 18, 6);
            $table->decimal('remaining_quantity', 18, 6);
            $table->decimal('supplier_cost', 18, 8);
            $table->decimal('landed_cost', 18, 8);
            $table->date('purchase_date')->nullable();
            $table->string('status')->default('available');
            $table->timestamps();
            $table->unique(['store_id', 'roll_number']);
        });

        Schema::create('remnants', function (Blueprint $table) {
            $table->id();
            $table->string('remnant_no')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_roll_id')->nullable()->constrained('fabric_rolls')->nullOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('original_quantity', 18, 6);
            $table->decimal('remaining_quantity', 18, 6);
            $table->decimal('conversion_rate', 18, 8);
            $table->decimal('original_base_quantity', 18, 6);
            $table->decimal('remaining_base_quantity', 18, 6);
            $table->decimal('cost_per_base_unit', 18, 8);
            $table->decimal('normal_price', 18, 4)->default(0);
            $table->decimal('remnant_price', 18, 4);
            $table->decimal('discount_percent', 9, 4)->default(0);
            $table->string('status')->default('available');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_no')->unique();
            $table->string('idempotency_key')->unique();
            $table->string('sale_type', 16); // main, remnant
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('completed');
            $table->decimal('subtotal', 18, 4);
            $table->decimal('discount_total', 18, 4)->default(0);
            $table->decimal('tax_total', 18, 4)->default(0);
            $table->decimal('grand_total', 18, 4);
            $table->decimal('paid_total', 18, 4)->default(0);
            $table->decimal('due_total', 18, 4)->default(0);
            $table->decimal('cost_total', 18, 4)->default(0);
            $table->decimal('profit_total', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('sold_at');
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('remnant_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 18, 6);
            $table->decimal('conversion_rate', 18, 8);
            $table->decimal('base_quantity', 18, 6);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('net_revenue', 18, 4);
            $table->decimal('cost_at_sale', 18, 8);
            $table->decimal('cost_total', 18, 4);
            $table->decimal('profit', 18, 4);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->string('icon')->nullable();
            $table->boolean('requires_reference')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 18, 4);
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 18, 4);
            $table->string('reference')->nullable();
            $table->date('paid_at');
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('inventory_type', 16);
            $table->string('movement_type');
            $table->nullableMorphs('reference');
            $table->decimal('quantity_in', 18, 6)->default(0);
            $table->decimal('quantity_out', 18, 6)->default(0);
            $table->decimal('balance_after', 18, 6);
            $table->decimal('unit_cost', 18, 8)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'store_id', 'inventory_type', 'created_at'], 'movement_ledger_idx');
        });

        Schema::create('customer_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('reference');
            $table->string('type');
            $table->decimal('debit', 18, 4)->default(0);
            $table->decimal('credit', 18, 4)->default(0);
            $table->decimal('balance_after', 18, 4);
            $table->dateTime('occurred_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('reference');
            $table->string('type');
            $table->decimal('debit', 18, 4)->default(0);
            $table->decimal('credit', 18, 4)->default(0);
            $table->decimal('balance_after', 18, 4);
            $table->dateTime('occurred_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('expense_date');
            $table->decimal('amount', 18, 4);
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });

        Schema::create('public_invoice_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('token', 80)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone');
            $table->text('message');
            $table->text('gateway_response')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'sms_logs', 'public_invoice_tokens', 'expenses', 'expense_categories',
            'supplier_ledger', 'customer_ledger', 'inventory_movements', 'purchase_payments',
            'sale_payments', 'payment_methods', 'sale_items', 'sales', 'remnants', 'fabric_rolls',
            'purchase_items', 'purchases', 'inventory_balances', 'product_suppliers', 'product_units',
            'products', 'suppliers', 'customers', 'categories', 'units', 'stores', 'business_settings'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'active']));
    }
};
