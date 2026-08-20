@extends('layouts.app') @section('title','Cheque Dashboard') @section('content')
<div class="space-y-6"><div><h1 class="font-serif text-3xl text-ink">Cheque Dashboard</h1><p class="text-sm text-slate-500">Manage incoming, outgoing, and upcoming cheques.</p></div><div class="grid gap-5 sm:grid-cols-3 xl:grid-cols-6">@foreach(['Received Pending'=>'received','Issued Pending'=>'issued','Due Today'=>'today','Due Next 2 Days'=>'next_two','Returned'=>'returned','Pending Value'=>'pending_value'] as $label=>$key)<a class="card p-4 hover:-translate-y-1 hover:shadow-lg transition-transform duration-300" href="{{ route('cheques.index') }}"><div class="text-[10px] uppercase font-semibold tracking-wider text-slate-400">{{ $label }}</div><div class="mt-2 text-2xl font-bold text-ink">{{ $key==='pending_value'?'Rs. '.number_format($summary[$key],2):$summary[$key] }}</div></a>@endforeach</div><div class="grid gap-5 xl:grid-cols-2">@foreach(['received'=>'Incoming / Received Cheques','issued'=>'Outgoing / Supplier Cheques'] as $dir=>$title)<div class="card overflow-hidden"><div class="border-b p-5"><h2 class="font-serif text-xl">{{ $title }}</h2></div><div class="overflow-x-auto"><table><thead><tr><th>Cheque</th><th>Party</th><th>Amount</th><th>Date</th><th>Reminder</th><th>Action</th></tr></thead><tbody>@forelse($upcoming->where('direction',$dir) as $c) @php $days=today()->diffInDays($c->cheque_date,false);$label=$days<0?'Overdue':($days===0?'Due Today':($days===1?'Due Tomorrow':'Due in '.$days.' Days')); @endphp<tr><td><a class="text-teal font-semibold" href="{{ route('cheques.show',$c) }}">{{ $c->cheque_number }}</a><div class="text-xs text-slate-400">{{ $c->bank }}</div></td><td>{{ $c->customer?->name ?? $c->supplier?->name }}</td><td class="font-medium">Rs. {{ number_format($c->amount,2) }}</td><td>{{ $c->cheque_date->format('d M Y') }}</td><td><span class="badge {{ $days<=0?'bg-red-50 text-red-700':'bg-amber-50 text-amber-700' }}">{{ $label }}</span></td><td>@if($days<=0)<div class="flex gap-1">
<div x-data="{ open: false }">
        <button type="button" @click="open = true" class="btn !bg-emerald-100 !px-2 !py-1 text-emerald-800 hover:bg-emerald-200 transition-colors">PASS</button>
        <template x-teleport="body">
            <div x-show="open" style="display:none;" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4">
                <form method="post" action="{{ route('cheques.pass',$c) }}" @click.away="open = false" class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                    @csrf
                    <h3 class="font-serif text-xl mb-2 text-ink">Pass Cheque</h3>
                    <p class="text-sm text-slate-500 mb-5">Select the date this cheque passed to clear linked payments.</p>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Pass Date</label>
                    <input type="date" name="pass_date" value="{{ today()->format('Y-m-d') }}" class="w-full mb-6 rounded-lg border-slate-300" required>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="open = false" class="btn-soft">Cancel</button>
                        <button type="submit" class="btn-teal">Confirm Pass</button>
                    </div>
                </form>
            </div>
        </template>
    </div>
<a class="btn !bg-red-50 !px-2 !py-1 text-red-700 hover:bg-red-100 transition-colors" href="{{ route('cheques.show',$c) }}#return">RETURN</a></div>@else—@endif</td></tr>@empty<tr><td colspan="6" class="py-8 text-center text-slate-400">No upcoming cheques.</td></tr>@endforelse</tbody></table></div></div>@endforeach</div></div>
@endsection
