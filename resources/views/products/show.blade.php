@extends('layouts.app') 
@section('title', $product->name . ' - Product Details') 
@section('content')

<div class="mb-6">
    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">
        <a href="{{ route('products.index') }}" class="hover:text-teal">Products</a>
        <span>/</span>
        <span class="text-slate-600">{{ $product->name }}</span>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl text-ink">{{ $product->name }}</h1>
            <div class="flex items-center gap-3 mt-1 text-sm text-slate-500">
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-600">{{ $product->sku }}</span>
                <span>{{ $product->category?->name ?? 'Uncategorized' }}</span>
                @if($product->active)
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Active
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Inactive
                    </span>
                @endif
            </div>
        </div>
        @can('manage-products')
            <a href="{{ route('products.edit', $product) }}" class="btn-soft">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Edit Product
            </a>
        @endcan
    </div>
</div>

<!-- Stats Row -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="card p-5 relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-blue-50 opacity-50"></div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 relative z-10">Total Sales</p>
        <h3 class="text-2xl font-bold text-slate-800 relative z-10">Rs. {{ number_format($totalSales, 2) }}</h3>
    </div>
    
    <div class="card p-5 relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-emerald-50 opacity-50"></div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 relative z-10">Total Profit</p>
        <h3 class="text-2xl font-bold text-emerald-600 relative z-10">Rs. {{ number_format($totalProfit, 2) }}</h3>
    </div>
    
    <div class="card p-5 relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-amber-50 opacity-50"></div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 relative z-10">Total Stock (Main)</p>
        <h3 class="text-2xl font-bold text-slate-800 relative z-10">{{ number_format($product->balances->where('inventory_type','main')->sum('quantity'), 3) }} <span class="text-sm font-normal text-slate-500">{{ $product->baseUnit->symbol }}</span></h3>
    </div>
    
    <div class="card p-5 relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-purple-50 opacity-50"></div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 relative z-10">Average Cost</p>
        <h3 class="text-2xl font-bold text-slate-800 relative z-10">Rs. {{ number_format($product->average_cost, 2) }}</h3>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <!-- Left Column: Details & Pricing -->
    <div class="xl:col-span-1 space-y-6">
        <!-- Units & Pricing -->
        <div class="card overflow-hidden">
            <div class="bg-slate-50/80 px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="grid h-8 w-8 place-items-center rounded-lg bg-teal/10 text-teal">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                </div>
                <h3 class="font-bold text-slate-800">Units & Pricing</h3>
            </div>
            <div class="p-5">
                <div class="space-y-4">
                    @foreach($product->productUnits as $pu)
                        <div class="flex items-center justify-between p-3 rounded-lg border border-slate-100 bg-slate-50/50">
                            <div>
                                <div class="font-semibold text-slate-800">{{ $pu->unit->name }} <span class="text-xs font-mono text-slate-500 ml-1">({{ $pu->unit->symbol }})</span></div>
                                <div class="text-xs text-slate-500 mt-1">1 {{ $pu->unit->symbol }} = {{ (float)$pu->conversion_rate }} {{ $product->baseUnit->symbol }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-teal">Rs. {{ number_format($product->main_selling_price * $pu->conversion_rate, 2) }}</div>
                                <div class="text-[10px] text-slate-400 font-medium uppercase tracking-wide mt-0.5">Selling Price</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <!-- Additional Info -->
        <div class="card overflow-hidden">
            <div class="bg-slate-50/80 px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="grid h-8 w-8 place-items-center rounded-lg bg-blue-50 text-blue-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="font-bold text-slate-800">Product Details</h3>
            </div>
            <div class="p-5">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Brand</dt>
                        <dd class="font-medium text-slate-800">{{ $product->brand ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Color</dt>
                        <dd class="font-medium text-slate-800">{{ $product->colour ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Material</dt>
                        <dd class="font-medium text-slate-800">{{ $product->material ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Pattern</dt>
                        <dd class="font-medium text-slate-800">{{ $product->pattern ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Min. Stock / Reorder</dt>
                        <dd class="font-medium text-slate-800">{{ (float)$product->minimum_stock }} / {{ (float)$product->reorder_level }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Track Rolls?</dt>
                        <dd class="font-medium text-slate-800">{{ $product->track_rolls ? 'Yes' : 'No' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Right Column: Inventory Movements -->
    <div class="xl:col-span-2">
        <div class="card overflow-hidden h-full flex flex-col">
            <div class="bg-slate-50/80 px-6 py-5 border-b border-slate-100 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="grid h-8 w-8 place-items-center rounded-lg bg-indigo-50 text-indigo-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    </div>
                    <h3 class="font-bold text-slate-800">Inventory Movements (In & Out)</h3>
                </div>
            </div>
            
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-3 font-semibold uppercase">Date</th>
                            <th class="px-6 py-3 font-semibold uppercase">Type</th>
                            <th class="px-6 py-3 font-semibold uppercase">Reference</th>
                            <th class="px-6 py-3 font-semibold text-right uppercase">In</th>
                            <th class="px-6 py-3 font-semibold text-right uppercase">Out</th>
                            <th class="px-6 py-3 font-semibold text-right uppercase">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($movements as $m)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="font-medium text-slate-800">{{ $m->created_at->format('d M, Y') }}</div>
                                <div class="text-xs text-slate-400">{{ $m->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-3.5">
                                @if($m->type === 'purchase')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-blue-50 text-blue-700 text-xs font-medium"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg> Purchase</span>
                                @elseif($m->type === 'sale')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-emerald-50 text-emerald-700 text-xs font-medium"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Sale</span>
                                @elseif(str_contains($m->type, 'transfer'))
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-indigo-50 text-indigo-700 text-xs font-medium"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg> Transfer</span>
                                @elseif(str_contains($m->type, 'adjust'))
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-amber-50 text-amber-700 text-xs font-medium"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> Adjustment</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-slate-100 text-slate-700 text-xs font-medium">{{ ucfirst($m->type) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">
                                @if($m->reference_type === \App\Models\Purchase::class)
                                    <a href="{{ route('purchases.show', $m->reference_id) }}" class="text-teal hover:underline font-medium">Purchase #{{ $m->reference_id }}</a>
                                @elseif($m->reference_type === \App\Models\Sale::class)
                                    <a href="{{ route('sales.show', $m->reference_id) }}" class="text-teal hover:underline font-medium">Sale #{{ $m->reference_id }}</a>
                                @else
                                    <span class="text-slate-500">{{ $m->description ?: 'Manual/System' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right font-medium text-emerald-600">
                                {{ $m->quantity_in > 0 ? '+'.(float)$m->quantity_in : '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-right font-medium text-red-500">
                                {{ $m->quantity_out > 0 ? '-'.(float)$m->quantity_out : '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-right font-bold text-slate-800">
                                {{ (float)$m->balance_after }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <div class="grid h-12 w-12 place-items-center rounded-full bg-slate-50 text-slate-300 mx-auto mb-3">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                </div>
                                <p>No inventory movements recorded yet.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($movements->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
