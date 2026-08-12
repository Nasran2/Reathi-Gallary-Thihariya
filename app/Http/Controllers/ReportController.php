<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    private function dates(Request $r): array
    {
        return [$r->date('from') ?? now()->startOfDay(), $r->date('to') ?? now()->endOfDay()];
    }

    private function render(Request $r, string $view, array $data, string $pdfTitle, string $orientation = 'portrait')
    {
        if ($r->get('export') === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf', [
                'title' => $pdfTitle,
                'period' => $r->date('from')?->format('d M Y') . ' to ' . $r->date('to')?->format('d M Y'),
                'content' => view($view . '-table', $data)->render(),
                'extraCss' => $data['extraCss'] ?? ''
            ])->setPaper('a4', $orientation);
            
            return $pdf->download(str_replace(' ', '_', $pdfTitle) . '_' . now()->format('Ymd') . '.pdf');
        }
        
        return view($view, $data);
    }

    public function sales(Request $r)
    {
        $this->authorize('view-reports');
        [$from, $to] = $this->dates($r);
        
        $query = Sale::with('customer')->whereBetween('sold_at', [$from->startOfDay(), $to->endOfDay()]);
        
        if ($r->filled('customer_id')) {
            $query->where('customer_id', $r->customer_id);
        }
        
        $sales = $query->orderBy('sold_at', 'desc')->get();
        $customers = Customer::orderBy('name')->get();

        return $this->render($r, 'reports.sales', compact('sales', 'customers', 'from', 'to'), 'Sales Report', 'landscape');
    }

    public function purchases(Request $r)
    {
        $this->authorize('view-reports');
        [$from, $to] = $this->dates($r);
        
        $query = Purchase::with('supplier')->whereBetween('purchase_date', [$from->startOfDay(), $to->endOfDay()]);
        
        if ($r->filled('supplier_id')) {
            $query->where('supplier_id', $r->supplier_id);
        }
        
        $purchases = $query->orderBy('purchase_date', 'desc')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return $this->render($r, 'reports.purchases', compact('purchases', 'suppliers', 'from', 'to'), 'Purchase Report', 'landscape');
    }

    public function expenses(Request $r)
    {
        $this->authorize('view-reports');
        [$from, $to] = $this->dates($r);
        
        $query = Expense::with('category')->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()]);
        
        if ($r->filled('category_id')) {
            $query->where('category_id', $r->category_id);
        }
        
        $expenses = $query->orderBy('expense_date', 'desc')->get();
        $categories = \App\Models\Category::orderBy('name')->get();

        return $this->render($r, 'reports.expenses', compact('expenses', 'categories', 'from', 'to'), 'Expense Report');
    }

    public function dueBills(Request $r)
    {
        $this->authorize('view-reports');
        [$from, $to] = $this->dates($r);
        
        $query = Sale::with('customer')->where('due_total', '>', 0);
        
        if ($r->filled('customer_id')) {
            $query->where('customer_id', $r->customer_id);
        }
        
        $sales = $query->orderBy('sold_at', 'desc')->get();
        $customers = Customer::orderBy('name')->get();

        return $this->render($r, 'reports.due-bills', compact('sales', 'customers', 'from', 'to'), 'Due Bills Report');
    }

    public function customerDue(Request $r)
    {
        $this->authorize('view-reports');
        
        $query = Customer::withSum('sales', 'due_total')->having('sales_sum_due_total', '>', 0);
        
        if ($r->filled('customer_id')) {
            $query->where('id', $r->customer_id);
        }
        
        $customers_due = $query->orderByDesc('sales_sum_due_total')->get();
        $customers = Customer::orderBy('name')->get();

        // Customer due doesn't need date filters usually as it's a current snapshot, 
        // but we pass dummy dates to satisfy the filter component if needed.
        $from = now(); $to = now();

        return $this->render($r, 'reports.customer-due', compact('customers_due', 'customers', 'from', 'to'), 'Customer Due Report');
    }

    public function dailyClosing(Request $r)
    {
        $this->authorize('view-reports');
        $date = $r->date('from') ?? now();
        $from = clone $date;
        $to = clone $date;

        $sales = Sale::whereBetween('sold_at', [$date->startOfDay(), $date->endOfDay()])->get();
        $purchases = Purchase::whereBetween('purchase_date', [$date->startOfDay(), $date->endOfDay()])->get();
        $expenses = Expense::whereDate('expense_date', $date->toDateString())->get();

        $data = compact('sales', 'purchases', 'expenses', 'date', 'from', 'to');
        
        return $this->render($r, 'reports.daily-closing', $data, 'Daily Closing Report - ' . $date->format('Y-m-d'));
    }

    public function profit(Request $r)
    {
        $this->authorize('view-reports');
        [$from, $to] = $this->dates($r);
        $base = Sale::where('status', 'completed')->whereBetween('sold_at', [$from->startOfDay(), $to->endOfDay()]);
        
        $main = clone $base;
        $mainData = $main->where('sale_type', 'main')->selectRaw('COALESCE(SUM(grand_total),0) sales, COALESCE(SUM(cost_total),0) cogs, COALESCE(SUM(profit_total),0) profit')->first();
        
        $remnant = clone $base;
        $remnantData = $remnant->where('sale_type', 'remnant')->selectRaw('COALESCE(SUM(grand_total),0) sales, COALESCE(SUM(cost_total),0) cogs, COALESCE(SUM(profit_total),0) profit')->first();
        
        $expenses = Expense::whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])->sum('amount');

        return $this->render($r, 'reports.profit', [
            'main' => $mainData, 
            'remnant' => $remnantData, 
            'expenses' => $expenses, 
            'from' => $from, 
            'to' => $to
        ], 'Profit & Loss Report');
    }

    public function valuation(Request $r)
    {
        $this->authorize('view-reports');
        $type = $r->get('type', 'combined');
        
        $query = Product::with(['category', 'baseUnit', 'balances', 'productUnits'])->whereHas('balances');
        
        if ($r->filled('category_id')) {
            $query->where('category_id', $r->category_id);
        }
        
        $rows = $query->get()->map(function ($p) use ($type) {
            $main = $p->balances->where('inventory_type', 'main')->sum('quantity');
            $remnant = $p->balances->where('inventory_type', 'remnant')->sum('quantity');
            $qty = $type === 'main' ? $main : ($type === 'remnant' ? $remnant : $main + $remnant);
            $mainPrice = $p->productUnits->first()?->main_selling_price ?? 0;
            $remnantPrice = $p->productUnits->first()?->remnant_selling_price ?? 0;

            return compact('p', 'main', 'remnant', 'qty', 'mainPrice', 'remnantPrice') + [
                'costValue' => $qty * $p->average_cost, 
                'sellingValue' => $main * $mainPrice + $remnant * $remnantPrice
            ];
        });

        $categories = \App\Models\Category::orderBy('name')->get();
        $from = now(); $to = now();

        return $this->render($r, 'reports.valuation', compact('rows', 'type', 'categories', 'from', 'to'), 'Stock Valuation Report');
    }

    public function deadStock(Request $r)
    {
        $this->authorize('view-reports');
        $days = (int) $r->get('days', 90);
        
        $query = Product::with(['category', 'baseUnit', 'balances', 'productUnits'])
            ->addSelect(['last_sale_at' => \App\Models\SaleItem::selectRaw('MAX(sales.sold_at)')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereColumn('sale_items.product_id', 'products.id')
                ->where('sales.status', 'completed')
            ])
            ->havingRaw('last_sale_at IS NULL OR last_sale_at < ?', [now()->subDays($days)]);
            
        if ($r->filled('category_id')) {
            $query->where('products.category_id', $r->category_id);
        }
        
        $products = $query->get();
        $categories = \App\Models\Category::orderBy('name')->get();
        $from = now(); $to = now();

        return $this->render($r, 'reports.dead-stock', compact('products', 'days', 'categories', 'from', 'to'), 'Dead Stock Report');
    }
}
