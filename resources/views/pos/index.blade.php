@extends('layouts.pos') @section('title',$type==='remnant'?'Remnant POS':'Main POS') @section('content')
@php
if($type==='main'){$catalog=$items->map(fn($p)=>['key'=>'p'.$p->id,'product_id'=>$p->id,'name'=>$p->name,'sku'=>$p->sku,'barcode'=>$p->barcode,'category_id'=>$p->category_id,'colour'=>$p->colour,'stock'=>(float)($p->balances->first()?->quantity??0),'units'=>$p->productUnits->where('can_sell',true)->map(fn($u)=>['unit_id'=>$u->unit_id,'name'=>$u->unit->name,'symbol'=>$u->unit->symbol,'rate'=>(float)$u->conversion_rate,'price'=>(float)($p->main_selling_price * $u->conversion_rate)])->values()])->values();}
else{$catalog=$items->map(fn($r)=>['key'=>'r'.$r->id,'product_id'=>$r->product_id,'remnant_id'=>$r->id,'name'=>$r->product->name,'sku'=>$r->product->sku,'barcode'=>$r->barcode,'category_id'=>$r->product->category_id,'colour'=>$r->product->colour,'remnant_no'=>$r->remnant_no,'stock'=>(float)$r->remaining_base_quantity,'cost'=>(float)$r->cost_per_base_unit,'normal_price'=>(float)($r->product->main_selling_price * $r->conversion_rate),'units'=>[['unit_id'=>$r->unit_id,'name'=>$r->unit->name,'symbol'=>$r->unit->symbol,'rate'=>(float)$r->conversion_rate,'price'=>(float)($r->product->remnant_selling_price * $r->conversion_rate)]],'display_qty'=>(float)$r->remaining_quantity])->values();}
@endphp
<div x-data='pos(@json($catalog),"{{ $type }}", @json($methods))' class="-m-4 lg:-m-8" @keydown.window="handleKey($event)">
 <div class="border-b {{ $type==='remnant'?'border-amber-300 bg-amber-50':'border-slate-200 bg-white' }} px-4 py-4 lg:px-7"><div class="flex flex-wrap items-center gap-4"><div class="mr-auto"><div class="flex items-center gap-2"><span class="badge {{ $type==='remnant'?'bg-amber-200 text-amber-900':'bg-teal/10 text-teal' }}">{{ $type==='remnant'?'CLEARANCE':'MAIN INVENTORY' }}</span><h1 class="font-serif text-2xl text-ink">{{ $type==='remnant'?'Remnant / Cut-Piece Sale':'Main Point of Sale' }}</h1></div><p class="mt-1 text-xs text-slate-500">{{ $type==='remnant'?'Below-cost sales are allowed and reported as losses.':'Only main inventory can be sold here.' }}</p></div><div class="relative w-full md:w-96"><input class="w-full pl-4" x-model="search" @keydown.enter.prevent="addFirst()" placeholder="Search or scan barcode…" autofocus><span class="absolute right-3 top-2.5 text-slate-400">⌕</span></div></div></div>
 <div class="grid min-h-[calc(100vh-145px)] xl:grid-cols-[1fr_430px]"><section class="p-4 lg:p-6"><div class="mb-4 flex gap-2 overflow-x-auto pb-1"><button class="btn whitespace-nowrap" :class="category===''?'bg-ink text-white':'bg-white text-slate-600'" @click="category=''">All fabrics</button>@foreach($categories as $c)<button class="btn whitespace-nowrap bg-white text-slate-600" :class="category==='{{ $c->id }}'&&'!bg-ink !text-white'" @click="category='{{ $c->id }}'">{{ $c->name }}</button>@endforeach</div>
 <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4"><template x-for="item in filtered" :key="item.key"><button type="button" @click="add(item)" class="card group p-4 text-left transition hover:-translate-y-0.5 hover:border-teal/30 hover:shadow-md"><div class="mb-4 flex h-28 items-center justify-center rounded-xl" :class="type==='remnant'?'bg-amber-50':'bg-gradient-to-br from-sand/60 to-teal/5'"><div class="text-center"><div class="font-serif text-4xl text-slate-300" x-text="item.name.charAt(0)"></div><div x-show="item.remnant_no" class="mt-2 rounded-md bg-amber-200 px-2 py-0.5 text-[10px] font-bold text-amber-900" x-text="item.remnant_no"></div></div></div><div class="flex justify-between gap-2"><div><div class="line-clamp-1 font-semibold" x-text="item.name"></div><div class="text-xs text-slate-400"><span x-text="item.sku"></span><span x-show="item.colour"> · <span x-text="item.colour"></span></span></div></div><div class="text-right"><div class="font-bold text-teal">Rs. <span x-text="money(item.units[0]?.price)"></span></div><div class="text-[10px] text-slate-400">per <span x-text="item.units[0]?.symbol"></span></div></div></div><div class="mt-3 flex justify-between border-t border-slate-100 pt-3 text-xs"><span class="text-slate-400">Available</span><strong x-text="type==='remnant'?`${item.display_qty} ${item.units[0].symbol}`:`${available(item,item.units[0])} ${item.units[0]?.symbol}`"></strong></div><div x-show="type==='remnant'" class="mt-2 flex justify-between text-xs"><span class="text-slate-400 line-through">Normal Rs.<span x-text="money(item.normal_price)"></span></span><span class="font-semibold text-amber-700" x-text="item.cost>item.units[0].price?'May report loss':'Clearance'"></span></div></button></template></div><div x-show="filtered.length===0" class="py-24 text-center text-slate-400">No matching stock found.</div></section>
 <aside class="border-l border-slate-200 bg-white"><div class="border-b border-slate-100 p-5"><div class="flex items-center justify-between"><h2 class="font-serif text-2xl text-ink">Current cart</h2><button type="button" class="text-xs font-semibold text-red-500" @click="cart=[]" x-show="cart.length">Clear</button></div><div x-show="paymentError && !showPaymentModal" class="mt-4 rounded-lg bg-red-100 p-3 text-sm font-semibold text-red-700" x-text="paymentError" style="display:none;"></div><div class="mt-4"><label>Customer</label><select class="w-full" name="customer_id" x-model="customer"><option value="">Walk-in / cash sale</option>@foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->mobile?'· '.$c->mobile:'' }}</option>@endforeach</select></div></div>
 <div class="max-h-[42vh] min-h-48 overflow-y-auto p-4"><template x-for="(line,i) in cart" :key="line.lineKey"><div class="mb-3 rounded-xl border border-slate-100 p-3"><input type="hidden" :name="`items[${i}][product_id]`" :value="line.product_id"><input type="hidden" :name="`items[${i}][remnant_id]`" :value="line.remnant_id||''"><div class="flex justify-between gap-2"><div><div class="text-sm font-semibold" x-text="line.name"></div><div class="text-[11px] text-slate-400" x-text="line.remnant_no||line.sku"></div></div><button type="button" class="text-red-400" @click="cart.splice(i,1)">×</button></div><div class="mt-3 grid grid-cols-3 gap-2"><div><label>Unit</label><select class="w-full !px-2" :name="`items[${i}][unit_id]`" x-model.number="line.unit_id" @change="unitChanged(line)"><template x-for="u in line.units"><option :value="u.unit_id" x-text="u.symbol"></option></template></select></div><div><label>Quantity</label><input class="w-full !px-2" type="number" min="0.001" step="0.001" :max="line.maxDisplay" :name="`items[${i}][quantity]`" x-model.number="line.quantity" required></div><div><label>Unit price</label><input class="w-full !px-2" type="number" min="0" step="0.0001" :name="`items[${i}][unit_price]`" x-model.number="line.price" required></div></div><div class="mt-2 grid grid-cols-2 gap-2"><div><label>Discount</label><input class="w-full !px-2" type="number" min="0" step="0.01" :name="`items[${i}][discount_amount]`" x-model.number="line.discount"></div><div class="flex items-end justify-end pb-2 text-sm font-bold">Rs. <span x-text="money(lineTotal(line))"></span></div></div></div></template><div x-show="cart.length===0" class="py-14 text-center"><div class="text-4xl text-slate-200">◫</div><p class="mt-2 text-sm text-slate-400">Choose an item to begin</p></div></div>
 <div class="border-t border-slate-100 bg-white p-5"><div class="space-y-3 text-sm font-semibold"><div class="flex justify-between text-slate-700"><span>Items</span><span x-text="cart.length"></span></div><div class="flex justify-between text-slate-700"><span>Subtotal</span><span>Rs <span x-text="money(subtotal)"></span></span></div><div class="flex justify-between text-slate-700"><span>Taxable Amount</span><span>Rs 0.00</span></div><div class="flex justify-between border-t border-slate-200 pt-3 text-lg font-bold text-ink"><span>Payable</span><span>Rs <span x-text="money(total)"></span></span></div></div><div class="mt-4 flex items-center gap-2"><label class="flex items-center gap-1 text-sm"><input type="radio" name="discount_type" value="fixed" checked> Fixed</label><label class="flex items-center gap-1 text-sm"><input type="radio" name="discount_type" value="percentage"> %</label><input class="w-full !px-3" type="number" min="0" step="0.01" value="0"></div><button type="button" @click="duePay()" class="mt-4 w-full rounded-xl border border-amber-200 bg-amber-50 py-3 font-semibold text-amber-700 transition hover:bg-amber-100" :disabled="submitting"><span x-text="submitting?'Processing...':'Due & Print (No Payment)'"></span></button><div class="mt-4 grid grid-cols-4 gap-2"><button type="button" @click="cart=[]" class="rounded-xl border border-red-100 bg-red-50 py-2.5 text-xs font-bold text-red-600 transition hover:bg-red-100">Clear</button><button type="button" class="rounded-xl border border-amber-100 bg-amber-50 py-2.5 text-xs font-bold text-amber-600 transition hover:bg-amber-100">Hold (F6)</button><button type="button" @click="quickPay()" class="rounded-xl bg-emerald-400 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-500">Quick (F10)</button><button type="button" @click="openPaymentModal()" class="rounded-xl bg-indigo-400 py-2.5 text-xs font-bold text-white transition hover:bg-indigo-500">Pay (F4)</button></div></div>
 </aside></div>
 
 <div x-show="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm" style="display: none;"><div class="w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-2xl" @click.away="showPaymentModal=false"><div class="flex items-center justify-between border-b border-slate-100 px-6 py-4"><h2 class="text-xl font-medium text-slate-700">Payment</h2><button type="button" @click="showPaymentModal=false" class="text-slate-400 hover:text-slate-600">✕</button></div><div x-show="paymentError" class="mx-6 mt-4 rounded-lg bg-red-100 p-3 text-sm font-semibold text-red-700" x-text="paymentError" style="display:none;"></div><div class="flex max-h-[70vh] overflow-y-auto"><div class="flex-1 p-6"><div class="mb-4 text-sm font-semibold text-slate-700">Advance Balance: Rs 0.00</div><div class="mb-6 rounded-xl bg-slate-100 p-4"><template x-for="(row, i) in paymentRows" :key="i"><div><div class="mb-4 flex gap-4"><div class="flex-1"><label class="mb-1 block text-sm font-bold">Amount:*</label><div class="relative"><span class="absolute left-3 top-2.5 text-slate-500">💵</span><input type="number" step="0.01" class="w-full pl-10" x-model.number="row.amount" placeholder="0.00" required></div></div><div class="flex-1"><label class="mb-1 block text-sm font-bold">Payment Method:*</label><div class="relative"><span class="absolute left-3 top-2.5 text-slate-500">💵</span><select class="w-full pl-10" x-model="row.method_id" required><option value="">Select Method</option>@foreach($methods as $m)@if($m->code!=='credit_due')<option value="{{ $m->id }}">{{ $m->name }}</option>@endif @endforeach</select></div></div></div><div class="mb-4 grid grid-cols-2 gap-4" x-show="methods.find(m => m.id == row.method_id)?.code.includes('cheque')"><div><label class="mb-1 block text-sm font-bold">Cheque No.*</label><input type="text" class="w-full" x-model="row.cheque_number" :required="methods.find(m => m.id == row.method_id)?.code.includes('cheque')"></div><div><label class="mb-1 block text-sm font-bold">Bank*</label><input type="text" class="w-full" x-model="row.bank" :required="methods.find(m => m.id == row.method_id)?.code.includes('cheque')"></div><div class="col-span-2"><label class="mb-1 block text-sm font-bold">Cheque Date*</label><input type="date" class="w-full" x-model="row.cheque_date" :required="methods.find(m => m.id == row.method_id)?.code.includes('cheque')"></div></div><div class="mb-4"><label class="mb-1 block text-sm font-bold">Payment note:</label><textarea class="w-full" rows="3" x-model="row.note"></textarea></div><div x-show="paymentRows.length > 1" class="mb-4 text-right"><button type="button" @click="paymentRows.splice(i,1)" class="text-sm font-bold text-red-500 hover:underline">Remove Row</button></div><hr class="my-4 border-slate-200" x-show="i < paymentRows.length - 1"></div></template><button type="button" @click="paymentRows.push({amount:0, method_id:'', note:'', cheque_number: '', bank: '', cheque_date: ''})" class="w-full rounded-lg bg-indigo-500 py-2.5 font-bold text-white transition hover:bg-indigo-600">Add Payment Row</button></div><div class="flex gap-4"><div class="flex-1"><label class="mb-1 block text-sm font-bold">Sell note:</label><textarea class="w-full" rows="4" name="sell_note" x-model="sellNote" placeholder="Sell note"></textarea></div><div class="flex-1"><label class="mb-1 block text-sm font-bold">Staff note:</label><textarea class="w-full" rows="4" name="staff_note" x-model="staffNote" placeholder="Staff note"></textarea></div></div></div><div class="w-64 bg-[#ff851b] p-6 text-white"><div class="mb-6"><div class="text-sm font-medium opacity-90">Total Items:</div><div class="text-2xl font-bold" x-text="cart.length.toFixed(2)"></div><div class="mt-4 border-b border-white/20"></div></div><div class="mb-6"><div class="text-sm font-medium opacity-90">Total Payable:</div><div class="text-2xl font-bold">Rs <span x-text="money(total)"></span></div><div class="mt-4 border-b border-white/20"></div></div><div class="mb-6"><div class="text-sm font-medium opacity-90">Total Paying:</div><div class="text-2xl font-bold">Rs <span x-text="money(totalPaying)"></span></div><div class="mt-4 border-b border-white/20"></div></div><div class="mb-6"><div class="text-sm font-medium opacity-90">Change Return:</div><div class="text-2xl font-bold">Rs <span x-text="money(changeReturn)"></span></div><div class="mt-4 border-b border-white/20"></div></div><div><div class="text-sm font-medium opacity-90">Balance:</div><div class="text-2xl font-bold">Rs <span x-text="money(balance)"></span></div></div></div></div><div class="flex justify-end gap-2 border-t border-slate-100 bg-white p-4"><button type="button" @click="showPaymentModal=false" class="rounded-lg bg-slate-700 px-6 py-2.5 font-bold text-white transition hover:bg-slate-800">Close</button><button type="button" @click="submitSale()" class="rounded-lg bg-indigo-500 px-6 py-2.5 font-bold text-white transition hover:bg-indigo-600" :disabled="submitting"><span x-text="submitting?'Processing...':'Finalize Payment'"></span></button></div></div></div>
 
 <form id="checkout-form" method="post" action="{{ route('pos.checkout') }}" style="display:none;">@csrf<input type="hidden" name="sale_type" value="{{ $type }}"><input type="hidden" name="store_id" value="{{ $store->id }}"><input type="hidden" name="idempotency_key" :value="token"><input type="hidden" name="customer_id" :value="customer"><input type="hidden" name="notes" :value="sellNote"><input type="hidden" name="staff_note" :value="staffNote"></form>
