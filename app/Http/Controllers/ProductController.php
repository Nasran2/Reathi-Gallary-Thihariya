<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\UnitPreset;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $products = Product::with(['category', 'baseUnit', 'productUnits.unit', 'balances'])->when($r->q, fn ($q, $v) => $q->where(fn ($s) => $s->where('name', 'like', "%$v%")->orWhere('sku', 'like', "%$v%")->orWhere('barcode', 'like', "%$v%")))->latest()->paginate(20)->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.form', $this->formData(new Product));
    }

    public function store(Request $r, \App\Services\InventoryService $inventoryService)
    {
        $this->authorize('manage-products');
        $data = $this->validated($r);
        $product = null;
        DB::transaction(function () use ($data, $r, $inventoryService, &$product) {
            $units = $data['units'] ?? [];
            $openingStock = (float) ($data['opening_stock'] ?? 0);
            unset($data['units'], $data['opening_stock']);
            $product = Product::create($data);
            $this->syncUnits($product, $units);
            
            if ($openingStock > 0) {
                $storeId = \App\Models\Store::where('active', 1)->value('id') ?? 1;
                $inventoryService->move($product, $storeId, 'main', 'stock_adjustment', $openingStock, 0, $product->average_cost, null, $r->user()->id, 'Opening Stock');
            }
        });

        if ($r->wantsJson()) {
            if ($product->default_supplier_id) {
                $product->suppliers()->syncWithoutDetaching([$product->default_supplier_id => ['last_cost' => $product->average_cost]]);
                $product->load('suppliers');
            }
            $catalogItem = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'units' => $product->productUnits->where('can_purchase', true)->map(fn($u) => ['id' => $u->unit_id, 'name' => $u->unit->name, 'symbol' => $u->unit->symbol, 'rate' => (float)$u->conversion_rate])->values(),
                'suppliers' => $product->suppliers->map(fn($s) => ['id' => $s->id, 'last_cost' => (float)$s->pivot->last_cost])->values()
            ];
            return response()->json(['success' => true, 'product' => $catalogItem]);
        }

        return redirect()->route('products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $product->load('productUnits');

        return view('products.form', $this->formData($product));
    }

    public function update(Request $r, Product $product, \App\Services\InventoryService $inventoryService)
    {
        $this->authorize('manage-products');
        $data = $this->validated($r, $product);
        DB::transaction(function () use ($data, $product, $r, $inventoryService) {
            $units = $data['units'] ?? [];
            $openingStock = (float) ($data['opening_stock'] ?? 0);
            unset($data['units'], $data['opening_stock']);
            $product->update($data);
            $this->syncUnits($product, $units);
            
            if ($openingStock > 0) {
                $storeId = \App\Models\Store::where('active', 1)->value('id') ?? 1;
                $inventoryService->move($product, $storeId, 'main', 'stock_adjustment', $openingStock, 0, $product->average_cost, null, $r->user()->id, 'Added Stock');
            }
        });

        return redirect()->route('products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $this->authorize('manage-products');
        try {
            DB::transaction(function () use ($product) {
                $product->productUnits()->delete();
                $product->delete();
            });
            return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Cannot delete product. It may be linked to existing sales or inventory.');
        }
    }

    public function show(Product $product)
    {
        $product->load(['category', 'brand', 'baseUnit', 'productUnits.unit', 'balances.store', 'suppliers']);

        $totalSales = SaleItem::where('product_id', $product->id)->sum('net_revenue');
        $movementQuery = InventoryMovement::where('product_id', $product->id);
        $totalStockIn = (clone $movementQuery)->sum('quantity_in');
        $totalStockOut = (clone $movementQuery)->sum('quantity_out');

        $movements = $movementQuery->with(['reference', 'user', 'store'])
            ->latest()
            ->paginate(15);

        return view('products.show', compact(
            'product',
            'totalSales',
            'totalStockIn',
            'totalStockOut',
            'movements'
        ));
    }

    public function categories()
    {
        $categories = Category::orderBy('name')->get();
        return view('products.categories', compact('categories'));
    }

    public function units()
    {
        $units = Unit::orderBy('name')->get();
        return view('products.units', compact('units'));
    }

    public function brands()
    {
        $brands = \App\Models\Brand::orderBy('name')->get();
        return view('products.brands', compact('brands'));
    }

    private function validated(Request $r, ?Product $p = null)
    {
        $r->merge([
            'name' => trim((string) $r->input('name')),
            'sku' => trim((string) $r->input('sku')),
            'barcode' => filled($r->input('barcode')) ? trim((string) $r->input('barcode')) : null,
        ]);

        $data = $r->validate(['name' => 'required|max:150', 'sku' => 'required|max:80|unique:products,sku,'.($p?->id ?? 'NULL'), 'barcode' => 'nullable|max:80|unique:products,barcode,'.($p?->id ?? 'NULL'), 'category_id' => 'nullable|exists:categories,id', 'base_unit_id' => 'required|exists:units,id', 'default_purchase_unit_id' => 'nullable|exists:units,id', 'default_selling_unit_id' => 'nullable|exists:units,id', 'default_supplier_id' => 'nullable|exists:suppliers,id', 'brand_id' => 'nullable|exists:brands,id', 'fabric_type' => 'nullable|max:100', 'material' => 'nullable|max:100', 'colour' => 'nullable|max:100', 'pattern' => 'nullable|max:100', 'width' => 'nullable|max:100', 'description' => 'nullable', 'average_cost' => 'required|numeric|min:0', 'main_selling_price' => 'required|numeric|min:0', 'remnant_selling_price' => 'required|numeric|min:0', 'opening_stock' => 'nullable|numeric|min:0', 'minimum_stock' => 'required|numeric|min:0', 'reorder_level' => 'required|numeric|min:0', 'tax_rate' => 'nullable|numeric|min:0', 'track_rolls' => 'boolean', 'active' => 'boolean', 'units' => 'nullable|array', 'units.*.unit_id' => 'required|distinct|exists:units,id', 'units.*.base_quantity' => 'required|numeric|gt:0', 'units.*.unit_quantity' => 'required|numeric|gt:0', 'units.*.can_purchase' => 'boolean', 'units.*.can_sell' => 'boolean']);

        $otherProducts = Product::query()->when($p, fn ($query) => $query->whereKeyNot($p->id));

        if ($data['barcode'] && (clone $otherProducts)->where('sku', $data['barcode'])->exists()) {
            throw ValidationException::withMessages([
                'barcode' => 'This barcode is already used as another product SKU.',
            ]);
        }

        if ((clone $otherProducts)->where('barcode', $data['sku'])->exists()) {
            throw ValidationException::withMessages([
                'sku' => 'This SKU is already used as another product barcode.',
            ]);
        }

        foreach ($data['units'] ?? [] as $index => $unit) {
            if ((int) $unit['unit_id'] === (int) $data['base_unit_id']) {
                throw ValidationException::withMessages([
                    "units.$index.unit_id" => 'Additional units must be different from the base stock unit.',
                ]);
            }
        }

        return $data;
    }

    private function formData(Product $product): array
    {
        return [
            'product' => $product,
            'categories' => Category::where('active', true)->orderBy('name')->get(),
            'brands' => \App\Models\Brand::where('active', true)->orderBy('name')->get(),
            'units' => Unit::where('active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::where('active', true)->orderBy('name')->get(),
            'unitPresets' => UnitPreset::with('conversions')->orderBy('name')->get(),
        ];
    }

    private function syncUnits(Product $product, array $additionalUnits): void
    {
        $product->productUnits()->delete();
        $product->productUnits()->create([
            'unit_id' => $product->base_unit_id,
            'base_quantity' => 1,
            'unit_quantity' => 1,
            'conversion_rate' => 1,
            'can_purchase' => true,
            'can_sell' => true,
        ]);

        foreach ($additionalUnits as $unit) {
            $product->productUnits()->create([
                'unit_id' => $unit['unit_id'],
                'base_quantity' => $unit['base_quantity'],
                'unit_quantity' => $unit['unit_quantity'],
                'conversion_rate' => Decimal::div($unit['base_quantity'], $unit['unit_quantity'], 8),
                'can_purchase' => $unit['can_purchase'] ?? true,
                'can_sell' => $unit['can_sell'] ?? true,
            ]);
        }
    }
}
