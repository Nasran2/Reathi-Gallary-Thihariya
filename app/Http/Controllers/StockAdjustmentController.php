<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Store;
use App\Services\InventoryService;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function index(Request $r)
    {
        $this->authorize('inventory.adjust');
        $adjustments = StockAdjustment::with('user')->withCount('items')
            ->when($r->start_date, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($r->end_date, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($r->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->latest()
            ->paginate(20)
            ->withQueryString();
            
        $users = \App\Models\User::orderBy('name')->get();
            
        return view('inventory.adjustments.index', compact('adjustments', 'users'));
    }

    public function report(Request $r)
    {
        $this->authorize('inventory.adjust');
        $items = \App\Models\StockAdjustmentItem::with(['adjustment.user', 'product', 'store', 'unit'])
            ->when($r->start_date, fn($q, $v) => $q->whereHas('adjustment', fn($q2) => $q2->whereDate('created_at', '>=', $v)))
            ->when($r->end_date, fn($q, $v) => $q->whereHas('adjustment', fn($q2) => $q2->whereDate('created_at', '<=', $v)))
            ->when($r->user_id, fn($q, $v) => $q->whereHas('adjustment', fn($q2) => $q2->where('user_id', $v)))
            ->when($r->store_id, fn($q, $v) => $q->where('store_id', $v))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $users = \App\Models\User::orderBy('name')->get();
        $stores = \App\Models\Store::orderBy('name')->get();

        return view('inventory.adjustments.report', compact('items', 'users', 'stores'));
    }

    public function create()
    {
        $this->authorize('inventory.adjust');
        return view('inventory.adjustments.edit', [
            'products' => Product::with('productUnits.unit')->where('active', 1)->get(),
            'stores' => Store::where('active', 1)->get(),
            'adjustment' => null
        ]);
    }

    public function store(Request $r, InventoryService $service)
    {
        $this->authorize('inventory.adjust');
        
        if ($r->has('items_json')) {
            $items = json_decode($r->input('items_json'), true);
            if (is_array($items)) {
                $r->merge(['items' => $items]);
            }
        }
        
        $d = $r->validate([
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.store_id' => 'required|exists:stores,id',
            'items.*.inventory_type' => 'required|in:main,remnant',
            'items.*.direction' => 'required|in:increase,decrease',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.reason' => 'required|max:120',
            'items.*.notes' => 'nullable|string|max:255'
        ]);

        DB::transaction(function () use ($d, $service, $r) {
            $adjustment = StockAdjustment::create([
                'user_id' => $r->user()->id,
                'notes' => $d['notes'] ?? null
            ]);

            foreach ($d['items'] as $item) {
                $adjItem = $adjustment->items()->create($item);

                $p = Product::findOrFail($item['product_id']);
                $u = $p->productUnits()->where('unit_id', $item['unit_id'])->firstOrFail();
                $base = Decimal::mul($item['quantity'], $u->conversion_rate, 6);
                $notes = $item['reason'] . ($item['notes'] ? ': ' . $item['notes'] : '');
                
                $service->move(
                    $p, 
                    $item['store_id'], 
                    $item['inventory_type'], 
                    'stock_adjustment', 
                    $item['direction'] === 'increase' ? $base : 0, 
                    $item['direction'] === 'decrease' ? $base : 0, 
                    $p->average_cost, 
                    $adjustment, 
                    $r->user()->id, 
                    $notes
                );
            }
        });

        return redirect()->route('inventory.adjustments.index')->with('success', count($d['items']) . ' adjustments posted to the stock ledger.');
    }

    public function show(StockAdjustment $adjustment)
    {
        $this->authorize('inventory.adjust');
        $adjustment->load('items.product.baseUnit', 'items.store', 'items.unit', 'user');
        return view('inventory.adjustments.show', compact('adjustment'));
    }

    public function edit(StockAdjustment $adjustment)
    {
        $this->authorize('inventory.adjust');
        $adjustment->load('items');
        return view('inventory.adjustments.edit', [
            'products' => Product::with('productUnits.unit')->where('active', 1)->get(),
            'stores' => Store::where('active', 1)->get(),
            'adjustment' => $adjustment
        ]);
    }

    public function update(Request $r, StockAdjustment $adjustment, InventoryService $service)
    {
        $this->authorize('inventory.adjust');

        if ($r->has('items_json')) {
            $items = json_decode($r->input('items_json'), true);
            if (is_array($items)) {
                $r->merge(['items' => $items]);
            }
        }
        
        $d = $r->validate([
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.store_id' => 'required|exists:stores,id',
            'items.*.inventory_type' => 'required|in:main,remnant',
            'items.*.direction' => 'required|in:increase,decrease',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.reason' => 'required|max:120',
            'items.*.notes' => 'nullable|string|max:255'
        ]);

        DB::transaction(function () use ($d, $adjustment, $service, $r) {
            // First, reverse the existing items
            foreach ($adjustment->items as $item) {
                $p = $item->product;
                $u = $p->productUnits()->where('unit_id', $item->unit_id)->firstOrFail();
                $base = Decimal::mul($item->quantity, $u->conversion_rate, 6);
                
                $service->move(
                    $p, 
                    $item->store_id, 
                    $item->inventory_type, 
                    'stock_adjustment_reversal', 
                    $item->direction === 'decrease' ? $base : 0, 
                    $item->direction === 'increase' ? $base : 0, 
                    $p->average_cost, 
                    $adjustment, 
                    $r->user()->id, 
                    'Reversal of adjustment #' . $adjustment->id
                );
            }

            // Delete old items
            $adjustment->items()->delete();

            // Update parent notes
            $adjustment->update(['notes' => $d['notes'] ?? null]);

            // Create new items and process movements
            foreach ($d['items'] as $item) {
                $adjItem = $adjustment->items()->create($item);

                $p = Product::findOrFail($item['product_id']);
                $u = $p->productUnits()->where('unit_id', $item['unit_id'])->firstOrFail();
                $base = Decimal::mul($item['quantity'], $u->conversion_rate, 6);
                $notes = $item['reason'] . ($item['notes'] ? ': ' . $item['notes'] : '');
                
                $service->move(
                    $p, 
                    $item['store_id'], 
                    $item['inventory_type'], 
                    'stock_adjustment', 
                    $item['direction'] === 'increase' ? $base : 0, 
                    $item['direction'] === 'decrease' ? $base : 0, 
                    $p->average_cost, 
                    $adjustment, 
                    $r->user()->id, 
                    $notes
                );
            }
        });

        return redirect()->route('inventory.adjustments.index')->with('success', 'Adjustment transaction updated successfully.');
    }

    public function destroy(StockAdjustment $adjustment, Request $r, InventoryService $service)
    {
        $this->authorize('inventory.adjust');

        DB::transaction(function () use ($adjustment, $service, $r) {
            // Reverse the existing items
            foreach ($adjustment->items as $item) {
                $p = $item->product;
                $u = $p->productUnits()->where('unit_id', $item->unit_id)->firstOrFail();
                $base = Decimal::mul($item->quantity, $u->conversion_rate, 6);
                
                $service->move(
                    $p, 
                    $item->store_id, 
                    $item->inventory_type, 
                    'stock_adjustment_reversal', 
                    $item->direction === 'decrease' ? $base : 0, 
                    $item->direction === 'increase' ? $base : 0, 
                    $p->average_cost, 
                    $adjustment, 
                    $r->user()->id, 
                    'Reversal of adjustment #' . $adjustment->id
                );
            }
            // Delete the adjustment (which cascades items)
            $adjustment->delete();
        });

        return redirect()->route('inventory.adjustments.index')->with('success', 'Adjustment transaction deleted and stock rolled back.');
    }
}