</div>

@if(isset($saleSuccess) && $saleSuccess)
<div x-data="saleSuccessModal()" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm">
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden" x-show="!smsModal">
        <div class="bg-emerald-500 p-6 text-center text-white relative">
            <button @click="closeAll()" class="absolute right-4 top-4 text-white/80 hover:text-white"><i class="ti ti-x text-2xl"></i></button>
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/20 mb-4">
                <i class="ti ti-check text-4xl"></i>
            </div>
            <h2 class="text-3xl font-serif font-bold">Sale Completed!</h2>
            <p class="mt-2 text-emerald-100">{{ $saleSuccess->invoice_no }} · {{ $saleSuccess->customer?->name ?? 'Walk-in Customer' }}</p>
        </div>
        
        <div class="p-8">
            <div class="flex justify-between items-center bg-slate-50 p-4 rounded-xl border border-slate-100 mb-6">
                <div>
                    <div class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Total Amount</div>
                    <div class="text-2xl font-bold text-slate-800">Rs. {{ number_format($saleSuccess->grand_total, 2) }}</div>
                </div>
                @if($saleSuccess->due_total > 0)
                <div class="text-right">
                    <div class="text-sm font-semibold text-amber-600 uppercase tracking-wider">Due Amount</div>
                    <div class="text-2xl font-bold text-amber-700">Rs. {{ number_format($saleSuccess->due_total, 2) }}</div>
                </div>
                @endif
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <a href="{{ route('sales.pdf', $saleSuccess->id) }}" target="_blank" class="flex flex-col items-center justify-center p-4 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 rounded-xl text-indigo-700 transition font-bold gap-2">
                    <i class="ti ti-file-download text-3xl"></i> Download PDF Invoice
                </a>
                <button type="button" @click="openSmsModal()" class="flex flex-col items-center justify-center p-4 bg-emerald-50 hover:bg-emerald-100 border border-emerald-100 rounded-xl text-emerald-700 transition font-bold gap-2">
                    <i class="ti ti-message-circle text-3xl"></i> Send SMS Receipt
                </button>
            </div>
            
            <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                <button @click="closeAll()" class="btn-indigo px-8 py-3 w-full">Start New Sale</button>
            </div>
        </div>
    </div>
    
    <!-- SMS Modal (Nested state) -->
    <div x-show="smsModal" @click.away="smsModal = false" class="w-full max-w-md rounded-2xl bg-white shadow-xl overflow-hidden p-6 relative">
        <div x-show="smsSending" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-10 flex flex-col items-center justify-center">
            <div class="text-indigo-600 mb-2">
                <i class="ti ti-loader animate-spin text-4xl"></i>
            </div>
            <div class="font-semibold text-slate-700">Sending SMS...</div>
        </div>
        
        <div x-show="smsSuccess" class="absolute inset-0 bg-white z-10 flex flex-col items-center justify-center text-center p-6">
            <div class="h-16 w-16 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center mb-4">
                <i class="ti ti-check text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">SMS Sent Successfully!</h3>
            <p class="text-slate-500 text-sm mb-6">The receipt link has been sent to the customer.</p>
            <button type="button" @click="closeAll()" class="px-6 py-2.5 font-bold text-white bg-slate-800 rounded-xl hover:bg-slate-900 w-full">Done</button>
        </div>
        
        <div class="flex justify-between items-center mb-5">
            <button @click="smsModal = false" class="text-slate-400 hover:text-slate-600 flex items-center gap-1 text-sm font-semibold"><i class="ti ti-arrow-left text-lg"></i> Back</button>
            <h3 class="text-xl font-bold text-slate-800">Send SMS</h3>
        </div>
        
        <div x-show="!customerId" class="mb-4 text-center">
            <div class="bg-amber-50 text-amber-800 p-4 rounded-xl border border-amber-200 mb-4 text-sm text-left">
                <i class="ti ti-alert-triangle mr-1"></i> This is a walk-in sale. Attach a customer to send SMS.
            </div>
            <div class="text-left mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Select Existing Customer</label>
                <select class="w-full rounded-xl border-slate-200 py-2.5 px-3 bg-slate-50" x-model="selectedCustomer">
                    <option value="">-- Choose Customer --</option>
                    @foreach(\App\Models\Customer::whereNotNull('mobile')->orderBy('name')->get() as $c)
                        <option value="{{ $c->id }}" data-phone="{{ $c->mobile }}">{{ $c->name }} ({{ $c->mobile }})</option>
                    @endforeach
                </select>
            </div>
            <div class="relative flex items-center py-2 mb-4">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="flex-shrink-0 mx-4 text-slate-400 text-xs font-semibold uppercase">OR QUICK ADD</span>
                <div class="flex-grow border-t border-slate-200"></div>
            </div>
            <div class="text-left space-y-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">New Customer Name</label>
                    <input type="text" x-model="newCustomerName" class="w-full rounded-xl border-slate-200 py-2 px-3" placeholder="John Doe">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Mobile Number</label>
                    <input type="text" x-model="newCustomerPhone" class="w-full rounded-xl border-slate-200 py-2 px-3" placeholder="07XXXXXXXX">
                </div>
            </div>
        </div>
        
        <div x-show="customerId" class="mb-6">
            <p class="text-slate-600 mb-2">Send receipt via SMS to:</p>
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                <div class="font-bold text-slate-800 mb-3 flex items-center gap-2">
                    <div class="h-8 w-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold">
                        <i class="ti ti-user"></i>
                    </div>
                    {{ $saleSuccess->customer?->name }}
                </div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Mobile Number</label>
                <input type="text" x-model="customerPhone" class="w-full rounded-lg border-slate-200 py-2.5 px-3 font-mono text-lg font-bold" placeholder="07XXXXXXXX">
            </div>
        </div>
        
        <div class="flex gap-3 justify-end pt-2 mt-4 border-t border-slate-100">
            <button type="button" @click="smsModal = false" class="px-5 py-2.5 font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">Cancel</button>
            <button type="button" @click="processSms()" class="px-6 py-2.5 font-bold text-white bg-emerald-500 rounded-xl shadow-sm hover:bg-emerald-600 transition flex items-center gap-2" :disabled="!canSendSms()">
                <i class="ti ti-send"></i> Send SMS
            </button>
        </div>
    </div>
