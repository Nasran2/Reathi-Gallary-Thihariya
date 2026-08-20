<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\SupplierPayment;
use App\Services\ChequeService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    public function index(Request $r)
    {
        $records = Supplier::query()->withCount('purchases')->withSum('purchases as purchases_total', 'supplier_total')
            ->addSelect(['last_purchase_at' => Purchase::select('purchase_date')->whereColumn('supplier_id', 'suppliers.id')->latest('purchase_date')->limit(1)])
            ->when($r->q, fn ($q, $v) => $q->where(fn ($x) => $x->where('name', 'like', "%{$v}%")->orWhere('company', 'like', "%{$v}%")->orWhere('phone', 'like', "%{$v}%")))
            ->when($r->status && $r->status !== 'all', fn ($q) => $q->where('active', $r->status === 'active'))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('suppliers.index', compact('records'));
    }

    public function store(Request $r)
    {
        $supplier = Supplier::create($this->validated($r) + ['active' => true]);

        if ($r->wantsJson()) {
            return response()->json(['success' => true, 'supplier' => $supplier]);
        }

        return back()->with('success', 'Supplier added.');
    }

    public function show(Request $r, Supplier $supplier)
    {
        $tab = $r->get('tab', 'all');
        $stats = ['purchases' => $supplier->purchases()->sum(DB::raw('supplier_total - returned_total')), 'paid' => $supplier->purchases()->sum('paid_total'), 'pending' => $supplier->payments()->where('status', 'pending')->sum('amount'), 'due' => max(0, (float) $supplier->balance - (float) $supplier->pending_balance), 'returns' => $supplier->returns()->sum('return_total')];
        $rows = match ($tab) {
            'purchases' => Purchase::where('supplier_id', $supplier->id)->latest('purchase_date')->paginate(20),
            'payments' => SupplierPayment::with('method', 'cheque')->where('supplier_id', $supplier->id)->latest('payment_date')->paginate(20),
            'cheques' => Cheque::where(fn ($q) => $q->where('supplier_id', $supplier->id)->orWhereHas('endorsements', fn ($e) => $e->where('supplier_id', $supplier->id)))->latest('cheque_date')->paginate(20),
            'returns' => PurchaseReturn::with('purchase')->where('supplier_id', $supplier->id)->latest('return_date')->paginate(20),
            default => SupplierLedger::with('reference')->where('supplier_id', $supplier->id)->latest('occurred_at')->paginate(30),
        };
        $unpaidPurchases = $supplier->purchases()->where('due_total', '>', 0)->oldest('purchase_date')->get();
        $methods = PaymentMethod::where('active', true)->whereNotIn('code', ['credit_due', 'cheque'])->orderBy('sort_order')->get();
        $eligibleCheques = Cheque::with('customer')->where('direction', 'received')->whereIn('status', ['pending', 'endorsed'])->get()->filter(fn ($c) => (float) $c->remaining_amount > 0);

        return view('suppliers.show', compact('supplier', 'stats', 'rows', 'tab', 'unpaidPurchases', 'methods', 'eligibleCheques'));
    }

    public function update(Request $r, Supplier $supplier)
    {
        $supplier->update($this->validated($r));

        return back()->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchases()->exists() || $supplier->ledger()->exists()) {
            $supplier->update(['active' => false]);

            return back()->with('success', 'Supplier has transactions and was deactivated.');
        } $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted.');
    }

    public function toggle(Supplier $supplier)
    {
        $supplier->update(['active' => ! $supplier->active]);

        return back()->with('success', $supplier->active ? 'Supplier activated.' : 'Supplier deactivated.');
    }

    public function pay(Request $r, Supplier $supplier, PaymentService $payments, ChequeService $cheques)
    {
        $data = $r->validate(['amount' => 'required|numeric|gt:0', 'payment_date' => 'required|date', 'payment_method_id' => 'required|exists:payment_methods,id', 'allocation_mode' => 'required|in:automatic,manual', 'allocations' => 'nullable|array', 'allocations.*' => 'nullable|numeric|min:0', 'reference' => 'nullable|max:150', 'notes' => 'nullable', 'cheque_id' => 'nullable|exists:cheques,id', 'cheque_number' => 'nullable|max:80', 'bank' => 'nullable|max:120', 'business_bank_account' => 'nullable|max:150', 'cheque_date' => 'nullable|date', 'attachment' => 'nullable|file|max:5120']);
        $method = PaymentMethod::findOrFail($data['payment_method_id']);
        if (in_array($method->code, ['credit_due', 'cheque'], true)) {
            throw ValidationException::withMessages(['payment_method_id' => 'This payment method is not valid for a supplier payment.']);
        }
        if ($method->code === 'own_cheque') {
            $r->validate(['cheque_number' => 'required', 'bank' => 'required', 'business_bank_account' => 'required', 'cheque_date' => 'required|date']);
            $path = $r->file('attachment')?->store('cheques', 'public');
            $cheques->issue($data + ['supplier_id' => $supplier->id, 'issue_date' => $data['payment_date'], 'attachment_path' => $path], $r->user()->id);
        } elseif ($method->code === 'endorsed_cheque') {
            $r->validate(['cheque_id' => 'required|exists:cheques,id']);
            $cheques->endorse(Cheque::findOrFail($data['cheque_id']), $data + ['supplier_id' => $supplier->id, 'transfer_date' => $data['payment_date']], $r->user()->id);
        } else {
            $payments->supplier($data + ['supplier_id' => $supplier->id], 'cleared', null, $r->user()->id);
        }

        return back()->with('success', in_array($method->code, ['own_cheque', 'endorsed_cheque']) ? 'Cheque payment recorded as pending.' : 'Supplier payment recorded and allocated.');
    }

    private function validated(Request $r): array
    {
        return $r->validate(['name' => 'required|max:150', 'company' => 'nullable|max:150', 'phone' => 'nullable|max:30', 'whatsapp' => 'nullable|max:30', 'email' => 'nullable|email', 'address' => 'nullable', 'tax_number' => 'nullable|max:100', 'opening_balance' => 'nullable|numeric', 'notes' => 'nullable']);
    }
}
