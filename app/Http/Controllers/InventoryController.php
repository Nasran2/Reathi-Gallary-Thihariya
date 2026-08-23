<?php

namespace App\Http\Controllers;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Remnant;
use App\Models\SaleItem;
use App\Models\Store;
use App\Services\InventoryService;
use App\Services\RemnantService;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryController extends Controller
{
    public function index(Request $r)
    {
        $type = $r->get('type', 'main') === 'remnant' ? 'remnant' : 'main';
        $canViewCost = $r->user()->can('inventory.view_cost');
        $query = InventoryBalance::with(['product.category', 'product.brand', 'product.baseUnit', 'product.defaultSupplier'])
            ->where('inventory_type', $type);

        $query->when($r->filled('q'), function ($query) use ($r) {
            $term = $r->string('q')->trim();
            $query->whereHas('product', fn ($product) => $product
                ->where(fn ($match) => $match->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%")
                    ->orWhereHas('category', fn ($category) => $category->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('suppliers', fn ($supplier) => $supplier->where('suppliers.name', 'like', "%{$term}%"))));
        });
        $query->when($r->filled('category_id'), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('category_id', $r->integer('category_id'))));
        $query->when($r->filled('brand_id'), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('brand_id', $r->integer('brand_id'))));
        $query->when($r->filled('supplier_id'), fn ($q) => $q->whereHas('product.suppliers', fn ($s) => $s->where('suppliers.id', $r->integer('supplier_id'))));
        $query->when($r->filled('unit_id'), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('base_unit_id', $r->integer('unit_id'))));
        $query->when($r->filled('product_status'), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('active', $r->input('product_status') === 'active')));
        $query->when($r->filled('min_qty'), fn ($q) => $q->where('quantity', '>=', $r->input('min_qty')));
        $query->when($r->filled('max_qty'), fn ($q) => $q->where('quantity', '<=', $r->input('max_qty')));

        match ($r->input('stock_status')) {
            'in_stock' => $query->where('quantity', '>', 0),
            'low_stock' => $query->where('quantity', '>', 0)->whereHas('product', fn ($p) => $p->whereRaw('inventory_balances.quantity <= products.reorder_level')),
            'out_of_stock' => $query->where('quantity', '=', 0),
            'negative' => $query->where('quantity', '<', 0),
            default => null,
        };

        if ($canViewCost) {
            $query->when($r->filled('min_cost'), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('average_cost', '>=', $r->input('min_cost'))));
            $query->when($r->filled('max_cost'), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('average_cost', '<=', $r->input('max_cost'))));
        }
        $query->when($r->filled('min_price'), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('main_selling_price', '>=', $r->input('min_price'))));
        $query->when($r->filled('max_price'), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('main_selling_price', '<=', $r->input('max_price'))));

        if ($r->filled('last_sale')) {
            $cutoff = match ($r->input('last_sale')) {
                '30_days' => now()->subDays(30), '3_months' => now()->subMonths(3),
                '6_months' => now()->subMonths(6), '12_months' => now()->subYear(), default => null,
            };
            if ($r->input('last_sale') === 'never') {
                $query->whereDoesntHave('product.saleItems.sale');
            } elseif ($cutoff) {
                $query->whereHas('product.saleItems.sale', fn ($sale) => $sale->where('sold_at', '>=', $cutoff));
            }
        }

        $sort = $r->input('sort', 'product');
        $direction = $r->input('direction') === 'desc' ? 'desc' : 'asc';
        if ($sort === 'quantity') {
            $query->orderBy('quantity', $direction);
        } else {
            $column = in_array($sort, ['sku', 'average_cost', 'main_selling_price'], true) ? $sort : 'name';
            $query->orderBy(Product::select($column)->whereColumn('products.id', 'inventory_balances.product_id'), $direction);
        }

        if (in_array($r->input('export'), ['csv', 'pdf'], true)) {
            $rows = $query->get();
            $this->attachLastSales($rows);
            if ($r->input('export') === 'pdf') {
                return Pdf::loadView('inventory.pdf', compact('rows', 'type', 'canViewCost'))->setPaper('a4', 'landscape')
                    ->download("{$type}_inventory_".now()->format('Ymd_His').'.pdf');
            }
            return response()->streamDownload(function () use ($rows, $canViewCost) {
                $out = fopen('php://output', 'w');
                $headings = ['Product', 'SKU', 'Category', 'Brand', 'Base Unit', 'Quantity'];
                if ($canViewCost) array_push($headings, 'Average Cost', 'Stock Cost Value');
                array_push($headings, 'Selling Price', 'Selling Value', 'Minimum Stock', 'Last Sale');
                fputcsv($out, $headings);
                foreach ($rows as $row) {
                    $data = [$row->product->name, $row->product->sku, $row->product->category?->name, $row->product->brand?->name, $row->product->baseUnit?->symbol, $row->quantity];
                    if ($canViewCost) array_push($data, $row->product->average_cost, (string) Decimal::mul($row->quantity, $row->product->average_cost, 4));
                    array_push($data, $row->product->main_selling_price, (string) Decimal::mul($row->quantity, $row->product->main_selling_price, 4), $row->product->minimum_stock, $row->product->last_sale_at);
                    fputcsv($out, $data);
                }
                fclose($out);
            }, "{$type}_inventory_".now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv']);
        }

        $balances = $query->paginate(25)->withQueryString();
        $this->attachLastSales($balances->getCollection());

        return view('inventory.index', [
            'balances' => $balances, 'type' => $type, 'canViewCost' => $canViewCost,
            'categories' => \App\Models\Category::orderBy('name')->get(),
            'brands' => \App\Models\Brand::orderBy('name')->get(),
            'suppliers' => \App\Models\Supplier::orderBy('name')->get(),
            'units' => \App\Models\Unit::orderBy('name')->get(),
        ]);
    }

    private function attachLastSales($rows): void
    {
        $lastSales = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sale_items.product_id', collect($rows)->pluck('product_id'))
            ->where('sales.status', 'completed')
            ->selectRaw('sale_items.product_id, MAX(sales.sold_at) as sold_at')
            ->groupBy('sale_items.product_id')
            ->pluck('sold_at', 'sale_items.product_id');
        foreach ($rows as $row) {
            $row->product->last_sale_at = $lastSales[$row->product_id] ?? null;
        }
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
        $this->authorize('inventory.adjust');
        
        if ($r->has('items_json')) {
            $items = json_decode($r->input('items_json'), true);
            if (is_array($items)) {
                $r->merge(['items' => $items]);
            }
        }
        
        \Log::info('Adjust form submitted:', $r->all());
        
        $d = $r->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.store_id' => 'required|exists:stores,id',
            'items.*.inventory_type' => 'required|in:main,remnant',
            'items.*.direction' => 'required|in:increase,decrease',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.reason' => 'required|max:120',
            'items.*.notes' => 'nullable'
        ]);

        DB::transaction(function () use ($d, $service, $r) {
            foreach ($d['items'] as $item) {
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
                    null, 
                    $r->user()->id, 
                    $notes
                );
            }
        });

        return redirect()->route('inventory.movements')->with('success', count($d['items']) . ' adjustments posted to the stock ledger.');
    }

    public function remnants(Request $r)
    {
        $canViewCost = $r->user()->can('inventory.view_cost');
        $query = Remnant::with(['product.category', 'unit'])->latest();
        $query->when($r->filled('q'), function ($query) use ($r) {
            $term = $r->string('q')->trim();
            $query->where(fn ($match) => $match->where('remnant_no', 'like', "%{$term}%")
                ->orWhere('barcode', 'like', "%{$term}%")
                ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")->orWhere('barcode', 'like', "%{$term}%")));
        });
        $query->when($r->filled('product_id'), fn ($q) => $q->where('product_id', $r->integer('product_id')));
        $query->when($r->filled('category_id'), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('category_id', $r->integer('category_id'))));
        $query->when($r->filled('status'), fn ($q) => $q->where('status', $r->input('status')));
        $query->when($r->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $r->input('from')));
        $query->when($r->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $r->input('to')));
        $query->when($r->filled('min_qty'), fn ($q) => $q->where('remaining_quantity', '>=', $r->input('min_qty')));
        $query->when($r->filled('max_qty'), fn ($q) => $q->where('remaining_quantity', '<=', $r->input('max_qty')));
        $query->when($r->filled('min_price'), fn ($q) => $q->where('remnant_price', '>=', $r->input('min_price')));
        $query->when($r->filled('max_price'), fn ($q) => $q->where('remnant_price', '<=', $r->input('max_price')));
        if ($canViewCost && $r->boolean('below_cost')) {
            $query->whereColumn('remnant_price', '<', 'cost_per_base_unit');
        }

        if ($r->input('export') === 'csv') {
            $rows = $query->get();
            return response()->streamDownload(function () use ($rows, $canViewCost) {
                $out = fopen('php://output', 'w');
                $headings = ['Remnant', 'Barcode', 'Product', 'SKU', 'Original Qty', 'Remaining Qty', 'Unit', 'Normal Price', 'Remnant Price'];
                if ($canViewCost) array_push($headings, 'Cost / Base', 'Potential Profit/Loss');
                array_push($headings, 'Status', 'Transfer Date');
                fputcsv($out, $headings);
                foreach ($rows as $row) {
                    $data = [$row->remnant_no, $row->barcode, $row->product->name, $row->product->sku, $row->original_quantity, $row->remaining_quantity, $row->unit->symbol, $row->normal_price, $row->remnant_price];
                    if ($canViewCost) array_push($data, $row->cost_per_base_unit, (string) Decimal::sub($row->remnant_price, $row->cost_per_base_unit, 4));
                    array_push($data, $row->status, $row->created_at->toDateString());
                    fputcsv($out, $data);
                }
                fclose($out);
            }, 'remnant_inventory_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv']);
        }

        return view('inventory.remnants', [
            'remnants' => $query->paginate(25)->withQueryString(), 'canViewCost' => $canViewCost,
            'products' => Product::orderBy('name')->get(), 'categories' => \App\Models\Category::orderBy('name')->get(),
        ]);
    }

    public function transferForm()
    {
        return view('inventory.transfer', ['products' => Product::with(['productUnits.unit', 'balances'])->where('active', 1)->get(), 'stores' => Store::where('active', 1)->get()]);
    }

    public function transfer(Request $r, RemnantService $service)
    {
        $this->authorize('inventory.remnant_transfer');
        $d = $r->validate(['product_id' => 'required|exists:products,id', 'store_id' => 'required|exists:stores,id', 'unit_id' => 'required|exists:units,id', 'quantity' => 'required|numeric|gt:0', 'remnant_price' => 'required|numeric|min:0', 'discount_percent' => 'nullable|numeric|min:0|max:100', 'reason' => 'required|max:120', 'notes' => 'nullable']);
        $remnant = $service->transfer($d, $r->user()->id);

        return redirect()->route('remnants.index')->with('success',"{$remnant->remnant_no} created without changing combined physical stock.");
    }
}