</div>
<script>
function saleSuccessModal() {
    return {
        saleId: {{ $saleSuccess->id }},
        customerId: {{ $saleSuccess->customer_id ?? 'null' }},
        customerPhone: '{{ $saleSuccess->customer?->mobile ?? '' }}',
        smsModal: false,
        smsSending: false,
        smsSuccess: false,
        
        selectedCustomer: '',
        newCustomerName: '',
        newCustomerPhone: '',
        
        openSmsModal() {
            this.smsModal = true;
            this.smsSuccess = false;
        },
        
        closeAll() {
            window.location.href = "{{ route('pos.' . $type) }}";
        },
        
        canSendSms() {
            if (this.customerId && this.customerPhone) return true;
            if (!this.customerId) {
                if (this.selectedCustomer) return true;
                if (this.newCustomerName.trim() && this.newCustomerPhone.trim()) return true;
            }
            if (this.customerId && !this.customerPhone) {
                if (this.customerPhone.trim()) return true;
            }
            return false;
        },
        
        async processSms() {
            if (!this.canSendSms()) return;
            this.smsSending = true;
            try {
                if (!this.customerId || (this.customerId && !this.customerPhone)) {
                    let payload = {};
                    if (!this.customerId && this.selectedCustomer) {
                        payload = { attach_customer_id: this.selectedCustomer };
                    } else if (!this.customerId && this.newCustomerName) {
                        payload = { new_customer_name: this.newCustomerName, new_customer_phone: this.newCustomerPhone };
                    } else if (this.customerId && this.customerPhone) {
                        payload = { update_phone: this.customerPhone };
                    }
                    let updateRes = await fetch(`/sales/${this.saleId}/update-customer`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        body: JSON.stringify(payload)
                    });
                    if (!updateRes.ok) throw new Error('Failed to update customer');
                }
                
                let smsRes = await fetch(`/sales/${this.saleId}/sms`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                });
                if (!smsRes.ok) throw new Error('Failed to send SMS');
                
                this.smsSending = false;
                this.smsSuccess = true;
            } catch (e) {
                this.smsSending = false;
                alert('An error occurred: ' + e.message);
            }
        }
    }
}
</script>
@endif

