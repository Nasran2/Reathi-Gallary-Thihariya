<?php

namespace App\Http\Controllers;

use App\Models\CustomerLedger;
use App\Models\Expense;
use App\Models\InventoryBalance;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SupplierLedger;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = now()->toDateString();
        $sales = Sale::whereDate('sold_at', $today)->where('status', 'completed');
        $stats = ['sales' => (clone $sales)->sum('grand_total'), 'main_sales' => (clone $sales)->where('sale_type', 'main')->sum('grand_total'), 'remnant_sales' => (clone $sales)->where('sale_type', 'remnant')->sum('grand_total'), 'gross_profit' => (clone $sales)->sum('profit_total'), 'expenses' => Expense::whereDate('expense_date', $today)->sum('amount')];
        $stats['net_profit'] = $stats['gross_profit'] - $stats['expenses'];
        $stats['customer_due'] = CustomerLedger::select('customer_id', DB::raw('MAX(id) id'))->groupBy('customer_id')->get()->sum(fn ($x) => CustomerLedger::find($x->id)?->balance_after ?? 0);
        $stats['supplier_due'] = SupplierLedger::select('supplier_id', DB::raw('MAX(id) id'))->groupBy('supplier_id')->get()->sum(fn ($x) => SupplierLedger::find($x->id)?->balance_after ?? 0);
        $stats['main_stock_value'] = InventoryBalance::where('inventory_type', 'main')->join('products', 'products.id', '=', 'inventory_balances.product_id')->sum(DB::raw('inventory_balances.quantity * products.average_cost'));
        $stats['remnant_stock_value'] = InventoryBalance::where('inventory_type', 'remnant')->join('products', 'products.id', '=', 'inventory_balances.product_id')->sum(DB::raw('inventory_balances.quantity * products.average_cost'));
        $recentSales = Sale::with('customer')->latest('sold_at')->limit(6)->get();
        $recentPurchases = Purchase::with('supplier')->latest()->limit(5)->get();
        $lowStock = InventoryBalance::with('product.baseUnit')->where('inventory_type', 'main')->whereColumn('quantity', '<=', DB::raw('(select reorder_level from products where products.id=inventory_balances.product_id)'))->limit(6)->get();
        $chart = Sale::where('status', 'completed')->where('sold_at', '>=', now()->subDays(6)->startOfDay())->selectRaw('DATE(sold_at) day, SUM(grand_total) total')->groupBy('day')->orderBy('day')->get();

        return view('dashboard', compact('stats','recentSales','recentPurchases','lowStock','chart'));
    }
}
