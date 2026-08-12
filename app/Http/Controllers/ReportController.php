<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function dates(Request $r): array
    {
        return [$r->date('from') ?? now()->startOfMonth(), $r->date('to') ?? now()];
    }

    public function profit(Request $r)
    {
        $this->authorize('view-reports');
        [$from,$to] = $this->dates($r);
        $base = Sale::where('status', 'completed')->whereBetween('sold_at', [$from->startOfDay(), $to->endOfDay()]);
        $main = (clone $base)->where('sale_type', 'main')->selectRaw('COALESCE(SUM(grand_total),0) sales, COALESCE(SUM(cost_total),0) cogs, COALESCE(SUM(profit_total),0) profit')->first();
        $remnant = (clone $base)->where('sale_type', 'remnant')->selectRaw('COALESCE(SUM(grand_total),0) sales, COALESCE(SUM(cost_total),0) cogs, COALESCE(SUM(profit_total),0) profit')->first();
        $expenses = Expense::whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])->sum('amount');

        return view('reports.profit', compact('main', 'remnant', 'expenses', 'from', 'to'));
    }

    public function valuation(Request $r)
    {
        $this->authorize('view-reports');
        $type = $r->get('type', 'combined');
        $rows = Product::with(['category', 'baseUnit', 'balances', 'productUnits'])->whereHas('balances')->get()->map(function ($p) use ($type) {
            $main = $p->balances->where('inventory_type', 'main')->sum('quantity');
            $remnant = $p->balances->where('inventory_type', 'remnant')->sum('quantity');
            $qty = $type === 'main' ? $main : ($type === 'remnant' ? $remnant : $main + $remnant);
            $mainPrice = $p->productUnits->first()?->main_selling_price ?? 0;
            $remnantPrice = $p->productUnits->first()?->remnant_selling_price ?? 0;

            return compact('p', 'main', 'remnant', 'qty', 'mainPrice', 'remnantPrice') + ['costValue' => $qty * $p->average_cost, 'sellingValue' => $main * $mainPrice + $remnant * $remnantPrice];
        });

        return view('reports.valuation', compact('rows', 'type'));
    }

    public function deadStock(Request $r)
    {
        $this->authorize('view-reports');
        $days = (int) $r->get('days', 90);
        $products = Product::with(['category', 'baseUnit', 'balances', 'productUnits'])->leftJoin('sale_items', 'sale_items.product_id', '=', 'products.id')->leftJoin('sales', fn ($j) => $j->on('sales.id', '=', 'sale_items.sale_id')->where('sales.status', 'completed'))->select('products.*', DB::raw('MAX(sales.sold_at) last_sale_at'))->groupBy('products.id')->havingRaw('MAX(sales.sold_at) IS NULL OR MAX(sales.sold_at) < ?', [now()->subDays($days)])->get();

        return view('reports.dead-stock',compact('products','days'));
    }
}
