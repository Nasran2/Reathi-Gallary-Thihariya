@extends('layouts.app') 
@section('title','Sales') 
@section('content')
<div x-data="salesList()">

    <div class="mb-6 flex justify-between">
        <div>
            <h1 class="font-serif text-3xl text-ink">Sales</h1>
            <p class="text-sm text-slate-500">Completed main and remnant invoices with frozen historical profit.</p>
        </div>
        <div class="flex gap-2">
            <a class="btn-soft" href="{{ route('pos.remnant') }}">Remnant sale</a>
            <a class="btn-teal" href="{{ route('pos.main') }}">Main sale</a>
        </div>
    </div>

    <div class="card overflow-x-auto">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Due</th>
                    <th>Profit / loss</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $s)
                <tr>
                    <td><a class="font-semibold text-teal" href="{{ route('sales.show',$s) }}">{{ $s->invoice_no }}</a></td>
                    <td>{{ $s->sold_at->format('d M Y H:i') }}</td>
                    <td>{{ $s->customer?->name??'Walk-in' }}</td>
                    <td><span class="badge {{ $s->sale_type==='remnant'?'bg-amber-50 text-amber-700':'bg-teal-50 text-teal-700' }}">{{ ucfirst($s->sale_type) }}</span></td>
                    <td>Rs. {{ number_format($s->grand_total,2) }}</td>
                    <td>Rs. {{ number_format($s->paid_total,2) }}</td>
                    <td>Rs. {{ number_format($s->due_total,2) }}</td>
                    <td class="font-semibold {{ $s->profit_total<0?'text-red-600':'text-emerald-600' }}">Rs. {{ number_format($s->profit_total,2) }}</td>
                    <td class="text-right space-x-3">
                        <button type="button" class="text-sm text-slate-500 hover:text-teal-600" @click="openModal('edit', '{{ route('sales.edit', $s) }}')">Edit</button>
                        <button type="button" class="text-sm text-red-500 hover:text-red-700" @click="openModal('delete', '{{ route('sales.destroy', $s) }}')">Delete</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="py-12 text-center text-slate-400">No completed sales yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $sales->links() }}</div>

    <!-- Modal -->
    <div x-show="confirmModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm" style="display: none;" x-cloak>
        <div class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl" @click.away="confirmModal=false">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h2 class="text-xl font-medium text-slate-700" x-text="modalTitle"></h2>
                <button type="button" @click="confirmModal=false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">✕</button>
            </div>
            <div class="p-6">
                <p class="mb-6 text-slate-600" x-text="modalMessage"></p>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="confirmModal=false" class="btn bg-white text-slate-600 border border-slate-200 hover:bg-slate-50">Cancel</button>
                    
                    <a x-show="modalType === 'edit'" :href="modalActionUrl" class="btn bg-teal text-white hover:bg-teal-600">Proceed</a>
                    
                    <form x-show="modalType === 'delete'" :action="modalActionUrl" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn bg-red-500 text-white hover:bg-red-600">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
function salesList() {
    return {
        confirmModal: false, 
        modalType: '', 
        modalActionUrl: '', 
        modalTitle: '', 
        modalMessage: '', 
        openModal(type, url) { 
            this.modalType = type; 
            this.modalActionUrl = url; 
            if (type === 'delete') { 
                this.modalTitle = 'Delete Sale'; 
                this.modalMessage = 'Are you sure you want to delete this sale? This will reverse stock, ledgers, cheques, and bank fees.'; 
            } else { 
                this.modalTitle = 'Edit Sale'; 
                this.modalMessage = 'Editing will void this sale, return its stock, and load its items back into the POS cart. Proceed?'; 
            } 
            this.confirmModal = true; 
        }
    }
}
</script>
@endpush
@endsection
