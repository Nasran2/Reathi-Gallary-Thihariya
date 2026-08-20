@extends('layouts.app') @section('title','Dashboard') @section('content')
<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        opacity: 0;
    }
</style>

<div class="mb-7 flex flex-wrap items-end justify-between gap-4 animate-fade-in-up">
    <div>
        <p class="text-sm font-semibold uppercase tracking-widest text-teal">Daily overview</p>
        <h1 class="mt-1 font-serif text-4xl text-ink">Good {{ now()->hour<12?'morning':(now()->hour<17?'afternoon':'evening') }}, {{ explode(' ',auth()->user()->name)[0] }}</h1>
        <p class="mt-2 text-slate-500">Here is how your textile business is moving today.</p>
    </div>
    <div class="flex gap-2">
        @can('purchases.create')<a href="{{ route('purchases.create') }}" class="btn-soft">Receive stock</a>@endcan
        @can('pos.main.access')<a href="{{ route('pos.main') }}" class="btn-teal">Open Main POS</a>@endcan
    </div>
</div>

@php 
    $cards=[['Today’s sales',$stats['sales'],'teal'],['Main sales',$stats['main_sales'],'ink'],['Remnant sales',$stats['remnant_sales'],'amber']];
    if(auth()->user()->can('dashboard.view_profit')) {
        $cards = array_merge($cards, [['Gross profit',$stats['gross_profit'],'emerald'],['Expenses',$stats['expenses'],'rose'],['Net profit',$stats['net_profit'],'plum']]);
    }
@endphp
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6 animate-fade-in-up" style="animation-delay: 0.1s;">
    @foreach($cards as [$label,$value,$tone])
    <div class="card p-5 hover:-translate-y-1 hover:shadow-lg transition-transform duration-300">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</div>
        <div class="mt-3 text-2xl font-bold {{ $value<0?'text-red-600':'text-ink' }}">Rs. {{ number_format($value,2) }}</div>
        <div class="mt-4 h-1.5 rounded-full bg-{{ $tone }}-100"><div class="h-full w-2/3 rounded-full bg-{{ $tone }}-500"></div></div>
    </div>
    @endforeach
</div>

@if(auth()->user()->can('cheques.view') && $upcomingCheques->isNotEmpty())
@php
    $incomingCheques = $upcomingCheques->filter(fn($c) => $c->direction === 'received' && $c->status !== 'endorsed');
    $outgoingCheques = $upcomingCheques->filter(fn($c) => $c->direction === 'issued' || $c->status === 'endorsed');
    $chequeGroups = [
        'Incoming / Received' => $incomingCheques,
        'Outgoing / Supplier' => $outgoingCheques,
    ];
@endphp
<section class="mt-6 card overflow-hidden animate-fade-in-up" style="animation-delay: 0.15s;">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b p-5">
        <div>
            <h2 class="font-serif text-2xl text-ink">Upcoming Cheques</h2>
            <p class="text-sm text-slate-400">Visible from two days before the cheque date; overdue cheques remain until processed.</p>
        </div>
        <a class="btn-soft" href="{{ route('cheques.dashboard') }}">Cheque Dashboard</a>
    </div>
    <div class="grid gap-3 border-b bg-slate-50/60 p-4 sm:grid-cols-3 xl:grid-cols-6">
        @foreach(['Received Pending'=>'received','Issued Pending'=>'issued','Due Today'=>'today','Due Next 2 Days'=>'next_two','Returned'=>'returned','Pending Value'=>'value'] as $label=>$key)
        <a href="{{ route('cheques.index') }}" class="rounded-xl bg-white p-3 shadow-sm hover:shadow transition">
            <div class="text-[10px] uppercase text-slate-400">{{ $label }}</div>
            <div class="mt-1 font-bold">{{ $key==='value'?'Rs. '.number_format($chequeSummary[$key],2):$chequeSummary[$key] }}</div>
        </a>
        @endforeach
    </div>
    <div class="grid xl:grid-cols-2">
        @foreach($chequeGroups as $title => $groupCheques)
        <div class="overflow-x-auto p-4 xl:border-r">
            <h3 class="mb-3 font-semibold text-slate-700">{{ $title }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>Cheque / Party</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Reminder</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groupCheques as $c) 
                    @php 
                        $days=today()->diffInDays($c->cheque_date,false);
                        $reminder=$days<0?'Overdue':($days===0?'Due Today':($days===1?'Due Tomorrow':'Due in '.$days.' Days')); 
                        
                        $partyText = $c->customer?->name ?? $c->supplier?->name ?? 'Unknown';
                        if($c->status === 'endorsed' && $c->endorsements->isNotEmpty()) {
                            $partyText = ($c->customer?->name ?? 'Unknown') . ' ➔ ' . ($c->endorsements->last()->supplier?->name ?? 'Unknown');
                        }
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td>
                            <a class="font-semibold text-teal" href="{{ route('cheques.show',$c) }}">{{ $c->cheque_number }}</a>
                            <div class="text-xs text-slate-400">{{ $partyText }} · {{ $c->bank }}</div>
                        </td>
                        <td class="whitespace-nowrap font-medium text-slate-700">Rs. {{ number_format($c->amount,2) }}</td>
                        <td class="whitespace-nowrap">{{ $c->cheque_date->format('d M') }}</td>
                        <td>
                            <span class="badge {{ $days<=0?'bg-red-50 text-red-700 border border-red-200':'bg-amber-50 text-amber-700 border border-amber-200' }}">{{ $reminder }}</span>
                        </td>
                        <td>
                            @if($days<=0)
                            <div class="flex gap-1">
                                <form method="post" action="{{ route('cheques.pass',$c) }}" onsubmit="return confirm('Pass this cheque and clear every linked payment?')">
                                    @csrf<button class="btn hover:bg-emerald-200 transition-colors !bg-emerald-100 !px-2 !py-1 text-emerald-800">PASS</button>
                                </form>
                                <a href="{{ route('cheques.show',$c) }}#return" class="btn hover:bg-red-100 transition-colors !bg-red-50 !px-2 !py-1 text-red-700">RETURN</a>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-6 text-center text-slate-400">No upcoming cheques.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endforeach
    </div>
