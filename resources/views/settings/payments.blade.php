@extends('layouts.app') 
@section('title', 'Payment Method Settings') 
@section('content')

<div class="mb-6">
    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">
        <a href="{{ route('dashboard') }}" class="hover:text-slate-600 transition">Dashboard</a> 
        <span>/</span>
        <span>Settings</span>
        <span>/</span>
        <span class="text-slate-600">Payment Methods</span>
    </div>
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-serif text-3xl text-ink">Payment Method Settings</h1>
            <p class="text-sm text-slate-500 mt-1">Configure payment methods available at POS checkout.</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ editMethod: null, deleteMethod: null }">
    <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-3">
        <div class="grid h-10 w-10 place-items-center rounded-lg bg-blue-50 text-blue-500">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
        </div>
        <div>
            <h2 class="font-bold text-slate-800 text-lg">Payment Methods</h2>
            <p class="text-xs text-slate-500 mt-0.5">Active payment types</p>
        </div>
    </div>
    
    <div class="p-6">
        <form method="post" action="{{ route('settings.payment-methods.store') }}" class="flex flex-wrap items-end gap-4 mb-8 bg-slate-50 p-5 rounded-xl border border-slate-100">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Name</label>
                <input class="w-full rounded-lg border-slate-200 py-2.5 shadow-sm" name="name" placeholder="E.g. Bank Transfer" required>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Code</label>
                <input class="w-full rounded-lg border-slate-200 py-2.5 shadow-sm" name="code" placeholder="bank_transfer" required>
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Bank Charge (%)</label>
                <input type="number" step="0.01" class="w-full rounded-lg border-slate-200 py-2.5 shadow-sm" name="bank_charge_percentage" placeholder="0.00">
            </div>
            <label class="flex items-center gap-2 normal-case py-3 cursor-pointer pr-4">
                <input type="hidden" name="requires_reference" value="0">
                <input type="checkbox" name="requires_reference" value="1" class="rounded h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300"> 
                <span class="text-sm font-semibold text-slate-700">Require reference</span>
            </label>
            <button class="px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700">Add Method</button>
        </form>
        
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-700">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Method Name</th>
                        <th class="px-6 py-3 font-semibold">Code</th>
                        <th class="px-6 py-3 font-semibold">Charge %</th>
                        <th class="px-6 py-3 font-semibold">Reference</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($methods as $m)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4 font-medium text-slate-800">{{ $m->name }}</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-xs font-mono">{{ $m->code }}</span></td>
                        <td class="px-6 py-4 font-semibold text-slate-700">{{ number_format($m->bank_charge_percentage, 2) }}%</td>
                        <td class="px-6 py-4">
                            @if($m->requires_reference)
                                <span class="text-amber-600 font-medium text-xs bg-amber-50 px-2 py-1 rounded">Required</span>
                            @else
                                <span class="text-slate-500 text-xs">Optional</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($m->active)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button @click="editMethod = {{ $m->toJson() }}" class="text-blue-600 hover:text-blue-800 font-semibold px-2">Edit</button>
                            <button @click="deleteMethod = {{ $m->id }}" class="text-red-600 hover:text-red-800 font-semibold px-2">Delete</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="editMethod" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div @click.away="editMethod = null" class="w-full max-w-md rounded-2xl bg-white shadow-xl overflow-hidden p-6">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-bold text-slate-800">Edit Payment Method</h3>
                <button @click="editMethod = null" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form :action="editMethod ? '{{ url('settings/payment-methods') }}/' + editMethod.id : ''" method="post" class="space-y-4">
                @csrf 
                @method('PUT')
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Name</label>
                    <input class="w-full rounded-lg border-slate-200 py-2" name="name" :value="editMethod ? editMethod.name : ''" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Code</label>
                    <input class="w-full rounded-lg border-slate-200 py-2 bg-slate-50 text-slate-500" name="code" :value="editMethod ? editMethod.code : ''" readonly>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Bank Charge (%)</label>
                    <input type="number" step="0.01" class="w-full rounded-lg border-slate-200 py-2" name="bank_charge_percentage" :value="editMethod ? editMethod.bank_charge_percentage : 0">
                </div>
                <label class="flex items-center gap-2 normal-case py-1 cursor-pointer">
                    <input type="hidden" name="requires_reference" value="0">
                    <input type="checkbox" name="requires_reference" value="1" :checked="editMethod ? editMethod.requires_reference : false" class="rounded text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-semibold text-slate-700">Require reference</span>
                </label>
                <label class="flex items-center gap-2 normal-case py-1 cursor-pointer">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" value="1" :checked="editMethod ? editMethod.active : false" class="rounded text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-semibold text-slate-700">Active</span>
                </label>
                <div class="flex gap-3 justify-end pt-4 mt-2 border-t border-slate-100">
                    <button type="button" @click="editMethod = null" class="px-4 py-2 font-semibold text-slate-600 hover:bg-slate-50 rounded-lg">Cancel</button>
                    <button class="px-5 py-2 font-semibold text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div x-show="deleteMethod" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div @click.away="deleteMethod = null" class="w-full max-w-sm rounded-2xl bg-white shadow-xl overflow-hidden p-6 text-center">
            <div class="h-14 w-14 rounded-full bg-red-100 text-red-600 grid place-items-center mx-auto mb-4">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Delete Payment Method?</h3>
            <p class="text-slate-500 text-sm mb-6">Are you sure you want to delete this payment method? This action cannot be undone.</p>
            <form :action="'{{ url('settings/payment-methods') }}/' + deleteMethod" method="post" class="flex gap-3 justify-center">
                @csrf 
                @method('DELETE')
                <button type="button" @click="deleteMethod = null" class="px-4 py-2 font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancel</button>
                <button class="px-5 py-2 font-semibold text-white bg-red-600 rounded-lg shadow-sm hover:bg-red-700">Delete</button>
            </form>
        </div>
    </div>
</div>

@endsection
