<?php

namespace App\Services;

use App\Models\Cheque;
use App\Models\PaymentMethod;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChequeService
{
    public function __construct(private PaymentService $payments, private LedgerService $ledger) {}

    public function receive(array $data, ?int $userId = null): Cheque
    {
        return DB::transaction(function () use ($data, $userId) {
            $cheque = Cheque::create([
                'uuid' => Str::uuid(), 'direction' => 'received', 'source_type' => 'customer', 'customer_id' => $data['customer_id'],
                'cheque_number' => $data['cheque_number'], 'bank' => $data['bank'], 'branch' => $data['branch'] ?? null,
                'account_name' => $data['account_name'] ?? null, 'cheque_date' => $data['cheque_date'],
                'received_date' => $data['received_date'], 'amount' => Decimal::money($data['amount']), 'status' => 'pending',
                'attachment_path' => $data['attachment_path'] ?? null, 'notes' => $data['notes'] ?? null, 'created_by' => $userId,
            ]);
            $method = PaymentMethod::firstOrCreate(['code' => 'cheque'], ['name' => 'Cheque', 'requires_reference' => true, 'active' => true, 'sort_order' => 20]);
            $payment = $this->payments->customer([
                'customer_id' => $data['customer_id'], 'payment_method_id' => $method->id, 'amount' => $data['amount'],
                'payment_date' => $data['received_date'], 'reference' => $data['cheque_number'], 'notes' => $data['notes'] ?? null,
                'allocation_mode' => $data['allocation_mode'] ?? 'automatic', 'allocations' => $data['allocations'] ?? [],
            ], 'pending', $cheque->id, $userId);
            $this->event($cheque, 'received', "Received from {$payment->customer->name}", ['amount' => $data['amount']], $userId);
            foreach ($payment->allocations as $allocation) {
                $this->event($cheque, 'allocated_customer', "Allocated to {$allocation->sale->invoice_no}", ['amount' => $allocation->amount], $userId);
            }

            return $cheque->load('customer', 'customerPayments.allocations.sale', 'events');
        }, 3);
    }

    public function issue(array $data, ?int $userId = null): Cheque
    {
        return DB::transaction(function () use ($data, $userId) {
            $cheque = Cheque::create([
                'uuid' => Str::uuid(), 'direction' => 'issued', 'source_type' => 'business', 'supplier_id' => $data['supplier_id'],
                'cheque_number' => $data['cheque_number'], 'bank' => $data['bank'], 'branch' => $data['branch'] ?? null,
                'business_bank_account' => $data['business_bank_account'] ?? null, 'account_name' => $data['account_name'] ?? null,
                'cheque_date' => $data['cheque_date'], 'issue_date' => $data['issue_date'], 'amount' => Decimal::money($data['amount']),
                'status' => 'pending', 'attachment_path' => $data['attachment_path'] ?? null, 'notes' => $data['notes'] ?? null, 'created_by' => $userId,
            ]);
            $method = PaymentMethod::firstOrCreate(['code' => 'own_cheque'], ['name' => 'Own Cheque', 'requires_reference' => true, 'active' => true, 'sort_order' => 21]);
            $payment = $this->payments->supplier([
                'supplier_id' => $data['supplier_id'], 'payment_method_id' => $method->id, 'amount' => $data['amount'],
                'payment_date' => $data['issue_date'], 'reference' => $data['cheque_number'], 'notes' => $data['notes'] ?? null,
                'allocation_mode' => $data['allocation_mode'] ?? 'automatic', 'allocations' => $data['allocations'] ?? [],
            ], 'pending', $cheque->id, $userId);
            $this->event($cheque, 'issued', "Issued to {$payment->supplier->name}", ['amount' => $data['amount']], $userId);
            foreach ($payment->allocations as $allocation) {
                $this->event($cheque, 'allocated_supplier', "Allocated to {$allocation->purchase->purchase_no}", ['amount' => $allocation->amount], $userId);
            }

            return $cheque->load('supplier', 'supplierPayments.allocations.purchase', 'events');
        }, 3);
    }

    public function endorse(Cheque $cheque, array $data, ?int $userId = null): Cheque
    {
        return DB::transaction(function () use ($cheque, $data, $userId) {
            $cheque = Cheque::lockForUpdate()->findOrFail($cheque->id);
            if ($cheque->direction !== 'received' || ! in_array($cheque->status, ['pending', 'deposited', 'endorsed'], true)) {
                throw ValidationException::withMessages(['cheque' => 'Only an unprocessed received cheque can be endorsed.']);
            }
            $remaining = Decimal::of($cheque->amount)->minus(Decimal::of($cheque->endorsements()->sum('amount')));
            if (Decimal::of($data['amount'])->isGreaterThan($remaining)) {
                throw ValidationException::withMessages(['amount' => "Only Rs. {$remaining} remains available on this cheque."]);
            }
            $method = PaymentMethod::firstOrCreate(['code' => 'endorsed_cheque'], ['name' => 'Customer Cheque / Endorsed', 'requires_reference' => true, 'active' => true, 'sort_order' => 22]);
            $payment = $this->payments->supplier([
                'supplier_id' => $data['supplier_id'], 'payment_method_id' => $method->id, 'amount' => $data['amount'],
                'payment_date' => $data['transfer_date'], 'reference' => $cheque->cheque_number, 'notes' => $data['notes'] ?? null,
                'allocation_mode' => $data['allocation_mode'] ?? 'automatic', 'allocations' => $data['allocations'] ?? [],
            ], 'pending', $cheque->id, $userId);
            $cheque->endorsements()->create(['supplier_id' => $data['supplier_id'], 'supplier_payment_id' => $payment->id, 'amount' => $data['amount'], 'transfer_date' => $data['transfer_date'], 'notes' => $data['notes'] ?? null, 'created_by' => $userId]);
            $cheque->update(['status' => 'endorsed']);
            $this->event($cheque, 'endorsed', "Endorsed to {$payment->supplier->name}", ['amount' => $data['amount']], $userId);
            foreach ($payment->allocations as $allocation) {
                $this->event($cheque, 'allocated_supplier', "Allocated to {$allocation->purchase->purchase_no}", ['amount' => $allocation->amount], $userId);
            }

            return $cheque->load('endorsements.supplier', 'events');
        }, 3);
    }

    public function deposit(Cheque $cheque, ?int $userId = null): Cheque
    {
        return DB::transaction(function () use ($cheque, $userId) {
            $cheque = Cheque::lockForUpdate()->findOrFail($cheque->id);
            if ($cheque->status === 'deposited') {
                return $cheque;
            }
            if ($cheque->direction !== 'received' || $cheque->status !== 'pending') {
                throw ValidationException::withMessages(['cheque' => 'This cheque cannot be deposited.']);
            }
            $cheque->update(['status' => 'deposited', 'deposited_at' => now()]);
            $this->event($cheque, 'deposited', 'Cheque deposited', [], $userId);

            return $cheque;
        }, 3);
    }

    public function pass(Cheque $cheque, ?string $passDate = null, ?int $userId = null): Cheque
    {
        return DB::transaction(function () use ($cheque, $passDate, $userId) {
            $cheque = Cheque::lockForUpdate()->findOrFail($cheque->id);
            if ($cheque->status === 'passed') {
                return $cheque;
            }
            if (! in_array($cheque->status, ['pending', 'deposited', 'endorsed'], true)) {
                throw ValidationException::withMessages(['cheque' => 'This cheque has already been processed.']);
            }
            if ($cheque->cheque_date->isFuture()) {
                throw ValidationException::withMessages(['cheque' => 'The cheque cannot pass before its cheque date.']);
            }
            foreach ($cheque->customerPayments()->where('status', 'pending')->lockForUpdate()->get() as $payment) {
                $this->payments->clearCustomer($payment, $passDate);
            }
            foreach ($cheque->supplierPayments()->where('status', 'pending')->lockForUpdate()->get() as $payment) {
                $this->payments->clearSupplier($payment, $passDate);
            }
            $cheque->update(['status' => 'passed', 'processed_at' => $passDate ? \Carbon\Carbon::parse($passDate) : now(), 'processed_by' => $userId]);
            $this->event($cheque, 'passed', 'Cheque passed / cleared; all linked pending payments cleared', [], $userId);

            return $cheque->load('events');
        }, 3);
    }

    public function returnCheque(Cheque $cheque, array $data, ?int $userId = null): Cheque
    {
        return DB::transaction(function () use ($cheque, $data, $userId) {
            $cheque = Cheque::lockForUpdate()->findOrFail($cheque->id);
            if ($cheque->status === 'returned') {
                return $cheque;
            }
            if (! in_array($cheque->status, ['pending', 'deposited', 'endorsed'], true)) {
                throw ValidationException::withMessages(['cheque' => 'This cheque has already been processed.']);
            }
            foreach ($cheque->customerPayments()->where('status', 'pending')->lockForUpdate()->get() as $payment) {
                $this->payments->cancelCustomer($payment);
            }
            foreach ($cheque->supplierPayments()->where('status', 'pending')->lockForUpdate()->get() as $payment) {
                $this->payments->cancelSupplier($payment);
            }
            $charge = Decimal::money($data['bank_charge'] ?? 0);
            if ($cheque->customer && ($data['charge_customer'] ?? false) && Decimal::of($charge)->isGreaterThan(0)) {
                $this->ledger->customer($cheque->customer, 'cheque_return_charge', $charge, 0, $cheque, 'Cheque return bank charge');
            }
            $cheque->update(['status' => 'returned', 'processed_at' => now(), 'processed_by' => $userId, 'return_date' => $data['return_date'] ?? now()->toDateString(), 'return_reason' => $data['return_reason'] ?? null, 'bank_charge' => $charge, 'charge_customer' => (bool) ($data['charge_customer'] ?? false), 'notes' => trim($cheque->notes."\n".($data['notes'] ?? ''))]);
            $this->event($cheque, 'returned', 'Cheque returned; linked pending payments cancelled and invoice/purchase dues restored', ['reason' => $data['return_reason'] ?? null, 'bank_charge' => $charge], $userId);

            return $cheque->load('events');
        }, 3);
    }

    public function cancel(Cheque $cheque, ?int $userId = null): Cheque
    {
        return DB::transaction(function () use ($cheque, $userId) {
            $cheque = Cheque::lockForUpdate()->findOrFail($cheque->id);
            if ($cheque->status === 'cancelled') {
                return $cheque;
            }
            if ($cheque->status !== 'pending') {
                throw ValidationException::withMessages(['cheque' => 'Only a pending, undeposited and unendorsed cheque can be cancelled.']);
            }
            foreach ($cheque->customerPayments()->where('status', 'pending')->lockForUpdate()->get() as $payment) {
                $this->payments->cancelCustomer($payment);
            }
            foreach ($cheque->supplierPayments()->where('status', 'pending')->lockForUpdate()->get() as $payment) {
                $this->payments->cancelSupplier($payment);
            }
            $cheque->update(['status' => 'cancelled', 'processed_at' => now(), 'processed_by' => $userId]);
            $this->event($cheque, 'cancelled', 'Cheque cancelled; linked pending allocations released', [], $userId);

            return $cheque->load('events');
        }, 3);
    }

    private function event(Cheque $cheque, string $type, string $description, array $metadata, ?int $userId): void
    {
        $cheque->events()->create(['event_type' => $type, 'description' => $description, 'metadata' => $metadata ?: null, 'occurred_at' => now(), 'user_id' => $userId]);
    }
}
