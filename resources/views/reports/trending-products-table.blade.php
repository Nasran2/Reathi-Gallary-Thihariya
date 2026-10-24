<div class="summary-grid summary-clearfix grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    {{-- Top Trending Product --}}
    <div class="summary-card bg-amber-50 border border-amber-200 p-4 rounded-xl shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-amber-800 uppercase tracking-wider">Top Trending Product</h4>
            <span class="text-lg">🔥</span>
        </div>
        <div class="card-val text-lg font-bold text-amber-950 truncate" title="{{ $topProduct->product_name ?? 'N/A' }}">
            {{ $topProduct->product_name ?? 'None' }}
        </div>
        <div class="card-note text-xs text-amber-700 font-medium mt-1">
            @if($topProduct)
                {{ number_format($topProduct->total_qty_sold, 1) }} sold · Rs. {{ number_format($topProduct->total_revenue, 2) }}
            @else
                No sales in selected period
            @endif
        </div>
    </div>

    {{-- Total Units Sold --}}
    <div class="summary-card bg-blue-50 border border-blue-100 p-4 rounded-xl shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-blue-800 uppercase tracking-wider">Total Units Sold</h4>
            <i class="ti ti-package text-blue-600 text-base"></i>
        </div>
        <div class="card-val text-2xl font-bold text-blue-950">
            {{ number_format($totalUnitsSold, 1) }}
        </div>
        <div class="card-note text-xs text-blue-600/80 mt-1">
            Volume across {{ $trendingProducts->count() }} products
        </div>
    </div>

    {{-- Total Revenue --}}
    <div class="summary-card bg-emerald-50 border border-emerald-100 p-4 rounded-xl shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-emerald-800 uppercase tracking-wider">Total Sales Turnover</h4>
            <i class="ti ti-cash text-emerald-600 text-base"></i>
        </div>
        <div class="card-val text-2xl font-bold text-emerald-950">
            Rs. {{ number_format($totalRevenue, 2) }}
        </div>
        <div class="card-note text-xs text-emerald-600/80 mt-1">
            Gross revenue generated
        </div>
    </div>

    {{-- Total Profit --}}
    <div class="summary-card bg-slate-900 border border-slate-800 p-4 rounded-xl text-white shadow-xs">
        <div class="flex items-center justify-between mb-1">
            <h4 class="card-title text-xs font-bold text-slate-300 uppercase tracking-wider">Total Profit Generated</h4>
            <i class="ti ti-chart-pie text-slate-400 text-base"></i>
        </div>
        <div class="card-val text-2xl font-bold text-emerald-400">
            Rs. {{ number_format($totalProfit, 2) }}
        </div>
        <div class="card-note text-xs text-slate-400 mt-1">
            Avg. Profit Margin: {{ number_format($overallMargin, 1) }}%
        </div>
    </div>
</div>
<div style="clear: both;"></div>

<div class="flex items-center justify-between mb-3 border-b border-slate-200 pb-2">
    <div>
        <h3 class="font-bold text-lg text-slate-800">Product Sales Ranking</h3>
        <p class="text-xs text-slate-500">
            Ranked by {{ match($sortBy ?? 'quantity') {
                'revenue' => 'Sales Revenue',
                'profit' => 'Profit Generated',
                'orders' => 'Order Frequency',
                default => 'Quantity Sold'
            } }} ({{ $sortDir === 'asc' ? 'Lowest First' : 'Highest First' }})
        </p>
    </div>
    <div class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg">
        Showing {{ $trendingProducts->count() }} items ({{ $daysInPeriod }} days period)
    </div>
</div>

