@extends('layouts.app') 
@section('title', 'Manual Sales Return') 

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <a class="text-sm text-slate-500" href="{{ route('sales.returns') }}">← Sales Returns</a>
        <h1 class="mt-2 font-serif text-3xl text-ink">Manual Sales Return</h1>
        <p class="text-sm text-slate-500">Return items without a bill, process cash refunds, or exchange for replacement products.</p>
    </div>
</div>

<div x-data="manualSaleReturnForm" class="space-y-6">
    <!-- Step 1: Optional Customer Selection -->
    <section class="card p-6">
        <h2 class="font-serif text-xl mb-4">1. Select Customer (Optional)</h2>
        <div>
            <select class="w-full max-w-md" x-model="customer_id" x-init="initSelect($el)">
                <option value="">Walk-in Customer / None</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}">{{ $c->name }} {{ $c->mobile ? '· '.$c->mobile : '' }}</option>
                @endforeach
            </select>
        </div>
    </section>

    <!-- Step 2: Items to Return -->
    <section class="card overflow-hidden">
        <div class="p-5 border-b flex justify-between items-center">
            <h2 class="font-serif text-xl">2. Items to Return</h2>
            <button type="button" class="btn-soft text-sm" @click="addReturnItem()">+ Add Item</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr>
                        <th class="p-3 border-b border-slate-100">Product</th>
                        <th class="p-3 border-b border-slate-100">Unit</th>
                        <th class="p-3 border-b border-slate-100">Qty</th>
                        <th class="p-3 border-b border-slate-100">Sold Price</th>
                        <th class="p-3 border-b border-slate-100">Return Value</th>
                        <th class="p-3 border-b border-slate-100"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, i) in items" :key="i">
                        <tr class="border-t border-slate-100">
                            <td class="p-3">
                                <select class="w-full min-w-[14rem]" x-model.number="item.product_id" @change="productChanged(item)" required x-init="initSelectItem($el, i, 'items')">
                                    <option value="">Select product</option>
                                    <template x-for="p in catalog" :key="p.id">
                                        <option :value="p.id" x-text="p.name + ' · ' + p.sku"></option>
                                    </template>
                                </select>
                            </td>
                            <td class="p-3">
                                <select class="w-24" x-model.number="item.unit_id" required>
                                    <template x-for="u in getProductUnits(item.product_id)" :key="u.id">
                                        <option :value="u.id" x-text="u.symbol"></option>
                                    </template>
                                </select>
                            </td>
                            <td class="p-3">
                                <input type="number" class="w-24" x-model.number="item.quantity" min="0.001" step="0.001" required>
                            </td>
                            <td class="p-3">
                                <input type="number" class="w-28" x-model.number="item.price" min="0" step="0.01" required>
                            </td>
                            <td class="p-3 font-semibold" x-text="'Rs. ' + money(item.quantity * item.price)"></td>
                            <td class="p-3 text-right">
                                <button type="button" class="text-red-500 hover:text-red-700 font-bold" @click="items.splice(i, 1)">✕</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="6" class="p-5 text-center text-slate-500">No items added to return.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="p-5 bg-slate-50 flex justify-end">
            <div class="text-right">
                <span class="text-sm text-slate-500">Total Return Value:</span>
                <span class="ml-2 font-bold text-xl" x-text="'Rs. ' + money(totalReturnValue)"></span>
            </div>
        </div>
    </section>

    <!-- Step 3: Settlement Type & Exchanges -->
    <section class="card p-6">
        <h2 class="font-serif text-xl mb-4">3. Settlement & Exchange</h2>
        
        <div class="mb-5 flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" x-model="settlement_type" value="cash_refund" class="text-teal-600 focus:ring-teal-500">
                <span>Cash Refund</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" x-model="settlement_type" value="exchange" class="text-teal-600 focus:ring-teal-500">
                <span>Exchange Products</span>
            </label>
        </div>

        <div x-show="settlement_type === 'exchange'" class="mt-6 border-t pt-5">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-lg">Replacement Items</h3>
                <button type="button" class="btn-soft text-sm" @click="addExchangeItem()">+ Add Replacement</button>
            </div>
            
            <div class="overflow-x-auto mb-4">
                <table class="w-full text-left">
                    <thead>
                        <tr>
                            <th class="p-2 border-b border-slate-100">Product</th>
                            <th class="p-2 border-b border-slate-100">Unit</th>
                            <th class="p-2 border-b border-slate-100">Qty</th>
                            <th class="p-2 border-b border-slate-100">Unit Price</th>
                            <th class="p-2 border-b border-slate-100">Total</th>
                            <th class="p-2 border-b border-slate-100"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(ex, i) in exchange_items" :key="i">
                            <tr class="border-t border-slate-100">
                                <td class="p-2">
                                    <select class="w-full min-w-[14rem]" x-model.number="ex.product_id" @change="exchangeProductChanged(ex)" required x-init="initSelectItem($el, i, 'exchange_items')">
                                        <option value="">Select product</option>
                                        <template x-for="p in catalog" :key="p.id">
                                            <option :value="p.id" x-text="p.name + ' · ' + p.sku"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="p-2">
                                    <select class="w-24" x-model.number="ex.unit_id" required>
                                        <template x-for="u in getProductUnits(ex.product_id)" :key="u.id">
                                            <option :value="u.id" x-text="u.symbol"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="p-2">
                                    <input type="number" class="w-24" x-model.number="ex.quantity" min="0.001" step="0.001" required>
                                </td>
                                <td class="p-2">
                                    <input type="number" class="w-28" x-model.number="ex.price" min="0" step="0.01" required>
                                </td>
                                <td class="p-2 font-semibold" x-text="'Rs. ' + money(ex.quantity * ex.price)"></td>
                                <td class="p-2 text-right">
                                    <button type="button" class="text-red-500 hover:text-red-700 font-bold" @click="exchange_items.splice(i, 1)">✕</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="exchange_items.length === 0">
                            <td colspan="6" class="p-3 text-center text-slate-400 text-sm">No replacement items added.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-slate-50 p-4 rounded-lg flex justify-between items-center border border-slate-200">
                <div>
                    <div class="text-sm text-slate-500">Replacement Cost: Rs. <span x-text="money(totalExchangeValue)"></span></div>
                    <div class="text-sm text-slate-500">Return Value: Rs. <span x-text="money(totalReturnValue)"></span></div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-semibold" :class="netDifference > 0 ? 'text-emerald-600' : 'text-red-600'">
                        <span x-text="netDifference > 0 ? 'Customer Pays (Cash In):' : (netDifference < 0 ? 'Store Refunds (Cash Out):' : 'Difference:')"></span>
                    </div>
                    <div class="text-2xl font-bold" :class="netDifference > 0 ? 'text-emerald-600' : (netDifference < 0 ? 'text-red-600' : 'text-slate-600')">
                        Rs. <span x-text="money(Math.abs(netDifference))"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 mt-6 md:grid-cols-2">
            <div>
                <label>Reason *</label>
                <input class="w-full" x-model="reason" placeholder="e.g. Returned manually">
            </div>
            <div>
                <label>Date *</label>
                <input type="date" class="w-full" x-model="return_date">
            </div>
        </div>
        <div class="mt-4">
            <label>Notes</label>
            <input class="w-full" x-model="notes" placeholder="Optional notes">
        </div>
        
        <div class="mt-6 flex justify-end gap-3">
            <a class="btn-soft" href="{{ route('sales.returns') }}">Cancel</a>
            <button class="btn-teal" @click="submitForm()" :disabled="isSubmitting || totalReturnValue === 0">
                <span x-show="!isSubmitting">Confirm Return</span>
                <span x-show="isSubmitting">Processing...</span>
            </button>
        </div>
    </section>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('manualSaleReturnForm', () => ({
        catalog: @json($catalog),
        customer_id: '',
        items: [],
        exchange_items: [],
        settlement_type: 'cash_refund', // 'cash_refund' or 'exchange'
        reason: 'Manual Return',
        notes: '',
        return_date: '{{ today()->format('Y-m-d') }}',
        isSubmitting: false,

        initSelect(element) {
            if (!element.tomselect) new TomSelect(element, { dropdownParent: 'body' });
        },

        initSelectItem(element, index, listType) {
            if (!element.tomselect) {
                let ts = new TomSelect(element, { 
                    dropdownParent: 'body',
                    onChange: (val) => {
                        this[listType][index].product_id = val;
                        if (listType === 'items') {
                            this.productChanged(this[listType][index]);
                        } else {
                            this.exchangeProductChanged(this[listType][index]);
                        }
                    }
                });
            }
        },

        get totalReturnValue() {
            return this.items.reduce((sum, item) => sum + ((Number(item.quantity) || 0) * (Number(item.price) || 0)), 0);
        },

        get totalExchangeValue() {
            return this.exchange_items.reduce((sum, item) => sum + ((Number(item.quantity) || 0) * (Number(item.price) || 0)), 0);
        },

        get netDifference() {
            return this.totalExchangeValue - this.totalReturnValue;
        },

        addReturnItem() {
            this.items.push({ product_id: '', unit_id: '', quantity: 1, price: 0 });
        },

        addExchangeItem() {
            this.exchange_items.push({ product_id: '', unit_id: '', quantity: 1, price: 0 });
        },

        getProductUnits(productId) {
            let p = this.catalog.find(c => c.id == productId);
            return p ? p.units : [];
        },

        productChanged(item) {
            let p = this.catalog.find(c => c.id == item.product_id);
            if (p) {
                item.unit_id = p.units[0]?.id || '';
                item.price = p.units[0]?.price || p.main_price;
            }
        },

        exchangeProductChanged(ex) {
            let p = this.catalog.find(c => c.id == ex.product_id);
            if (p) {
                ex.unit_id = p.units[0]?.id || '';
                ex.price = p.units[0]?.price || p.main_price;
            }
        },

        money(val) {
            return Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        async submitForm() {
            if (this.totalReturnValue === 0) {
                alert("Please select at least one item to return.");
                return;
            }
            
            // Validate all items have product and unit
            for (let i of this.items) {
                if (!i.product_id || !i.unit_id) {
                    alert("Please select product and unit for all return items.");
                    return;
                }
            }
            
            if (this.settlement_type === 'exchange') {
                for (let i of this.exchange_items) {
                    if (!i.product_id || !i.unit_id) {
                        alert("Please select product and unit for all exchange items.");
                        return;
                    }
                }
            }

            if (!this.reason) {
                alert("Please provide a reason.");
                return;
            }

            this.isSubmitting = true;

            let payload = {
                _token: '{{ csrf_token() }}',
                customer_id: this.customer_id || null,
                return_date: this.return_date,
                reason: this.reason,
                notes: this.notes,
                settlement: this.settlement_type,
                items: this.items
            };

            if (this.settlement_type === 'exchange') {
                payload.exchange_items = this.exchange_items;
            }

            try {
                let res = await fetch('{{ route('sales.returns.manual.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });

                let data = await res.json();
                if (res.ok) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || "An error occurred.");
                    this.isSubmitting = false;
                }
            } catch (e) {
                console.error(e);
                alert("Network error.");
                this.isSubmitting = false;
            }
        }
    }));
});
</script>
@endsection
