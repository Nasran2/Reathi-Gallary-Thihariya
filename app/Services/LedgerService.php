<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Model;

class LedgerService
{
    public function customer(Customer $customer, string $type, mixed $debit, mixed $credit, Model $reference, ?string $notes = null): void
    {
        $last = CustomerLedger::where('customer_id', $customer->id)->lockForUpdate()->latest('id')->first();
        $balance = Decimal::sub(Decimal::add($last?->balance_after ?? $customer->opening_balance, $debit, 4), $credit, 4);
        CustomerLedger::create(['customer_id' => $customer->id, 'reference_type' => $reference->getMorphClass(), 'reference_id' => $reference->getKey(), 'type' => $type, 'debit' => $debit, 'credit' => $credit, 'balance_after' => $balance, 'occurred_at' => now(), 'notes' => $notes]);
    }

    public function supplier(Supplier $supplier, string $type, mixed $debit, mixed $credit, Model $reference, ?string $notes = null): void
    {
        $last = SupplierLedger::where('supplier_id', $supplier->id)->lockForUpdate()->latest('id')->first();
        $balance = Decimal::sub(Decimal::add($last?->balance_after ?? $supplier->opening_balance, $credit, 4), $debit, 4);
        SupplierLedger::create(['supplier_id' => $supplier->id, 'reference_type' => $reference->getMorphClass(), 'reference_id' => $reference->getKey(), 'type' => $type, 'debit' => $debit, 'credit' => $credit, 'balance_after' => $balance, 'occurred_at' => now(), 'notes' => $notes]);
    }
}
