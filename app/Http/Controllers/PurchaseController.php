<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\ChequeService;
use App\Services\PaymentService;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index()
    {
        return view('purchases.index', ['purchases' => Purchase::with('supplier')->latest()->paginate(20)]);
    }

    public function create()
    {
        $methods = PaymentMethod::where('active', true)->whereNotIn('code', ['credit_due', 'cheque'])->orderBy('sort_order')->get();
        $eligibleCheques = Cheque::with('customer')->where('direction', 'received')->whereIn('status', ['pending', 'endorsed'])->get()->filter(fn ($c) => (float) $c->remaining_amount > 0);
        return view('purchases.form', [
            'suppliers' => Supplier::where('active', 1)->get(), 
            'stores' => Store::where('active', 1)->get(), 
            'products' => Product::with('productUnits.unit', 'suppliers')->where('active', 1)->get(),
            'methods' => $methods,
            'eligibleCheques' => $eligibleCheques,
            'categories' => \App\Models\Category::where('active', 1)->get(),
            'baseUnits' => \App\Models\Unit::where('active', 1)->get()
        ]);
    }

    public function store(Request $r, PurchaseService $service, PaymentService $payments, ChequeService $cheques)
    {
        $this->authorize('manage-purchases');
        $data = $r->validate([
            'supplier_id' => 'required|exists:suppliers,id', 'store_id' => 'required|exists:stores,id', 
            'supplier_invoice_no' => 'nullable|max:100', 'reference_no' => 'nullable|max:100', 
            'purchase_date' => 'required|date', 'due_date' => 'nullable|date', 
            'extra_cost_total' => 'nullable|numeric|min:0', 'notes' => 'nullable', 
            'items' => 'required|array|min:1', 'items.*.product_id' => 'required|exists:products,id', 
            'items.*.unit_id' => 'required|exists:units,id', 'items.*.quantity' => 'required|numeric|gt:0', 
            'items.*.supplier_unit_cost' => 'required|numeric|min:0', 'items.*.discount_amount' => 'nullable|numeric|min:0', 
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.payment_method_id' => 'required|exists:payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.reference' => 'nullable|max:150',
            'payments.*.cheque_number' => 'nullable|max:80',
            'payments.*.bank' => 'nullable|max:120',
            'payments.*.business_bank_account' => 'nullable|max:150',
            'payments.*.cheque_date' => 'nullable|date',
            'payments.*.cheque_id' => 'nullable|exists:cheques,id',
        ]);
        
        $purchase = $service->receive($data, $r->user()->id);

        foreach ($r->input('payments', []) as $payment) {
            if (empty($payment['amount']) || $payment['amount'] <= 0) {
                continue;
            }
            $method = PaymentMethod::find($payment['payment_method_id']);
            if (!$method) continue;
            
            $paymentData = $payment + [
                'supplier_id' => $purchase->supplier_id,
                'payment_date' => $data['purchase_date'],
                'allocation_mode' => 'manual',
                'allocations' => [$purchase->id => $payment['amount']],
            ];
            
            if ($method->code === 'own_cheque') {
                $cheques->issue($paymentData + ['issue_date' => $data['purchase_date']], $r->user()->id);
            } elseif ($method->code === 'endorsed_cheque') {
                $cheques->endorse(Cheque::findOrFail($payment['cheque_id']), $paymentData + ['transfer_date' => $data['purchase_date']], $r->user()->id);
            } else {
                $payments->supplier($paymentData, 'cleared', null, $r->user()->id);
            }
        }

        return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase received and stock updated.');
    }

    public function show(Purchase $purchase)
    {
        return view('purchases.show', ['purchase' => $purchase->load('supplier', 'store', 'items.product', 'items.unit')]);
    }

    public function destroy(Purchase $purchase, PurchaseService $service)
    {
        $this->authorize('manage-purchases');
        try {
            $service->delete($purchase);
            return redirect()->route('purchases.index')->with('success', 'Purchase deleted and stock reverted.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('purchases.index')->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->route('purchases.index')->with('error', 'Could not delete purchase: ' . $e->getMessage());
        }
    }
}
