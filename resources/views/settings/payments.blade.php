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

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
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
            <label class="flex items-center gap-2 normal-case py-3 cursor-pointer pr-4">
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
                        <th class="px-6 py-3 font-semibold">Reference</th>
                        <th class="px-6 py-3 font-semibold text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($methods as $m)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4 font-medium text-slate-800">{{ $m->name }}</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-xs font-mono">{{ $m->code }}</span></td>
                        <td class="px-6 py-4">
                            @if($m->requires_reference)
                                <span class="text-amber-600 font-medium text-xs bg-amber-50 px-2 py-1 rounded">Required</span>
                            @else
                                <span class="text-slate-500 text-xs">Optional</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
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
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
