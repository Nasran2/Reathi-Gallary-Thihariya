<?php

namespace App\Http\Controllers;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Remnant;
use App\Models\Store;
use App\Services\InventoryService;
use App\Services\RemnantService;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $r)
    {
        $type = $r->get('type', 'main');
        $balances = InventoryBalance::with('product.baseUnit')->where('inventory_type', $type)->when($r->q, fn ($q, $v) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%$v%")->orWhere('sku', 'like', "%$v%")))->paginate(25)->withQueryString();

        return view('inventory.index', compact('balances', 'type'));
    }

    public function movements(Request $r)
    {
        $movements = InventoryMovement::with(['product.baseUnit', 'user'])->when($r->type, fn ($q, $v) => $q->where('inventory_type', $v))->latest()->paginate(30)->withQueryString();

        return view('inventory.movements', compact('movements'));
    }

    public function adjustmentForm()
    {
        return view('inventory.adjust', ['products' => Product::with('productUnits.unit')->where('active', 1)->get(), 'stores' => Store::where('active', 1)->get()]);
    }

    public function adjust(Request $r, InventoryService $service)
    {
        $this->authorize('adjust-stock');
        $d = $r->validate(['product_id' => 'required|exists:products,id', 'store_id' => 'required|exists:stores,id', 'inventory_type' => 'required|in:main,remnant', 'direction' => 'required|in:increase,decrease', 'quantity' => 'required|numeric|gt:0', 'unit_id' => 'required|exists:units,id', 'reason' => 'required|max:120', 'notes' => 'nullable']);
        DB::transaction(function () use ($d, $service, $r) {
            $p = Product::findOrFail($d['product_id']);
            $u = $p->productUnits()->where('unit_id', $d['unit_id'])->firstOrFail();
            $base = Decimal::mul($d['quantity'], $u->conversion_rate, 6);
            $service->move($p, $d['store_id'], $d['inventory_type'], 'stock_adjustment', $d['direction'] === 'increase' ? $base : 0, $d['direction'] === 'decrease' ? $base : 0, $p->average_cost, null, $r->user()->id, $d['reason'].': '.($d['notes'] ?? ''));
        });

        return redirect()->route('inventory.movements')->with('success', 'Adjustment posted to the stock ledger.');
    }

    public function remnants()
    {
        return view('inventory.remnants', ['remnants' => Remnant::with(['product', 'unit'])->latest()->paginate(25)]);
    }

    public function transferForm()
    {
        return view('inventory.transfer', ['products' => Product::with(['productUnits.unit', 'balances'])->where('active', 1)->get(), 'stores' => Store::where('active', 1)->get()]);
    }

    public function transfer(Request $r, RemnantService $service)
    {
        $this->authorize('transfer-remnant');
        $d = $r->validate(['product_id' => 'required|exists:products,id', 'store_id' => 'required|exists:stores,id', 'unit_id' => 'required|exists:units,id', 'quantity' => 'required|numeric|gt:0', 'remnant_price' => 'required|numeric|min:0', 'discount_percent' => 'nullable|numeric|min:0|max:100', 'reason' => 'required|max:120', 'notes' => 'nullable']);
        $remnant = $service->transfer($d, $r->user()->id);

        return redirect()->route('remnants.index')->with('success',"{$remnant->remnant_no} created without changing combined physical stock.");
    }
}
