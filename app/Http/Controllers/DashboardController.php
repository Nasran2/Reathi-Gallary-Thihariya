<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Models\CustomerLedger;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\InventoryBalance;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SupplierLedger;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = now()->toDateString();
        $sales = Sale::whereDate('sold_at', $today)->where('status', 'completed');
        $stats = ['sales' => (clone $sales)->sum('grand_total'), 'main_sales' => (clone $sales)->where('sale_type', 'main')->sum('grand_total'), 'remnant_sales' => (clone $sales)->where('sale_type', 'remnant')->sum('grand_total'), 'gross_profit' => (clone $sales)->sum('profit_total'), 'expenses' => Expense::whereDate('expense_date', $today)->sum('amount')];
        $returns = SaleReturn::join('sales', 'sales.id', '=', 'sale_returns.sale_id')->whereDate('sale_returns.return_date', $today);
        $returnTotals = (clone $returns)->selectRaw('COALESCE(SUM(return_total),0) revenue, COALESCE(SUM(sale_returns.cost_total),0) cost')->first();
        $stats['sales'] -= $returnTotals->revenue;
        $stats['main_sales'] -= (clone $returns)->where('sales.sale_type', 'main')->sum('return_total');
        $stats['remnant_sales'] -= (clone $returns)->where('sales.sale_type', 'remnant')->sum('return_total');
        $stats['gross_profit'] -= ($returnTotals->revenue - $returnTotals->cost);
        $stats['net_profit'] = $stats['gross_profit'] - $stats['expenses'];
        $stats['customer_due'] = max(0, CustomerLedger::select('customer_id', DB::raw('MAX(id) id'))->groupBy('customer_id')->get()->sum(fn ($x) => CustomerLedger::find($x->id)?->balance_after ?? 0) - CustomerPayment::where('status', 'pending')->sum('amount'));
        $stats['supplier_due'] = max(0, SupplierLedger::select('supplier_id', DB::raw('MAX(id) id'))->groupBy('supplier_id')->get()->sum(fn ($x) => SupplierLedger::find($x->id)?->balance_after ?? 0) - SupplierPayment::where('status', 'pending')->sum('amount'));
        $stats['main_stock_value'] = InventoryBalance::where('inventory_type', 'main')->join('products', 'products.id', '=', 'inventory_balances.product_id')->sum(DB::raw('inventory_balances.quantity * products.average_cost'));
        $stats['remnant_stock_value'] = InventoryBalance::where('inventory_type', 'remnant')->join('products', 'products.id', '=', 'inventory_balances.product_id')->sum(DB::raw('inventory_balances.quantity * products.average_cost'));
        $recentSales = Sale::with('customer')->latest('sold_at')->limit(6)->get();
        $recentPurchases = Purchase::with('supplier')->latest()->limit(5)->get();
        $lowStock = InventoryBalance::with('product.baseUnit')->where('inventory_type', 'main')->whereColumn('quantity', '<=', DB::raw('(select reorder_level from products where products.id=inventory_balances.product_id)'))->limit(6)->get();
        $chart = Sale::where('status', 'completed')->where('sold_at', '>=', now()->subDays(6)->startOfDay())->selectRaw('DATE(sold_at) day, SUM(grand_total) total')->groupBy('day')->orderBy('day')->get();
        $upcomingCheques = Cheque::with('customer', 'supplier')->whereIn('status', ['pending', 'deposited', 'endorsed'])->whereDate('cheque_date', '<=', today()->addDays(2))->orderBy('cheque_date')->get();
        $chequeSummary = ['received' => Cheque::where('direction', 'received')->whereIn('status', ['pending', 'deposited', 'endorsed'])->count(), 'issued' => Cheque::where('direction', 'issued')->whereIn('status', ['pending', 'deposited', 'endorsed'])->count(), 'today' => Cheque::whereIn('status', ['pending', 'deposited', 'endorsed'])->whereDate('cheque_date', today())->count(), 'next_two' => Cheque::whereIn('status', ['pending', 'deposited', 'endorsed'])->whereBetween('cheque_date', [today(), today()->addDays(2)])->count(), 'returned' => Cheque::where('status', 'returned')->count(), 'value' => Cheque::whereIn('status', ['pending', 'deposited', 'endorsed'])->sum('amount')];

        return view('dashboard', compact('stats', 'recentSales', 'recentPurchases', 'lowStock', 'chart', 'upcomingCheques', 'chequeSummary'));
    }
}