</section>
@endif

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <section class="card p-6 xl:col-span-2 animate-fade-in-up" style="animation-delay: 0.2s;">
        <div class="flex justify-between">
            <div>
                <h2 class="font-serif text-2xl text-ink">Sales pulse</h2>
                <p class="text-sm text-slate-400">Last seven days</p>
            </div>
        </div>
        @php $max=max(1,$chart->max('total')??1); @endphp
        <div class="mt-7 flex h-52 items-end gap-3">
            @foreach(range(6,0) as $ago)
            @php 
                $date=now()->subDays($ago);
                $value=$chart->firstWhere('day',$date->toDateString())?->total??0; 
            @endphp
            <div class="group flex h-full flex-1 flex-col justify-end text-center">
                <div class="mx-auto mb-2 opacity-0 group-hover:opacity-100 transition-opacity text-[10px] text-slate-500 font-bold bg-slate-100 px-2 py-0.5 rounded-md">{{ $value?number_format($value/1000,1).'k':'' }}</div>
                <div class="w-full rounded-t-lg bg-teal/80 hover:bg-teal transition-colors" style="height:{{ max(4,($value/$max)*160) }}px"></div>
                <div class="mt-2 text-xs text-slate-400">{{ $date->format('D') }}</div>
            </div>
            @endforeach
        </div>
    </section>

    @if(auth()->user()->hasAnyPermission(['dashboard.view_customer_due','dashboard.view_supplier_due','dashboard.view_stock_value']))
    <section class="card p-6 animate-fade-in-up" style="animation-delay: 0.25s;">
        <h2 class="font-serif text-2xl text-ink">Financial position</h2>
        <div class="mt-6 space-y-5">
            @can('dashboard.view_customer_due')<div class="flex justify-between border-b border-slate-100 pb-4"><span class="text-sm text-slate-500">Customer due</span><strong>Rs. {{ number_format($stats['customer_due'],2) }}</strong></div>@endcan
            @can('dashboard.view_supplier_due')<div class="flex justify-between border-b border-slate-100 pb-4"><span class="text-sm text-slate-500">Supplier due</span><strong>Rs. {{ number_format($stats['supplier_due'],2) }}</strong></div>@endcan
            @can('dashboard.view_stock_value')<div class="flex justify-between border-b border-slate-100 pb-4"><span class="text-sm text-slate-500">Main stock cost</span><strong>Rs. {{ number_format($stats['main_stock_value'],2) }}</strong></div>
            <div class="flex justify-between"><span class="text-sm text-slate-500">Remnant stock cost</span><strong>Rs. {{ number_format($stats['remnant_stock_value'],2) }}</strong></div>@endcan
        </div>
    </section>
    @endif
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <section class="card overflow-hidden xl:col-span-2 animate-fade-in-up" style="animation-delay: 0.3s;">
        <div class="flex items-center justify-between p-5">
            <h2 class="font-serif text-xl text-ink">Recent sales</h2>
            <a class="text-sm font-semibold text-teal" href="{{ route('sales.index') }}">View all</a>
        </div>
        <div class="overflow-x-auto">
            <table>
                <thead>
                    <tr><th>Invoice</th><th>Customer</th><th>Type</th><th>Total</th>@can('dashboard.view_profit')<th>Profit</th>@endcan</tr>
                </thead>
                <tbody>
                    @forelse($recentSales as $sale)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td><a class="font-semibold text-teal" href="{{ route('sales.show',$sale) }}">{{ $sale->invoice_no }}</a></td>
                        <td>{{ $sale->customer?->name??'Walk-in' }}</td>
                        <td><span class="badge {{ $sale->sale_type==='remnant'?'bg-amber-100 text-amber-700':'bg-teal-50 text-teal-700' }}">{{ ucfirst($sale->sale_type) }}</span></td>
                        <td>Rs. {{ number_format($sale->grand_total,2) }}</td>
                        @can('dashboard.view_profit')<td class="{{ $sale->profit_total<0?'text-red-600':'text-emerald-600' }} font-medium">Rs. {{ number_format($sale->profit_total,2) }}</td>@endcan
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-slate-400">No sales recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    
    <section class="card p-5 animate-fade-in-up" style="animation-delay: 0.35s;">
        <h2 class="font-serif text-xl text-ink">Low stock</h2>
        <div class="mt-4 space-y-3">
            @forelse($lowStock as $row)
            <div class="flex items-center justify-between rounded-xl bg-rose-50 p-3 shadow-sm hover:shadow-md transition">
                <div>
                    <div class="text-sm font-semibold text-rose-900">{{ $row->product->name }}</div>
                    <div class="text-xs text-rose-500/70">{{ $row->product->sku }}</div>
                </div>
                <span class="text-sm font-bold text-rose-600">{{ $row->quantity+0 }} {{ $row->product->baseUnit->symbol }}</span>
            </div>
            @empty
            <p class="py-8 text-center text-sm text-slate-400">Everything is sufficiently stocked.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
