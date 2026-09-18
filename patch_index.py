import re

with open('resources/views/purchases/index.blade.php', 'r') as f:
    content = f.read()

# 1. Update the Status badge
old_badge = '<span class="badge bg-emerald-50 text-emerald-700">{{ ucfirst($p->status) }}</span>'
new_badge = '''@if($p->due_total > 0)
<span class="badge bg-amber-50 text-amber-700">Due</span>
@else
<span class="badge bg-emerald-50 text-emerald-700">Paid</span>
@endif'''
content = content.replace(old_badge, new_badge)

# 2. Add Pay button in Actions
old_actions = '<td class="text-right space-x-3"><a class="text-sm text-slate-500 hover:text-teal-600" href="{{ route(\'purchases.show\', $p) }}">View</a>'
new_actions = '<td class="text-right space-x-3">@if($p->due_total > 0)<button type="button" class="text-sm font-semibold text-green-500 hover:text-green-700" @click="openModal({{ $p->id }}, {{ $p->supplier_id }}, \'{{ $p->purchase_no }}\', {{ $p->due_total }})">Pay</button>@endif<a class="text-sm text-slate-500 hover:text-teal-600" href="{{ route(\'purchases.show\', $p) }}">View</a>'
content = content.replace(old_actions, new_actions)

# 3. Wrap table in x-data and append modal HTML and JS
if '<div class="card overflow-x-auto"' in content and 'x-data="purchasePayments' not in content:
    content = content.replace('<div class="card overflow-x-auto">', '<div x-data="purchasePayments(@json($methods), @json($eligibleCheques))"><div class="card overflow-x-auto">')
    
    modal_html = """
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
"""
    content = content.replace('</div><div class="mt-5">{{ $purchases->links() }}</div>@endsection', modal_html)
    
    js = """
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
"""
    content += js

with open('resources/views/purchases/index.blade.php', 'w') as f:
    f.write(content)

print("Patched index.blade.php successfully!")
