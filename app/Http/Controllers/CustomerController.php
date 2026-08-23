<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\CustomerPayment;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SmsLog;
use App\Services\ChequeService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function index(Request $r)
    {
        $query = Customer::query()->withCount(['sales', 'ledger', 'payments'])->withSum('sales as sales_total', 'grand_total')
            ->withSum('sales as sales_paid', 'paid_total')
            ->withSum(['payments as pending_sum' => fn ($q) => $q->where('status', 'pending')], 'amount')
            ->addSelect(['last_purchase_at' => Sale::select('sold_at')->whereColumn('customer_id', 'customers.id')->latest('sold_at')->limit(1)])
            ->when($r->q, fn ($q, $v) => $q->where(fn ($x) => $x->where('name', 'like', "%{$v}%")->orWhere('mobile', 'like', "%{$v}%")))
            ->when($r->status && $r->status !== 'all', fn ($q) => $q->where('active', $r->status === 'active'))
            ->when($r->from, fn ($q, $v) => $q->whereHas('sales', fn ($s) => $s->whereDate('sold_at', '>=', $v)))
            ->when($r->to, fn ($q, $v) => $q->whereHas('sales', fn ($s) => $s->whereDate('sold_at', '<=', $v)));
        if ($r->inactive_for) {
            $days = ['30_days' => 30, '3_months' => 90, '6_months' => 180, '1_year' => 365][$r->inactive_for] ?? 0;
            if ($days) {
                $query->where(fn ($q) => $q->whereDoesntHave('sales')->orWhereDoesntHave('sales', fn ($s) => $s->where('sold_at', '>=', now()->subDays($days))));
            }
        }
        if ($r->boolean('due_only')) {
            $query->whereRaw("COALESCE((SELECT balance_after FROM customer_ledger WHERE customer_id = customers.id ORDER BY id DESC LIMIT 1), opening_balance) - COALESCE((SELECT SUM(amount) FROM customer_payments WHERE customer_id = customers.id AND status = 'pending'), 0) > 0");
        }
        $records = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('customers.index', compact('records'));
    }

    public function store(Request $r)
    {
        $customer = Customer::create($this->validated($r) + ['active' => true]);

        if ($r->wantsJson() || $r->ajax()) {
            return response()->json($customer);
        }

        return back()->with('success', 'Customer added.');
    }

    public function show(Request $r, Customer $customer)
    {
        $tab = $r->get('tab', 'all');
        $stats = [
            'bills' => $customer->sales()->count(),
            'purchases' => $customer->sales()->sum(DB::raw('grand_total - returned_total')),
            'paid' => $customer->sales()->sum('paid_total'),
            'pending' => $customer->payments()->where('status', 'pending')->sum('amount'),
            'due' => max(0, (float) $customer->balance - (float) $customer->pending_balance),
            'returns' => $customer->returns()->sum('return_total'),
        ];
        $rows = match ($tab) {
            'bills', 'due' => Sale::where('customer_id', $customer->id)->when($tab === 'due', fn ($q) => $q->where('due_total', '>', 0))->latest('sold_at')->paginate(20),
            'payments' => CustomerPayment::with('method', 'cheque')->where('customer_id', $customer->id)->latest('payment_date')->paginate(20),
            'cheques' => Cheque::where('customer_id', $customer->id)->latest('received_date')->paginate(20),
            'returns' => SaleReturn::with('sale')->where('customer_id', $customer->id)->latest('return_date')->paginate(20),
            'sms' => SmsLog::where('customer_id', $customer->id)->latest()->paginate(20),
            default => CustomerLedger::with('reference')->where('customer_id', $customer->id)->latest('occurred_at')->paginate(30),
        };
        $unpaidSales = $customer->sales()->where('due_total', '>', 0)->oldest('sold_at')->get();
        $methods = PaymentMethod::where('active', true)->whereNotIn('code', ['credit_due', 'own_cheque', 'endorsed_cheque'])->orderBy('sort_order')->get();

        return view('customers.show', compact('customer', 'stats', 'rows', 'tab', 'unpaidSales', 'methods'));
    }

    public function update(Request $r, Customer $customer)
    {
        $customer->update($this->validated($r, $customer));

        return back()->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->is_walk_in) {
            return back()->withErrors(['customer' => 'Walk-in Customer cannot be deleted.']);
        }
        if ($customer->sales()->exists() || $customer->ledger()->exists() || $customer->payments()->exists()) {
            $customer->update(['active' => false]);

            return back()->with('success', 'Customer has transactions and was deactivated instead of deleted.');
        }
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }

    public function toggle(Customer $customer)
    {
        if ($customer->is_walk_in && $customer->active) {
            return back()->withErrors(['customer' => 'Walk-in Customer must remain active.']);
        }
        $customer->update(['active' => ! $customer->active]);

        return back()->with('success', $customer->active ? 'Customer activated.' : 'Customer deactivated.');
    }

    public function payDue(Request $r, Customer $customer, PaymentService $payments, ChequeService $cheques)
    {
        $data = $r->validate([
            'amount' => 'required|numeric|gt:0', 'payment_date' => 'required|date', 'payment_method_id' => 'required|exists:payment_methods,id',
            'allocation_mode' => 'required|in:automatic,manual', 'allocations' => 'nullable|array', 'allocations.*' => 'nullable|numeric|min:0',
            'reference' => 'nullable|max:150', 'notes' => 'nullable', 'cheque_number' => 'nullable|required_if:is_cheque,1|max:80',
            'bank' => 'nullable|required_if:is_cheque,1|max:120', 'branch' => 'nullable|max:120', 'account_name' => 'nullable|max:150',
            'cheque_date' => 'nullable|required_if:is_cheque,1|date', 'attachment' => 'nullable|file|max:5120',
        ]);
        $method = PaymentMethod::findOrFail($data['payment_method_id']);
        if (in_array($method->code, ['credit_due', 'own_cheque', 'endorsed_cheque'], true)) {
            throw ValidationException::withMessages(['payment_method_id' => 'This payment method is not valid for a customer receipt.']);
        }
        if ($method->code === 'cheque') {
            $r->validate(['cheque_number' => 'required|max:80', 'bank' => 'required|max:120', 'cheque_date' => 'required|date']);
            $path = $r->file('attachment')?->store('cheques', 'public');
            $cheques->receive($data + ['customer_id' => $customer->id, 'received_date' => $data['payment_date'], 'attachment_path' => $path], $r->user()->id);
        } else {
            $payments->customer($data + ['customer_id' => $customer->id], 'cleared', null, $r->user()->id);
        }

        return back()->with('success', $method->code === 'cheque' ? 'Cheque recorded as pending. It will not count as cleared until passed.' : 'Payment recorded and allocated.');
    }

    private function validated(Request $r, ?Customer $customer = null): array
    {
        $id = $customer ? $customer->id : null;
        return $r->validate([
            'name' => 'required|max:150', 
            'mobile' => 'nullable|max:30|unique:customers,mobile,' . $id, 
            'whatsapp' => 'nullable|max:30', 
            'email' => 'nullable|email', 
            'address' => 'nullable', 
            'credit_limit' => 'nullable|numeric|min:0', 
            'opening_balance' => 'nullable|numeric', 
            'notes' => 'nullable'
        ]);
    }
}
