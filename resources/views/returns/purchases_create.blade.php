@extends('layouts.app') 
@section('title', 'Advanced Purchase Return') 

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <a class="text-sm text-slate-500" href="{{ route('purchases.index') }}">← Purchases</a>
        <h1 class="mt-2 font-serif text-3xl text-ink">New Purchase Return / Exchange</h1>
        <p class="text-sm text-slate-500">Return items, deduct from dues, or exchange for replacement products.</p>
    </div>
</div>

<div x-data="returnExchangeForm()" class="space-y-6">
    <!-- Step 1: Select Supplier and Bill -->
    <section class="card p-6">
        <h2 class="font-serif text-xl mb-4">1. Select Supplier & Invoice</h2>
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label>Supplier *</label>
                <select class="w-full" x-model="supplier_id" @change="fetchPurchases()" x-init="initSelect($el)">
                    <option value="">Select Supplier</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Purchase Invoice *</label>
                <select class="w-full" x-model="purchase_id" @change="fetchItems()" :disabled="!supplier_id">
                    <option value="">Select Invoice</option>
                    <template x-for="p in purchases" :key="p.id">
                        <option :value="p.id" x-text="p.purchase_no + ' (' + p.purchase_date + ')'"></option>
                    </template>
                </select>
            </div>
        </div>
    </section>

    <!-- Step 2: Select Items to Return -->
    <section class="card overflow-hidden" x-show="purchase_id" style="display: none;">
        <div class="p-5 border-b">
            <h2 class="font-serif text-xl">2. Items to Return</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr>
                        <th class="p-3 border-b border-slate-100">Product</th>
                        <th class="p-3 border-b border-slate-100">Purchased</th>
                        <th class="p-3 border-b border-slate-100">Available</th>
                        <th class="p-3 border-b border-slate-100">Return Qty</th>
                        <th class="p-3 border-b border-slate-100">Unit Cost</th>
                        <th class="p-3 border-b border-slate-100">Return Value</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, index) in items" :key="item.id">
                        <tr class="border-t border-slate-100">
                            <td class="p-3">
                                <div class="font-semibold" x-text="item.product_name"></div>
                                <div class="text-xs text-slate-400" x-text="item.product_sku"></div>
                            </td>
                            <td class="p-3" x-text="item.purchased_qty + ' ' + item.unit_symbol"></td>
                            <td class="p-3" x-text="item.available_qty + ' ' + item.unit_symbol"></td>
                            <td class="p-3">
                                <input type="number" class="w-24" x-model.number="item.return_qty" min="0" :max="item.available_qty" step="0.001">
                            </td>
                            <td class="p-3" x-text="'Rs. ' + money(item.cost)"></td>
                            <td class="p-3 font-semibold" x-text="'Rs. ' + money(item.return_qty * item.cost)"></td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="6" class="p-5 text-center text-slate-500">No items available for return on this invoice.</td>
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
    <section class="card p-6" x-show="purchase_id" style="display: none;">
        <h2 class="font-serif text-xl mb-4">3. Settlement & Exchange</h2>
        
        <div class="mb-5 flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" x-model="settlement_type" value="deduct" class="text-teal-600 focus:ring-teal-500">
                <span>Deduct from dues (Standard Return)</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" x-model="settlement_type" value="exchange" class="text-teal-600 focus:ring-teal-500">
                <span>Exchange Products (Same or Different)</span>
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
                            <th class="p-2 border-b border-slate-100">Unit Cost</th>
                            <th class="p-2 border-b border-slate-100">Total</th>
                            <th class="p-2 border-b border-slate-100"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(ex, i) in exchange_items" :key="i">
                            <tr class="border-t border-slate-100">
                                <td class="p-2">
                                    <select class="w-full min-w-56" x-model.number="ex.product_id" @change="exchangeProductChanged(ex)" required>
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
                                    <input type="number" class="w-28" x-model.number="ex.cost" min="0" step="0.0001" required>
                                </td>
                                <td class="p-2 font-semibold" x-text="'Rs. ' + money(ex.quantity * ex.cost)"></td>
                                <td class="p-2 text-right">
                                    <button type="button" class="text-red-500 hover:text-red-700" @click="exchange_items.splice(i, 1)">×</button>
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
                    <div class="text-sm font-semibold" :class="netDifference > 0 ? 'text-red-600' : 'text-emerald-600'">
                        <span x-text="netDifference > 0 ? 'Additional Cost to Supplier Ledger:' : 'Credit to Supplier Ledger:'"></span>
                    </div>
                    <div class="text-2xl font-bold" :class="netDifference > 0 ? 'text-red-600' : 'text-emerald-600'">
                        Rs. <span x-text="money(Math.abs(netDifference))"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 mt-6 md:grid-cols-2">
            <div>
                <label>Reason *</label>
                <input class="w-full" x-model="reason" placeholder="e.g. Defective items">
            </div>
            <div>
                <label>Date *</label>
                <input type="date" class="w-full" x-model="return_date">
            </div>
        </div>
        
        <div class="mt-6 flex justify-end gap-3">
            <a class="btn-soft" href="{{ route('purchases.index') }}">Cancel</a>
            <button class="btn-teal" @click="submitForm()" :disabled="isSubmitting || totalReturnValue === 0">
                <span x-show="!isSubmitting">Confirm Return / Exchange</span>
                <span x-show="isSubmitting">Processing...</span>
            </button>
        </div>
    </section>
