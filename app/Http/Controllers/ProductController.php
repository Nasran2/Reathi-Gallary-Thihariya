<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $products = Product::with(['category', 'baseUnit', 'productUnits.unit', 'balances'])->when($r->q, fn ($q, $v) => $q->where(fn ($s) => $s->where('name', 'like', "%$v%")->orWhere('sku', 'like', "%$v%")->orWhere('barcode', 'like', "%$v%")))->latest()->paginate(20)->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.form', ['product' => new Product, 'categories' => Category::where('active', 1)->get(), 'units' => Unit::where('active', 1)->get(), 'suppliers' => Supplier::where('active', 1)->get()]);
    }

    public function store(Request $r)
    {
        $this->authorize('manage-products');
        $data = $this->validated($r);
        DB::transaction(function () use ($data) {
            $units = $data['units'];
            unset($data['units']);
            $product = Product::create($data);
            foreach ($units as $u) {
                $product->productUnits()->create($u);
            }
        });

        return redirect()->route('products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        return view('products.form', compact('product') + ['categories' => Category::where('active', 1)->get(), 'units' => Unit::where('active', 1)->get(), 'suppliers' => Supplier::where('active', 1)->get()]);
    }

    public function update(Request $r, Product $product)
    {
        $this->authorize('manage-products');
        $data = $this->validated($r, $product);
        DB::transaction(function () use ($data, $product) {
            $units = $data['units'];
            unset($data['units']);
            $product->update($data);
            $product->productUnits()->delete();
            foreach ($units as $u) {
                $product->productUnits()->create($u);
            }
        });

        return redirect()->route('products.index')->with('success', 'Product updated.');
    }

    private function validated(Request $r, ?Product $p = null)
    {
        return $r->validate(['name' => 'required|max:150', 'sku' => 'required|max:80|unique:products,sku,'.($p?->id ?? 'NULL'), 'barcode' => 'nullable|max:80|unique:products,barcode,'.($p?->id ?? 'NULL'), 'category_id' => 'nullable|exists:categories,id', 'base_unit_id' => 'required|exists:units,id', 'default_purchase_unit_id' => 'nullable|exists:units,id', 'default_selling_unit_id' => 'nullable|exists:units,id', 'default_supplier_id' => 'nullable|exists:suppliers,id', 'brand' => 'nullable|max:100', 'fabric_type' => 'nullable|max:100', 'material' => 'nullable|max:100', 'colour' => 'nullable|max:100', 'pattern' => 'nullable|max:100', 'width' => 'nullable|max:100', 'description' => 'nullable', 'minimum_stock' => 'required|numeric|min:0', 'reorder_level' => 'required|numeric|min:0', 'tax_rate' => 'nullable|numeric|min:0', 'track_rolls' => 'boolean', 'active' => 'boolean', 'units' => 'required|array|min:1', 'units.*.unit_id' => 'required|distinct|exists:units,id', 'units.*.conversion_rate' => 'required|numeric|gt:0', 'units.*.main_selling_price' => 'required|numeric|min:0', 'units.*.remnant_selling_price' => 'required|numeric|min:0', 'units.*.can_purchase' => 'boolean', 'units.*.can_sell' => 'boolean']);
    }
}
