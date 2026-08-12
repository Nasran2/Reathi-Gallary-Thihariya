<!DOCTYPE html><html lang="en" class="h-full"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title','Dashboard') · {{ \App\Models\BusinessSetting::read('business_name','Reathi Gallery') }}</title>@vite(['resources/css/app.css','resources/js/app.js'])</head><body class="min-h-full" x-data="{menu:false}"><div class="min-h-screen lg:flex">
<div x-show="menu" x-cloak class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" @click="menu=false"></div><aside :class="menu?'translate-x-0':'-translate-x-full'" class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-ink text-white transition lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"><div class="flex h-20 items-center gap-3 border-b border-white/10 px-6"><div class="grid h-10 w-10 place-items-center rounded-xl bg-teal font-serif text-xl">R</div><div><div class="font-semibold">{{ \App\Models\BusinessSetting::read('business_name','Reathi Gallery') }}</div><div class="text-xs text-slate-400">Textile commerce suite</div></div></div>
@php 
$nav = [
    ['Dashboard', 'dashboard', 'tabler-dashboard'],
    ['User Management', [
        ['Users', 'users.index'],
        ['Roles', 'roles.index'],
    ], 'tabler-users'],
    ['Products', [
        ['Products List', 'products.index'],
        ['Categories', 'categories.index'],
        ['Units', 'units.index'],
        ['Brands', 'brands.index'],
    ], 'tabler-box'],
    ['Purchase', [
        ['Purchase List', 'purchases.index'],
        ['Add Purchase', 'purchases.create'],
        ['Purchase Returns', 'purchases.returns'],
    ], 'tabler-shopping-cart'],
    ['Sales', [
        ['Main POS', 'pos.main'],
        ['Remnant POS', 'pos.remnant'],
        ['Sales List', 'sales.index'],
        ['Sales Returns', 'sales.returns'],
    ], 'tabler-cash'],
    ['Customers', 'customers.index', 'tabler-user-check'],
    ['Suppliers', 'suppliers.index', 'tabler-truck'],
    ['Distribution', [
        ['Vehicles', 'distribution.vehicles'],
        ['Drivers', 'distribution.drivers'],
        ['Delivery Routes', 'distribution.routes'],
    ], 'tabler-map-pin'],
    ['Store Stock', [
        ['Main Inventory', 'inventory.index'],
        ['Remnants', 'remnants.index'],
        ['Stock Transfers', 'transfers.index'],
    ], 'tabler-building-store'],
    ['Expenses', [
        ['Expenses List', 'expenses.index'],
        ['Expense Categories', 'expenses.categories'],
    ], 'tabler-currency-dollar'],
    ['Accounting', [
        ['Accounts', 'accounting.accounts'],
        ['Deposits', 'accounting.deposits'],
        ['Transfers', 'accounting.transfers'],
    ], 'tabler-calculator'],
    ['Cheque Management', [
        ['Received Cheques', 'cheques.received'],
        ['Issued Cheques', 'cheques.issued'],
    ], 'tabler-receipt'],
    ['Tax Management', [
        ['Tax Rates', 'taxes.rates'],
        ['Tax Reports', 'taxes.reports'],
    ], 'tabler-receipt-tax'],
    ['Reports', [
        ['Profit/Loss', 'reports.profit'],
        ['Sales Report', 'reports.sales'],
        ['Purchase Report', 'reports.purchases'],
        ['Stock Report', 'reports.stock'],
    ], 'tabler-chart-bar'],
    ['Settings', [
        ['General Settings', 'settings.show', ['section' => 'general']],
        ['Business Profile', 'settings.show', ['section' => 'business-profile']],
        ['Branch / Store Settings', 'settings.show', ['section' => 'branches']],
        ['Invoice Settings', 'settings.show', ['section' => 'invoice']],
        ['POS Settings', 'settings.show', ['section' => 'pos']],
        ['Product Settings', 'settings.show', ['section' => 'products']],
        ['Stock Settings', 'settings.show', ['section' => 'stock']],
        ['Barcode Settings', 'settings.show', ['section' => 'barcode']],
        ['Purchase Settings', 'settings.show', ['section' => 'purchase']],
        ['Sales Settings', 'settings.show', ['section' => 'sales']],
        ['Customer Settings', 'settings.show', ['section' => 'customers']],
        ['Supplier Settings', 'settings.show', ['section' => 'suppliers']],
        ['Account Settings', 'settings.show', ['section' => 'accounts']],
        ['Expense Settings', 'settings.show', ['section' => 'expenses']],
        ['Tax & Discount Settings', 'settings.show', ['section' => 'taxes']],
        ['Payment Method Settings', 'settings.show', ['section' => 'payments']],
        ['SMS & WhatsApp Settings', 'settings.show', ['section' => 'sms']],
        ['Report Settings', 'settings.show', ['section' => 'reports']],
        ['Backup Settings', 'settings.show', ['section' => 'backups']],
        ['System Security', 'settings.show', ['section' => 'security']],
        ['Audit Log Settings', 'settings.show', ['section' => 'audit']],
    ], 'tabler-settings'],
]; 
@endphp
<nav class="flex-1 space-y-1 overflow-y-auto p-4">
    @foreach($nav as [$label, $route, $icon])
        @if(is_array($route))
            @php 
                $isActive = collect($route)->contains(fn($r) => request()->routeIs($r[1]) && (empty($r[2]) || request()->segment(2) === ($r[2]['section'] ?? null))); 
            @endphp
            <div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm {{ $isActive ? 'bg-white/12 text-white' : 'text-slate-300 hover:bg-white/8 hover:text-white' }}">
                    <span class="grid h-5 w-5 place-items-center rounded-md border border-current/30 text-[10px]">{{ substr($label,0,1) }}</span>
                    {{ $label }}
                    <svg class="ml-auto h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div x-show="open" x-collapse class="pl-8 pr-3 py-1 space-y-1">
                    @foreach($route as $sub)
                        @php
                            $subLabel = $sub[0];
                            $subRoute = $sub[1];
                            $subParams = $sub[2] ?? ($subRoute === 'inventory.index' ? ['type' => 'main'] : []);
                            $isSubActive = request()->routeIs($subRoute) && (empty($subParams['section']) || request()->segment(2) === $subParams['section']);
                        @endphp
                        <a href="{{ route($subRoute, $subParams) }}" class="block rounded-lg px-3 py-2 text-sm {{ $isSubActive ? 'text-blue-400 font-medium bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                            {{ $subLabel }}
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm {{ request()->routeIs($route) ? 'bg-white/12 text-white' : 'text-slate-300 hover:bg-white/8 hover:text-white' }}">
                <span class="grid h-5 w-5 place-items-center rounded-md border border-current/30 text-[10px]">{{ substr($label,0,1) }}</span>
                {{ $label }}
            </a>
        @endif
    @endforeach
