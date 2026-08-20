@extends('layouts.app') @section('title',$purchase->purchase_no) @section('content')
<div class="mb-6 flex flex-wrap justify-between gap-3"><div><a class="text-sm text-slate-500" href="{{ route('purchases.index') }}">← Purchases</a><h1 class="mt-2 font-serif text-3xl text-ink">{{ $purchase->purchase_no }}</h1><p class="text-sm text-slate-500">{{ $purchase->supplier->name }} · {{ $purchase->purchase_date->format('d M Y') }}</p></div><div class="flex gap-2"><a class="btn-soft" href="{{ route('purchases.returns',['purchase_id'=>$purchase->id]) }}">Return Items</a><span class="badge h-fit bg-emerald-50 text-emerald-700">Received</span></div></div>
<div class="card overflow-x-auto"><table><thead><tr><th>Product</th><th>Purchase quantity</th><th>Base quantity</th><th>Supplier cost</th><th>System cost</th><th>Invoice total</th><th>Allocated extra</th><th>Landed/base</th><th>New avg</th></tr></thead><tbody>@foreach($purchase->items as $i)<tr><td><div class="font-semibold">{{ $i->product->name }}</div><div class="text-xs text-slate-400">{{ $i->product->sku }}</div></td><td>{{ $i->quantity+0 }} {{ $i->unit->symbol }}</td><td>{{ $i->base_quantity+0 }} {{ $i->product->baseUnit?->symbol }}</td><td>Rs. {{ number_format($i->supplier_unit_cost,4) }}</td><td>Rs. {{ number_format($i->system_unit_cost,4) }}</td><td>Rs. {{ number_format($i->supplier_line_total,2) }}</td><td>Rs. {{ number_format($i->allocated_extra_cost,2) }}</td><td>Rs. {{ number_format($i->landed_unit_cost,6) }}</td><td>Rs. {{ number_format($i->new_average_cost,6) }}</td></tr>@endforeach</tbody></table></div>

@if($purchase->paymentAllocations->count() > 0)
<div class="mt-8">
    <h2 class="font-serif text-xl mb-4">Payments</h2>
    <div class="card overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="text-left p-3">Date</th>
                    <th class="text-left p-3">Method</th>
                    <th class="text-left p-3">Amount</th>
                    <th class="text-left p-3">Reference / Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase->paymentAllocations as $allocation)
                @php $pay = $allocation->payment; @endphp
                <tr class="border-t border-slate-100">
                    <td class="p-3">{{ $pay->payment_date->format('d M Y') }}</td>
                    <td class="p-3">{{ $pay->method->name }}</td>
                    <td class="p-3 font-semibold">Rs. {{ number_format($allocation->amount, 2) }}</td>
                    <td class="p-3">
                        @if($pay->method->code === 'own_cheque' && $pay->cheque)
                            Cheque No: {{ $pay->cheque->cheque_number }} ({{ $pay->cheque->bank }}) - Due: {{ $pay->cheque->cheque_date?->format('d M Y') ?? 'N/A' }}
                        @elseif($pay->method->code === 'endorsed_cheque' && $pay->cheque)
                            Endorsed Cheque: {{ $pay->cheque->cheque_number }} ({{ $pay->cheque->bank }})
                        @else
                            {{ $pay->reference ?: '-' }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="mt-5 ml-auto grid max-w-4xl grid-cols-2 gap-3 md:grid-cols-5">@foreach(['Supplier Total'=>$purchase->supplier_total,'Paid / Cleared'=>$purchase->paid_total,'Pending Cheques'=>$purchase->pending_total,'Due'=>$purchase->due_total,'Returned'=>$purchase->returned_total] as $label=>$value)<div class="card p-4"><div class="text-xs text-slate-400">{{ $label }}</div><div class="mt-1 text-xl font-bold">Rs. {{ number_format($value,2) }}</div></div>@endforeach</div>
@endsection
