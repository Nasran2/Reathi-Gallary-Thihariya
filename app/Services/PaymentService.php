<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private LedgerService $ledger) {}

    public function customer(array $data, string $status = 'cleared', ?int $chequeId = null, ?int $userId = null): CustomerPayment
    {
        return DB::transaction(function () use ($data, $status, $chequeId, $userId) {
            $customer = Customer::lockForUpdate()->findOrFail($data['customer_id']);
            $amount = Decimal::of($data['amount']);
            $due = Decimal::of($customer->balance)->minus(Decimal::of($customer->pending_balance));
            if ($amount->isLessThanOrEqualTo(0)) {
                throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
            }
            if ($amount->isGreaterThan($due) && ! filter_var(BusinessSetting::read('allow_customer_advance', false), FILTER_VALIDATE_BOOL)) {
                throw ValidationException::withMessages(['amount' => 'Payment cannot be greater than the customer due.']);
            }
            $method = PaymentMethod::findOrFail($data['payment_method_id']);
            $payment = CustomerPayment::create([
                'uuid' => Str::uuid(), 'customer_id' => $customer->id, 'payment_method_id' => $method->id,
                'cheque_id' => $chequeId, 'amount' => Decimal::money($amount), 'payment_date' => $data['payment_date'],
                'status' => $status, 'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null,
                'created_by' => $userId, 'cleared_at' => $status === 'cleared' ? now() : null,
            ]);
            $this->allocateCustomer($payment, $data['allocations'] ?? [], $data['allocation_mode'] ?? 'automatic', $status);
            if ($status === 'pending') {
                $this->ledger->customer($customer, 'cheque_received', 0, 0, $payment, $data['reference'] ?? 'Pending cheque', $amount, $data['payment_date']);
            } else {
                $this->ledger->customer($customer, 'payment', 0, $amount, $payment, $data['reference'] ?? $method->name, 0, $data['payment_date']);
            }

            return $payment->load('allocations.sale', 'method', 'cheque');
        }, 3);
    }

    public function supplier(array $data, string $status = 'cleared', ?int $chequeId = null, ?int $userId = null): SupplierPayment
    {
        return DB::transaction(function () use ($data, $status, $chequeId, $userId) {
            $supplier = Supplier::lockForUpdate()->findOrFail($data['supplier_id']);
            $amount = Decimal::of($data['amount']);
            $due = Decimal::of($supplier->balance)->minus(Decimal::of($supplier->pending_balance));
            if ($amount->isLessThanOrEqualTo(0) || $amount->isGreaterThan($due)) {
                throw ValidationException::withMessages(['amount' => 'Payment must be greater than zero and cannot exceed supplier due.']);
            }
            $method = PaymentMethod::findOrFail($data['payment_method_id']);
            $payment = SupplierPayment::create([
                'uuid' => Str::uuid(), 'supplier_id' => $supplier->id, 'payment_method_id' => $method->id,
                'cheque_id' => $chequeId, 'amount' => Decimal::money($amount), 'payment_date' => $data['payment_date'],
                'status' => $status, 'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null,
                'created_by' => $userId, 'cleared_at' => $status === 'cleared' ? now() : null,
            ]);
            $this->allocateSupplier($payment, $data['allocations'] ?? [], $data['allocation_mode'] ?? 'automatic', $status);
            if ($status === 'pending') {
                $this->ledger->supplier($supplier, 'cheque_issued', 0, 0, $payment, $data['reference'] ?? 'Pending cheque', $amount, $data['payment_date']);
            } else {
                $this->ledger->supplier($supplier, 'payment', $amount, 0, $payment, $data['reference'] ?? $method->name, 0, $data['payment_date']);
            }

            return $payment->load('allocations.purchase', 'method', 'cheque');
        }, 3);
    }

    private function allocateCustomer(CustomerPayment $payment, array $manual, string $mode, string $status): void
    {
        $remaining = Decimal::of($payment->amount);
        $sales = Sale::where('customer_id', $payment->customer_id)->where('due_total', '>', 0)->oldest('sold_at')->lockForUpdate()->get();
        foreach ($sales as $sale) {
            $requested = $mode === 'manual' ? Decimal::of($manual[$sale->id] ?? 0) : $remaining;
            if ($requested->isLessThanOrEqualTo(0)) {
                continue;
            }
            if ($requested->isGreaterThan(Decimal::of($sale->due_total))) {
                if ($mode === 'manual') {
                    throw ValidationException::withMessages(['allocations' => "Allocation exceeds due on {$sale->invoice_no}."]);
                }
                $requested = Decimal::of($sale->due_total);
            }
            if ($requested->isGreaterThan($remaining)) {
                throw ValidationException::withMessages(['allocations' => 'Allocations exceed the payment amount.']);
            }
            $payment->allocations()->create(['sale_id' => $sale->id, 'amount' => Decimal::money($requested), 'status' => $status]);
            $changes = ['due_total' => Decimal::sub($sale->due_total, $requested, 4)];
            $column = $status === 'pending' ? 'pending_total' : 'paid_total';
            $changes[$column] = Decimal::add($sale->{$column}, $requested, 4);
            $sale->update($changes);
            $remaining = $remaining->minus($requested);
            if ($remaining->isZero()) {
                break;
            }
        }
        if ($mode === 'manual' && collect($manual)->sum(fn ($v) => (float) $v) - (float) $payment->amount > 0.0001) {
            throw ValidationException::withMessages(['allocations' => 'Allocations exceed the payment amount.']);
        }
    }

    private function allocateSupplier(SupplierPayment $payment, array $manual, string $mode, string $status): void
    {
        $remaining = Decimal::of($payment->amount);
        $purchases = Purchase::where('supplier_id', $payment->supplier_id)->where('due_total', '>', 0)->oldest('purchase_date')->lockForUpdate()->get();
        foreach ($purchases as $purchase) {
            $requested = $mode === 'manual' ? Decimal::of($manual[$purchase->id] ?? 0) : $remaining;
            if ($requested->isLessThanOrEqualTo(0)) {
                continue;
            }
            if ($requested->isGreaterThan(Decimal::of($purchase->due_total))) {
                if ($mode === 'manual') {
                    throw ValidationException::withMessages(['allocations' => "Allocation exceeds due on {$purchase->purchase_no}."]);
                }
                $requested = Decimal::of($purchase->due_total);
            }
            if ($requested->isGreaterThan($remaining)) {
                throw ValidationException::withMessages(['allocations' => 'Allocations exceed the payment amount.']);
            }
            $payment->allocations()->create(['purchase_id' => $purchase->id, 'amount' => Decimal::money($requested), 'status' => $status]);
            $column = $status === 'pending' ? 'pending_total' : 'paid_total';
            $purchase->update(['due_total' => Decimal::sub($purchase->due_total, $requested, 4), $column => Decimal::add($purchase->{$column}, $requested, 4)]);
            $remaining = $remaining->minus($requested);
            if ($remaining->isZero()) {
                break;
            }
        }
    }

    public function clearCustomer(CustomerPayment $payment, ?string $date = null): void
    {
        if ($payment->status !== 'pending') {
            return;
        }
        foreach ($payment->allocations()->where('status', 'pending')->lockForUpdate()->get() as $allocation) {
            $sale = Sale::lockForUpdate()->findOrFail($allocation->sale_id);
            $sale->update(['pending_total' => Decimal::sub($sale->pending_total, $allocation->amount, 4), 'paid_total' => Decimal::add($sale->paid_total, $allocation->amount, 4)]);
            $allocation->update(['status' => 'cleared']);
        }
        $payment->update(['status' => 'cleared', 'cleared_at' => $date ? \Carbon\Carbon::parse($date) : now()]);
        $this->ledger->customer($payment->customer, 'cheque_passed', 0, $payment->amount, $payment, $payment->reference, Decimal::of($payment->amount)->negated(), $date);
    }

    public function cancelCustomer(CustomerPayment $payment): void
    {
        if ($payment->status !== 'pending') {
            return;
        }
        foreach ($payment->allocations()->where('status', 'pending')->lockForUpdate()->get() as $allocation) {
            $sale = Sale::lockForUpdate()->findOrFail($allocation->sale_id);
            $sale->update(['pending_total' => Decimal::sub($sale->pending_total, $allocation->amount, 4), 'due_total' => Decimal::add($sale->due_total, $allocation->amount, 4)]);
            $allocation->update(['status' => 'cancelled']);
        }
        $payment->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $this->ledger->customer($payment->customer, 'cheque_returned', 0, 0, $payment, $payment->reference, Decimal::of($payment->amount)->negated());
    }

    public function clearSupplier(SupplierPayment $payment, ?string $date = null): void
    {
        if ($payment->status !== 'pending') {
            return;
        }
        foreach ($payment->allocations()->where('status', 'pending')->lockForUpdate()->get() as $allocation) {
            $purchase = Purchase::lockForUpdate()->findOrFail($allocation->purchase_id);
            $purchase->update(['pending_total' => Decimal::sub($purchase->pending_total, $allocation->amount, 4), 'paid_total' => Decimal::add($purchase->paid_total, $allocation->amount, 4)]);
            $allocation->update(['status' => 'cleared']);
        }
        $payment->update(['status' => 'cleared', 'cleared_at' => $date ? \Carbon\Carbon::parse($date) : now()]);
        $this->ledger->supplier($payment->supplier, 'cheque_passed', $payment->amount, 0, $payment, $payment->reference, Decimal::of($payment->amount)->negated(), $date);
    }

    public function cancelSupplier(SupplierPayment $payment): void
    {
        if ($payment->status !== 'pending') {
            return;
        }
        foreach ($payment->allocations()->where('status', 'pending')->lockForUpdate()->get() as $allocation) {
            $purchase = Purchase::lockForUpdate()->findOrFail($allocation->purchase_id);
            $purchase->update(['pending_total' => Decimal::sub($purchase->pending_total, $allocation->amount, 4), 'due_total' => Decimal::add($purchase->due_total, $allocation->amount, 4)]);
            $allocation->update(['status' => 'cancelled']);
        }
        $payment->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $this->ledger->supplier($payment->supplier, 'cheque_returned', 0, 0, $payment, $payment->reference, Decimal::of($payment->amount)->negated());
    }
}
