@extends('layouts.app') @section('title','Purchases') @section('content')
<div class="mb-6 flex items-center justify-between"><div><h1 class="font-serif text-3xl text-ink">Purchases</h1><p class="text-sm text-slate-500">Only received purchases change stock.</p></div>
    <div class="flex gap-2">
        <a class="btn-soft" href="{{ route('purchases.returns.create') }}">Purchase Returns / Exchange</a>
        @can('purchases.create')<a class="btn-teal" href="{{ route('purchases.create') }}">+ Receive purchase</a>@endcan
    </div>
</div>

<div class="mb-5 flex flex-wrap items-center gap-3">
    <!-- Supplier Filter -->
    <form id="filterForm" method="GET" class="flex items-center">
        @if(request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
        @if(request('start')) <input type="hidden" name="start" value="{{ request('start') }}"> @endif
        @if(request('end')) <input type="hidden" name="end" value="{{ request('end') }}"> @endif
        
        <select name="supplier_id" class="text-sm border-slate-200 rounded-md py-1.5 pl-3 pr-8 bg-white" onchange="this.form.submit()">
            <option value="">All Suppliers</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
    </form>

    <div class="w-px h-6 bg-slate-200 mx-1"></div> <!-- Divider -->

    @php
        $filters = [
            '' => 'All Time', 'today' => 'Today', 'yesterday' => 'Yesterday', 'this_week' => 'This Week',
            'last_week' => 'Last Week', 'this_month' => 'This Month', 'last_month' => 'Last Month',
            'this_year' => 'This Year', 'custom' => 'Custom'
        ];
        $current = request('filter', '');
        $supplierParam = request('supplier_id') ? ['supplier_id' => request('supplier_id')] : [];
    @endphp
    @foreach($filters as $key => $label)
        <a href="{{ route('purchases.index', array_merge(['filter' => $key], $supplierParam)) }}" class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors {{ $current === $key ? 'bg-teal-50 border-teal-200 text-teal-800' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            {{ $label }}
        </a>
    @endforeach

    @if($current === 'custom')
    <form class="ml-2 flex items-center gap-2 bg-white p-1 rounded-lg border border-slate-200" method="GET">
        <input type="hidden" name="filter" value="custom">
        @if(request('supplier_id')) <input type="hidden" name="supplier_id" value="{{ request('supplier_id') }}"> @endif
        <input type="date" name="start" value="{{ request('start') }}" class="text-sm border-0 focus:ring-0" required>
        <span class="text-slate-400 text-sm">to</span>
        <input type="date" name="end" value="{{ request('end') }}" class="text-sm border-0 focus:ring-0" required>
        <button type="submit" class="btn-soft text-xs py-1.5">Apply</button>
    </form>
    @endif
</div>

<div x-data="purchasePayments(@json($methods), @json($eligibleCheques))"><div class="card overflow-x-auto"><table><thead><tr><th>Purchase</th><th>Date</th><th>Supplier</th><th>Supplier invoice</th><th>Invoice cost</th><th>Due</th><th>Status</th><th class="text-right">Actions</th></tr></thead><tbody>@forelse($purchases as $p)<tr><td><a class="font-semibold text-teal" href="{{ route('purchases.show',$p) }}">{{ $p->purchase_no }}</a></td><td>{{ $p->purchase_date->format('d M Y') }}</td><td>{{ $p->supplier->name }}</td><td>{{ $p->supplier_invoice_no??'—' }}</td><td>Rs. {{ number_format($p->supplier_total,2) }}</td><td>Rs. {{ number_format($p->due_total,2) }}</td><td>@if($p->due_total > 0)
<span class="badge bg-amber-50 text-amber-700">Due</span>
@else
<span class="badge bg-emerald-50 text-emerald-700">Paid</span>
@endif</td><td class="text-right space-x-3">@if($p->due_total > 0)<button type="button" class="text-sm font-semibold text-green-500 hover:text-green-700" @click="openModal({{ $p->id }}, {{ $p->supplier_id }}, '{{ $p->purchase_no }}', {{ $p->due_total }})">Pay</button>@endif<a class="text-sm text-slate-500 hover:text-teal-600" href="{{ route('purchases.show', $p) }}">View</a><a class="text-sm text-indigo-500 hover:text-indigo-600" href="{{ route('purchases.pdf', $p) }}">PDF</a>@can('purchases.create')<a class="text-sm text-amber-500 hover:text-amber-600" href="{{ route('purchases.edit', $p) }}">Edit</a>@endcan @can('purchases.cancel')<form action="{{ route('purchases.destroy', $p) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this purchase? This will reverse stock and ledger entries. Purchases with payments cannot be deleted.')">@csrf @method('DELETE')<button type="submit" class="text-sm text-red-500 hover:text-red-700">Delete</button></form>@endcan</td></tr>@empty<tr><td colspan="8" class="py-12 text-center text-slate-400">No purchases recorded.</td></tr>@endforelse</tbody></table>
    </div>
    <div class="mt-5">{{ $purchases->links() }}</div>

    <!-- Payment Modal -->
    <template x-teleport="body">
        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm" style="display: none;">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6" @click.outside="showModal = false">
                <h3 class="text-xl font-serif mb-1">Pay Purchase</h3>
                <p class="text-sm text-slate-500 mb-4">Invoice: <span class="font-semibold text-ink" x-text="purchaseNo"></span></p>
                <form :action="payUrl" method="POST" x-ref="payForm">
                    @csrf
                    <input type="hidden" name="allocation_mode" value="manual">
                    <input type="hidden" :name="'allocations['+purchaseId+']'" x-model="amount">
                    <div class="space-y-4">
                        <div><label>Payment Method *</label><select class="w-full" name="payment_method_id" x-model.number="method_id" required><option value="">Select method</option><template x-for="m in methods"><option :value="m.id" x-text="m.name"></option></template></select></div>
                        <div><label>Amount *</label><input type="number" class="w-full" name="amount" min="0.01" step="0.01" :max="maxAmount" x-model="amount" required></div>
                        <div><label>Payment Date *</label><input type="date" class="w-full" name="payment_date" x-model="date" required></div>
                        
                        <div x-show="!isCheque()"><label>Reference</label><input class="w-full" name="reference" placeholder="Reference (Optional)"></div>
                        <div x-show="isOwnCheque()" class="grid grid-cols-2 gap-2"><div class="col-span-2"><label>Cheque Number *</label><input class="w-full" name="cheque_number" :required="isOwnCheque()"></div><div class="col-span-2"><label>Bank *</label><input class="w-full" name="bank" :required="isOwnCheque()"></div><div class="col-span-2"><label>Cheque Date *</label><input type="date" class="w-full" name="cheque_date" :required="isOwnCheque()"></div></div>
                        <div x-show="isEndorsedCheque()"><label>Customer Cheque *</label><select class="w-full" name="cheque_id" :required="isEndorsedCheque()"><option value="">Select customer cheque</option><template x-for="c in eligibleCheques"><option :value="c.id" x-text="c.cheque_number + ' (Rs.' + c.amount + ')'"></option></template></select></div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" class="btn-soft" @click="showModal = false">Cancel</button>
                        <button type="submit" class="btn-teal">Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection


@push('scripts')
<script>
function purchasePayments(methods, cheques) {
    return {
        methods,
        eligibleCheques: cheques,
        showModal: false,
        purchaseId: null,
        supplierId: null,
        purchaseNo: '',
        amount: 0,
        maxAmount: 0,
        method_id: '',
        date: '{{ now()->toDateString() }}',
        get payUrl() {
            if(!this.supplierId) return '';
            return '{{ route("suppliers.pay", "SUPPLIER_ID") }}'.replace('SUPPLIER_ID', this.supplierId);
        },
        openModal(pId, sId, pNo, due) {
            this.purchaseId = pId;
            this.supplierId = sId;
            this.purchaseNo = pNo;
            this.amount = due;
            this.maxAmount = due;
            this.method_id = '';
            this.date = '{{ now()->toDateString() }}';
            this.showModal = true;
        },
        isCheque() { return this.isOwnCheque() || this.isEndorsedCheque(); },
        isOwnCheque() { const m = this.methods.find(x => x.id == this.method_id); return m && m.code === 'own_cheque'; },
        isEndorsedCheque() { const m = this.methods.find(x => x.id == this.method_id); return m && m.code === 'endorsed_cheque'; }
    }
}
</script>
@endpush