@push('scripts')<script>function pos(catalog,type,methods){return{catalog,type,methods,search:'',category:'',cart:[],customer:'',token:'{{ \Illuminate\Support\Str::uuid() }}',submitting:false,showPaymentModal:false,paymentRows:[],sellNote:'',staffNote:'',paymentError:'',showError(msg){this.paymentError=msg;setTimeout(()=>this.paymentError='',4000);},get filtered(){let s=this.search.toLowerCase();return this.catalog.filter(x=>(!this.category||String(x.category_id)===this.category)&&(!s||`${x.name} ${x.sku} ${x.barcode||''} ${x.remnant_no||''} ${x.colour||''}`.toLowerCase().includes(s)))},addFirst(){if(this.filtered[0])this.add(this.filtered[0])},available(item,u){return u?Number(item.stock/u.rate).toFixed(3):0},add(item){let u=item.units[0];if(!u)return;let key=item.key+'-'+u.unit_id,found=this.cart.find(x=>x.lineKey===key);if(found&&type==='main'){found.quantity+=1;return}this.cart.push({...item,lineKey:key,unit_id:u.unit_id,quantity:type==='remnant'?item.display_qty:1,price:u.price,discount:0,maxDisplay:Number(item.stock/u.rate).toFixed(3)})},unitChanged(line){let u=line.units.find(x=>x.unit_id==line.unit_id);line.price=u.price;line.maxDisplay=Number(item.stock/u.rate).toFixed(3);line.lineKey=line.key+'-'+u.unit_id},lineTotal(l){return Math.max(0,(Number(l.quantity)||0)*(Number(l.price)||0)-(Number(l.discount)||0))},get subtotal(){return this.cart.reduce((s,l)=>s+(Number(l.quantity)||0)*(Number(l.price)||0),0)},get discount(){return this.cart.reduce((s,l)=>s+(Number(l.discount)||0),0)},get total(){return Math.max(0,this.subtotal-this.discount)},get totalPaying(){return this.paymentRows.reduce((s,r)=>s+(Number(r.amount)||0),0)},get changeReturn(){return Math.max(0,this.totalPaying-this.total)},get balance(){return Math.max(0,this.total-this.totalPaying)},money(v){return Number(v||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})},openPaymentModal(){if(this.cart.length===0)return;if(this.paymentRows.length===0){this.paymentRows=[{amount:this.total,method_id:{{ $methods->firstWhere('code','cash')?->id ?? '""' }},note:'',cheque_number:'',bank:'',cheque_date:''}];}this.showPaymentModal=true;},duePay(){if(this.cart.length===0)return;if(!this.customer){this.showError("Please select the customer to complete this");return;}this.paymentRows=[];this.submitSale();},quickPay(){if(this.cart.length===0)return;this.paymentRows=[{amount:this.total,method_id:{{ $methods->firstWhere('code','cash')?->id ?? '""' }},note:'',cheque_number:'',bank:'',cheque_date:''}];this.submitSale();},handleKey(e){if(this.showPaymentModal)return;if(e.key==='F4'){e.preventDefault();this.openPaymentModal();}if(e.key==='F10'){e.preventDefault();this.quickPay();}},submitSale(){if(this.cart.length===0)return;if(this.paymentRows.length && this.paymentRows.some(r=>!r.method_id)){this.showError("Please select a payment method for all rows.");return;}let missingCheque=this.paymentRows.some(r=>{let m=this.methods.find(m=>m.id==r.method_id);return m?.code.includes('cheque')&&(!r.cheque_number||!r.bank||!r.cheque_date);});if(missingCheque){this.showError("Cheque payments require cheque number, bank, and date.");return;}let missingRef=this.paymentRows.some(r=>{let m=this.methods.find(m=>m.id==r.method_id);return m?.requires_reference&&!r.note&&!m?.code.includes('cheque');});if(missingRef){this.showError("A payment reference (note) is required for the selected method.");return;}let hasCheque=this.paymentRows.some(r=>this.methods.find(m=>m.id==r.method_id)?.code.includes('cheque'));let hasDue=this.totalPaying<this.total;if((hasCheque||hasDue)&&!this.customer){this.showError("Please select the customer to complete this");return;}this.submitting=true;let form=document.getElementById('checkout-form');this.cart.forEach((c,i)=>{form.insertAdjacentHTML('beforeend',`<input type="hidden" name="items[${i}][product_id]" value="${c.product_id}"><input type="hidden" name="items[${i}][remnant_id]" value="${c.remnant_id||''}">`);form.insertAdjacentHTML('beforeend',`<input type="hidden" name="items[${i}][unit_id]" value="${c.unit_id}"><input type="hidden" name="items[${i}][quantity]" value="${c.quantity||0}"><input type="hidden" name="items[${i}][unit_price]" value="${c.price||0}"><input type="hidden" name="items[${i}][discount_amount]" value="${c.discount||0}">`)});this.paymentRows.forEach((r,i)=>{form.insertAdjacentHTML('beforeend',`<input type="hidden" name="payments[${i}][payment_method_id]" value="${r.method_id}"><input type="hidden" name="payments[${i}][amount]" value="${r.amount||0}"><input type="hidden" name="payments[${i}][reference]" value="${r.note||''}">`);if(this.methods.find(m=>m.id==r.method_id)?.code.includes('cheque')){form.insertAdjacentHTML('beforeend',`<input type="hidden" name="payments[${i}][cheque_number]" value="${r.cheque_number||''}"><input type="hidden" name="payments[${i}][bank]" value="${r.bank||''}"><input type="hidden" name="payments[${i}][cheque_date]" value="${r.cheque_date||''}">`);}});form.submit();}}}</script>@endpush @endsection