</div>

<script>
function returnExchangeForm() {
    return {
        catalog: @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'average_cost' => (float)$p->average_cost, 'units' => $p->productUnits->map(fn($u) => ['id' => $u->unit_id, 'name' => $u->unit->name, 'symbol' => $u->unit->symbol])])),
        supplier_id: '',
        purchase_id: '',
        purchases: [],
        items: [],
        exchange_items: [],
        settlement_type: 'deduct', // 'deduct' or 'exchange'
        reason: '',
        return_date: '{{ today()->format('Y-m-d') }}',
        isSubmitting: false,

        initSelect(element) {
            if (!element.tomselect) new TomSelect(element, { dropdownParent: 'body' });
        },

        async fetchPurchases() {
            this.purchase_id = '';
            this.purchases = [];
            this.items = [];
            if (!this.supplier_id) return;
            let res = await fetch(`/api/purchases/supplier/${this.supplier_id}`);
            this.purchases = await res.json();
        },

        async fetchItems() {
            this.items = [];
            if (!this.purchase_id) return;
            let res = await fetch(`/api/purchases/items/${this.purchase_id}`);
            let fetchedItems = await res.json();
            this.items = fetchedItems.map(i => ({ ...i, return_qty: 0 }));
        },

        get totalReturnValue() {
            return this.items.reduce((sum, item) => sum + ((Number(item.return_qty) || 0) * Number(item.cost)), 0);
        },

        get totalExchangeValue() {
            return this.exchange_items.reduce((sum, item) => sum + ((Number(item.quantity) || 0) * Number(item.cost)), 0);
        },

        get netDifference() {
            return this.totalExchangeValue - this.totalReturnValue;
        },

        addExchangeItem() {
            this.exchange_items.push({ product_id: '', unit_id: '', quantity: 1, cost: 0, system_cost: 0 });
        },

        getProductUnits(productId) {
            let p = this.catalog.find(c => c.id == productId);
            return p ? p.units : [];
        },

        exchangeProductChanged(ex) {
            let p = this.catalog.find(c => c.id == ex.product_id);
            if (p) {
                ex.unit_id = p.units[0]?.id || '';
                ex.cost = p.average_cost;
                ex.system_cost = p.average_cost;
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
            if (!this.reason) {
                alert("Please provide a reason.");
                return;
            }

            this.isSubmitting = true;

            let payload = {
                _token: '{{ csrf_token() }}',
                purchase_id: this.purchase_id,
                return_date: this.return_date,
                reason: this.reason,
                settlement: this.settlement_type,
                items: {}
            };

            this.items.forEach(i => {
                if (i.return_qty > 0) payload.items[i.id] = i.return_qty;
            });

            if (this.settlement_type === 'exchange') {
                payload.exchange_items = this.exchange_items;
            }

            try {
                let res = await fetch('{{ route('purchases.returns.store') }}', {
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
    }
}
</script>
@endsection