<div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
        <thead>
            <tr class="border-b border-slate-200 text-slate-500 uppercase tracking-wider text-xs bg-slate-50">
                <th class="py-3 px-3 font-semibold text-center w-14">Rank</th>
                <th class="py-3 px-3 font-semibold">Product Name & SKU</th>
                <th class="py-3 px-3 font-semibold">Category</th>
                <th class="py-3 px-3 text-right font-semibold">Current Stock</th>
                <th class="py-3 px-3 text-right font-semibold">Qty Sold</th>
                <th class="py-3 px-3 text-right font-semibold">Orders</th>
                <th class="py-3 px-3 text-right font-semibold">Avg. Price (Rs.)</th>
                <th class="py-3 px-3 text-right font-semibold">Revenue (Rs.)</th>
                <th class="py-3 px-3 text-right font-semibold">Profit (Rs.)</th>
                <th class="py-3 px-3 text-right font-semibold">Velocity</th>
                <th class="py-3 px-3 text-center font-semibold">Trend</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($trendingProducts as $p)
                @php
                    $isTop3 = $p->rank <= 3;
                    $rankBadge = match($p->rank) {
                        1 => 'bg-amber-100 text-amber-900 border-amber-300 font-black',
                        2 => 'bg-slate-200 text-slate-800 border-slate-300 font-bold',
                        3 => 'bg-amber-50 text-amber-800 border-amber-200 font-bold',
                        default => 'bg-slate-50 text-slate-600 border-slate-200'
                    };
                    $trendBadge = match(true) {
                        $p->rank === 1 || $p->volume_share >= 25 => ['label' => 'Hot Trend', 'class' => 'bg-red-100 text-red-800 border-red-200', 'icon' => '🔥'],
                        $p->rank <= 3 || $p->volume_share >= 15 => ['label' => 'Fast Mover', 'class' => 'bg-orange-100 text-orange-800 border-orange-200', 'icon' => '🚀'],
                        $p->volume_share >= 5 => ['label' => 'Steady', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'icon' => '📈'],
                        default => ['label' => 'Regular', 'class' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => '⚡']
                    };
                @endphp
                <tr class="hover:bg-slate-50/80 transition-colors {{ $isTop3 ? 'bg-amber-50/20' : '' }}">
                    <td class="py-3 px-3 text-center">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs border {{ $rankBadge }}">
                            {{ $p->rank }}
                        </span>
                    </td>
                    <td class="py-3 px-3">
                        <div class="font-bold text-slate-800 hover:text-teal transition-colors">
                            {{ $p->product_name }}
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
                            @if($p->sku)
                                <span class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-600 font-mono">{{ $p->sku }}</span>
                            @endif
                            @if($p->barcode && $p->barcode !== $p->sku)
                                <span>{{ $p->barcode }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="py-3 px-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-700">
                            {{ $p->category_name ?? 'Uncategorized' }}
                        </span>
                    </td>
                    <td class="py-3 px-3 text-right font-medium">
                        @if((float) $p->current_stock <= 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-800">
                                Out of Stock
                            </span>
                        @elseif((float) $p->current_stock <= 10)
                            <span class="text-amber-700 font-bold" title="Low stock alert">
                                {{ number_format($p->current_stock, 1) }} {{ $p->unit_name }}
                            </span>
                        @else
                            <span class="text-slate-700 font-semibold">
                                {{ number_format($p->current_stock, 1) }} {{ $p->unit_name }}
                            </span>
                        @endif
                    </td>
                    <td class="py-3 px-3 text-right">
                        <div class="font-bold text-slate-900 text-base">
                            {{ number_format($p->total_qty_sold, 1) }}
                        </div>
                        <div class="text-[11px] text-slate-400 font-medium">
                            {{ number_format($p->volume_share, 1) }}% of volume
                        </div>
                    </td>
                    <td class="py-3 px-3 text-right font-medium text-slate-700">
                        {{ $p->orders_count }}
                    </td>
                    <td class="py-3 px-3 text-right font-medium text-slate-700">
                        {{ number_format($p->avg_price, 2) }}
                    </td>
                    <td class="py-3 px-3 text-right">
                        <span class="font-bold text-emerald-700">
                            Rs. {{ number_format($p->total_revenue, 2) }}
                        </span>
                    </td>
                    <td class="py-3 px-3 text-right">
                        <div class="font-bold {{ $p->total_profit >= 0 ? 'text-teal-700' : 'text-rose-600' }}">
                            Rs. {{ number_format($p->total_profit, 2) }}
                        </div>
                        <div class="text-[11px] text-slate-400">
                            {{ number_format($p->profit_margin, 1) }}% margin
                        </div>
                    </td>
                    <td class="py-3 px-3 text-right font-medium text-slate-600 whitespace-nowrap">
                        <span class="font-bold text-slate-800">{{ number_format($p->daily_velocity, 2) }}</span>
                        <span class="text-xs text-slate-400">/day</span>
                    </td>
                    <td class="py-3 px-3 text-center whitespace-nowrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border shadow-2xs {{ $trendBadge['class'] }}">
                            <span>{{ $trendBadge['icon'] }}</span>
                            <span>{{ $trendBadge['label'] }}</span>
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="py-12 text-center text-slate-500">
                        <i class="ti ti-chart-bar-off text-4xl mb-2 opacity-40 block"></i>
                        <p class="font-semibold text-slate-700">No trending sales data found</p>
                        <p class="text-xs text-slate-400 mt-1">Try expanding your date range or clearing category filters.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($trendingProducts->isNotEmpty())
            <tfoot>
                <tr class="bg-slate-50 font-bold border-t-2 border-slate-200 text-slate-800 text-xs">
                    <td colspan="4" class="py-3 px-3 text-right uppercase tracking-wider">Total (Filtered):</td>
                    <td class="py-3 px-3 text-right text-base text-slate-900">{{ number_format($trendingProducts->sum('total_qty_sold'), 1) }}</td>
                    <td class="py-3 px-3 text-right">{{ $trendingProducts->sum('orders_count') }}</td>
                    <td class="py-3 px-3 text-right">-</td>
                    <td class="py-3 px-3 text-right text-emerald-700 text-sm">Rs. {{ number_format($trendingProducts->sum('total_revenue'), 2) }}</td>
                    <td class="py-3 px-3 text-right text-teal-700 text-sm">Rs. {{ number_format($trendingProducts->sum('total_profit'), 2) }}</td>
                    <td colspan="2" class="py-3 px-3 text-right text-slate-400">-</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