</nav>
<div class="border-t border-white/10 p-4"><div class="mb-3 flex items-center gap-3"><div class="grid h-9 w-9 place-items-center rounded-full bg-white/10 text-sm font-semibold">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div><div class="min-w-0"><div class="truncate text-sm font-medium">{{ auth()->user()->name }}</div><div class="text-xs capitalize text-slate-400">{{ str_replace('_',' ',auth()->user()->role) }}</div></div></div><form method="post" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg bg-white/5 py-2 text-xs text-slate-300 hover:bg-white/10">Sign out</button></form></div></aside>
<main class="min-w-0 flex-1"><header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200/70 bg-ivory/90 px-4 backdrop-blur lg:px-8"><button class="rounded-lg p-2 lg:hidden" @click="menu=true">☰</button><div><div class="text-xs text-slate-400">{{ now()->format('l, d F Y') }}</div><div class="text-sm font-semibold">Main Store</div></div><div class="flex gap-2"><a href="{{ route('pos.remnant') }}" class="btn bg-amber-100 text-amber-800">Remnant POS</a><a href="{{ route('pos.main') }}" class="btn-teal">New Sale</a></div></header><div class="p-4 lg:p-8">@if(session('success'))<div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif @if($errors->any())<div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><strong>Please fix the following:</strong><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif @yield('content')</div></main></div>@stack('scripts')</body></html>
