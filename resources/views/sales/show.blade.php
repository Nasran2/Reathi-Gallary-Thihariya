@extends('layouts.app') 
@section('title', $sale->invoice_no) 
@section('content')

<div class="mx-auto max-w-5xl" x-data="saleShow()">
    @if(session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="ti ti-circle-check-filled text-lg"></i>
                <span class="font-bold">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <a class="text-sm text-slate-500 hover:text-slate-700" href="{{ route('sales.index') }}">← Back to Sales</a>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="font-serif text-3xl text-ink">{{ $sale->invoice_no }}</h1>
                <span class="badge {{ $sale->sale_type==='remnant'?'bg-amber-100 text-amber-800':'bg-teal/10 text-teal' }}">{{ strtoupper($sale->sale_type) }}</span>
            </div>
            <p class="text-sm text-slate-500 mt-1">{{ $sale->sold_at->format('d M Y, H:i A') }} · <span class="font-semibold">{{ $sale->customer?->name ?? 'Walk-in Customer' }}</span></p>
        </div>
        
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('pos.'.$sale->sale_type) }}" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-lg hover:bg-slate-200 transition">
                New Sale
            </a>
            <a href="{{ route('invoice.public', $sale->publicToken->token) }}" target="_blank" class="px-4 py-2 border border-slate-200 text-slate-700 font-bold rounded-lg hover:bg-slate-50 transition">
                Public Link ↗
            </a>
            <button @click="printReceipt()" class="px-4 py-2 bg-indigo-500 text-white font-bold rounded-lg hover:bg-indigo-600 transition shadow-sm flex items-center gap-2">
                <i class="ti ti-printer"></i> Print Receipt
            </button>
            <button @click="openSmsModal()" class="px-4 py-2 bg-emerald-500 text-white font-bold rounded-lg hover:bg-emerald-600 transition shadow-sm flex items-center gap-2">
                <i class="ti ti-message-circle"></i> Send SMS
            </button>
            <a href="{{ route('sales.returns',['sale_id'=>$sale->id]) }}" class="px-4 py-2 bg-amber-100 text-amber-800 font-bold rounded-lg">Return Items</a>
        </div>
    </div>

    <section class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-sm">
                    <tr>
                        <th class="px-6 py-3 font-semibold text-slate-600">Product</th>
                        <th class="px-6 py-3 font-semibold text-slate-600">Unit</th>
                        <th class="px-6 py-3 font-semibold text-slate-600">Qty</th>
                        <th class="px-6 py-3 font-semibold text-slate-600">Price</th>
                        <th class="px-6 py-3 font-semibold text-slate-600">Discount</th>
                        <th class="px-6 py-3 font-semibold text-slate-600">Net</th>
                        @can('view-cost')
                        <th class="px-6 py-3 font-semibold text-slate-600">Cost snapshot</th>
                        <th class="px-6 py-3 font-semibold text-slate-600">Profit</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($sale->items as $i)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <div class="font-semibold text-slate-800">{{ $i->product->name }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ $i->product->sku }} {{ $i->remnant?'· '.$i->remnant->remnant_no:'' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $i->unit->symbol }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-slate-800">{{ $i->quantity+0 }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">Rs. {{ number_format($i->unit_price,2) }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">Rs. {{ number_format($i->discount_amount,2) }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-teal-700">Rs. {{ number_format($i->net_revenue,2) }}</td>
                        @can('view-cost')
                        <td class="px-6 py-4 text-sm text-slate-600">Rs. {{ number_format($i->cost_at_sale,4) }} × {{ $i->base_quantity+0 }}</td>
                        <td class="px-6 py-4 text-sm font-semibold {{ $i->profit<0?'text-red-600':'text-emerald-600' }}">Rs. {{ number_format($i->profit,2) }}</td>
                        @endcan
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="grid gap-6 border-t border-slate-100 p-6 md:grid-cols-2 bg-slate-50/30">
            <div>
                <h3 class="text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wider">Payment Details</h3>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    @forelse($sale->payments as $p)
                    <div class="flex justify-between items-center text-sm py-1">
                        <span class="text-slate-600">{{ $p->method->name }} <span class="text-xs text-slate-400">{{ $p->reference?'· '.$p->reference:'' }}</span></span>
                        <strong class="text-slate-800">Rs. {{ number_format($p->amount,2) }}</strong>
                    </div>
                    @empty
                    <div class="flex items-center gap-2 text-amber-600 text-sm font-semibold bg-amber-50 p-2 rounded border border-amber-100">
                        <i class="ti ti-alert-circle"></i> This is a full credit/due sale.
                    </div>
                    @endforelse
                </div>
            </div>
            
            <div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal</span>
                        <span class="font-medium">Rs. {{ number_format($sale->subtotal,2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Discount</span>
                        <span class="text-red-500 font-medium">− Rs. {{ number_format($sale->discount_total,2) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-slate-100 pt-3 text-lg font-bold text-ink">
                        <span>Total Payable</span>
                        <span>Rs. {{ number_format($sale->grand_total,2) }}</span>
                    </div>
                    @if($sale->due_total > 0)
                    <div class="flex justify-between text-amber-700 bg-amber-50 p-2 rounded mt-2">
                        <span class="font-semibold">Due Amount</span>
                        <strong class="font-bold text-lg">Rs. {{ number_format($sale->due_total,2) }}</strong>
                    </div>
                    @endif
                    <div class="flex justify-between text-emerald-700 bg-emerald-50 p-2 rounded mt-2"><span class="font-semibold">Paid / Cleared</span><strong>Rs. {{ number_format($sale->paid_total,2) }}</strong></div>
                    @if($sale->pending_total > 0)<div class="flex justify-between text-blue-700 bg-blue-50 p-2 rounded mt-2"><span class="font-semibold">Pending Cheque</span><strong>Rs. {{ number_format($sale->pending_total,2) }}</strong></div>@endif
                    @if($sale->returned_total > 0)<div class="flex justify-between text-amber-700 bg-amber-50 p-2 rounded mt-2"><span class="font-semibold">Returned</span><strong>Rs. {{ number_format($sale->returned_total,2) }}</strong></div>@endif
                    @can('view-cost')
                    <div class="flex justify-between {{ $sale->profit_total<0?'text-red-600 bg-red-50':'text-emerald-600 bg-emerald-50' }} p-2 rounded mt-2">
                        <span class="font-semibold">Net Profit / Loss</span>
                        <strong class="font-bold text-lg">Rs. {{ number_format($sale->profit_total,2) }}</strong>
                    </div>
                    @endcan
                </div>
            </div>
        </div>
    </section>
    
    <iframe id="print-frame" style="display:none;" src="{{ url('/sales/'.$sale->id.'/print') }}"></iframe>

    <!-- SMS Modal -->
    <div x-show="smsModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div @click.away="smsModal = false" class="w-full max-w-md rounded-2xl bg-white shadow-xl overflow-hidden p-6 relative">
            
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
                <button type="button" @click="smsModal = false; smsSuccess = false;" class="px-6 py-2.5 font-bold text-white bg-slate-800 rounded-xl hover:bg-slate-900 w-full">Done</button>
            </div>
            
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold text-slate-800">Send SMS Receipt</h3>
                <button @click="smsModal = false" class="text-slate-400 hover:text-slate-600"><i class="ti ti-x text-lg"></i></button>
            </div>
            
            <div x-show="!customerId" class="mb-4 text-center">
                <div class="bg-amber-50 text-amber-800 p-4 rounded-xl border border-amber-200 mb-4 text-sm text-left">
                    <i class="ti ti-alert-triangle mr-1"></i> This is a walk-in sale. You must attach a customer with a valid phone number to send an SMS.
                </div>
                
                <div class="text-left mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Select Existing Customer</label>
                    <select class="w-full rounded-xl border-slate-200 py-2.5 px-3 bg-slate-50 focus:bg-white transition" x-model="selectedCustomer">
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
            
            <div x-show="customerId && !customerPhone" class="mb-4">
                <div class="bg-blue-50 text-blue-800 p-4 rounded-xl border border-blue-200 mb-4 text-sm">
                    Customer <strong>{{ $sale->customer?->name }}</strong> has no phone number on record. Please provide one.
                </div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Mobile Number</label>
                <input type="text" x-model="customerPhone" class="w-full rounded-xl border-slate-200 py-2.5 px-3 text-lg font-bold" placeholder="07XXXXXXXX">
            </div>
            
            <div x-show="customerId && customerPhone" class="mb-6">
                <p class="text-slate-600 mb-2">Send receipt via SMS to:</p>
                <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <div class="h-10 w-10 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-lg">
                        <i class="ti ti-user"></i>
                    </div>
                    <div>
                        <div class="font-bold text-slate-800">{{ $sale->customer?->name }}</div>
                        <div class="text-slate-500 font-mono text-sm" x-text="customerPhone"></div>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3 justify-end pt-2">
                <button type="button" @click="smsModal = false" class="px-5 py-2.5 font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">Cancel</button>
                <button type="button" @click="processSms()" class="px-6 py-2.5 font-bold text-white bg-emerald-500 rounded-xl shadow-sm hover:bg-emerald-600 transition flex items-center gap-2" :disabled="!canSendSms()">
                    <i class="ti ti-send"></i> Send SMS
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function saleShow() {
    return {
        saleId: {{ $sale->id }},
        customerId: {{ $sale->customer_id ?? 'null' }},
        customerPhone: '{{ $sale->customer?->mobile ?? '' }}',
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
        
        printReceipt() {
            let iframe = document.getElementById('print-frame');
            iframe.contentWindow.print();
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
                // If we need to attach or create a customer
                if (!this.customerId || (this.customerId && !this.customerPhone)) {
                    let payload = {};
                    if (!this.customerId && this.selectedCustomer) {
                        payload = { attach_customer_id: this.selectedCustomer };
                    } else if (!this.customerId && this.newCustomerName) {
                        payload = { new_customer_name: this.newCustomerName, new_customer_phone: this.newCustomerPhone };
                    } else if (this.customerId && this.customerPhone) {
                        payload = { update_phone: this.customerPhone };
                    }
                    
                    // Call an endpoint to update the sale/customer before sending
                    let updateRes = await fetch(`/sales/${this.saleId}/update-customer`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(payload)
                    });
                    
                    if (!updateRes.ok) throw new Error('Failed to update customer');
                }
                
                // Now trigger the actual SMS
                let smsRes = await fetch(`/sales/${this.saleId}/sms`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
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
@endpush
@endsection
