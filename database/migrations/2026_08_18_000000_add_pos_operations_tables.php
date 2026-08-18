<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('pending_total', 18, 4)->default(0)->after('paid_total');
            $table->decimal('returned_total', 18, 4)->default(0)->after('due_total');
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('pending_total', 18, 4)->default(0)->after('paid_total');
            $table->decimal('due_total', 18, 4)->default(0)->after('pending_total');
            $table->decimal('returned_total', 18, 4)->default(0)->after('due_total');
        });
        DB::table('purchases')->update(['due_total' => DB::raw('supplier_total - paid_total')]);

        Schema::table('customer_ledger', fn (Blueprint $table) => $table->decimal('pending', 18, 4)->default(0)->after('credit'));
        Schema::table('supplier_ledger', fn (Blueprint $table) => $table->decimal('pending', 18, 4)->default(0)->after('credit'));

        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('direction', 16); // received, issued
            $table->string('source_type', 24); // customer, business
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('cheque_number', 80);
            $table->string('bank', 120);
            $table->string('branch', 120)->nullable();
            $table->string('account_name', 150)->nullable();
            $table->string('business_bank_account', 150)->nullable();
            $table->date('cheque_date');
            $table->date('received_date')->nullable();
            $table->date('issue_date')->nullable();
            $table->decimal('amount', 18, 4);
            $table->string('status', 24)->default('pending');
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('deposited_at')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->date('return_date')->nullable();
            $table->string('return_reason')->nullable();
            $table->decimal('bank_charge', 18, 4)->default(0);
            $table->boolean('charge_customer')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['direction', 'status', 'cheque_date']);
            $table->unique(['direction', 'bank', 'cheque_number'], 'cheque_identity_unique');
        });

        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->foreignId('cheque_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('amount', 18, 4);
            $table->date('payment_date');
            $table->string('status', 24)->default('cleared');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cleared_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('customer_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 18, 4);
            $table->string('status', 24);
            $table->timestamps();
            $table->unique(['customer_payment_id', 'sale_id'], 'customer_payment_sale_unique');
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->foreignId('cheque_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('amount', 18, 4);
            $table->date('payment_date');
            $table->string('status', 24)->default('cleared');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cleared_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('supplier_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 18, 4);
            $table->string('status', 24);
            $table->timestamps();
            $table->unique(['supplier_payment_id', 'purchase_id'], 'supplier_payment_purchase_unique');
        });

        Schema::create('cheque_endorsements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cheque_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_payment_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 18, 4);
            $table->date('transfer_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('cheque_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cheque_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->string('description');
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('return_no')->unique();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('return_date');
            $table->decimal('return_total', 18, 4);
            $table->decimal('cost_total', 18, 4);
            $table->string('settlement', 24)->default('customer_credit');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 6);
            $table->decimal('base_quantity', 18, 6);
            $table->decimal('return_amount', 18, 4);
            $table->decimal('cost_amount', 18, 4);
            $table->timestamps();
        });

        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('return_no')->unique();
            $table->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->date('return_date');
            $table->decimal('return_total', 18, 4);
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 6);
            $table->decimal('base_quantity', 18, 6);
            $table->decimal('return_amount', 18, 4);
            $table->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('transfer_no')->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('to_store_id')->constrained('stores')->restrictOnDelete();
            $table->string('from_type', 16);
            $table->string('to_type', 16);
            $table->decimal('quantity', 18, 6);
            $table->decimal('base_quantity', 18, 6);
            $table->date('transfer_date');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        foreach ([['Cheque', 'cheque', 20], ['Own Cheque', 'own_cheque', 21], ['Customer Cheque / Endorsed', 'endorsed_cheque', 22]] as [$name, $code, $sort]) {
            DB::table('payment_methods')->updateOrInsert(['code' => $code], ['name' => $name, 'requires_reference' => true, 'active' => true, 'sort_order' => $sort, 'updated_at' => now(), 'created_at' => now()]);
        }
        foreach (['Rent', 'Electricity', 'Transport', 'Loading', 'Delivery', 'Salary', 'Maintenance', 'Other'] as $name) {
            DB::table('expense_categories')->updateOrInsert(['name' => $name], ['active' => true, 'updated_at' => now(), 'created_at' => now()]);
        }
        DB::table('business_settings')->updateOrInsert(['key' => 'allow_customer_advance'], ['value' => 'false', 'encrypted' => false, 'updated_at' => now(), 'created_at' => now()]);
    }

    public function down(): void
    {
        foreach (['stock_transfers', 'purchase_return_items', 'purchase_returns', 'sale_return_items', 'sale_returns', 'cheque_events', 'cheque_endorsements', 'supplier_payment_allocations', 'supplier_payments', 'customer_payment_allocations', 'customer_payments', 'cheques'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('supplier_ledger', fn (Blueprint $table) => $table->dropColumn('pending'));
        Schema::table('customer_ledger', fn (Blueprint $table) => $table->dropColumn('pending'));
        Schema::table('purchases', fn (Blueprint $table) => $table->dropColumn(['pending_total', 'due_total', 'returned_total']));
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn(['pending_total', 'returned_total']));
    }
};
