<!DOCTYPE html><html lang="en" class="h-full"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title','Dashboard') · {{ \App\Models\BusinessSetting::read('business_name','Reathi Gallery') }}</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css"><link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet"><script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script><style>.ts-control { border-radius: 0.5rem; border: 1px solid #e2e8f0; padding: 0.5rem 0.75rem; box-shadow: none; font-size: 0.875rem; line-height: 1.25rem; min-height: 2.5rem; background-color: #fff; } .ts-control.focus { border-color: #0d9488; box-shadow: 0 0 0 1px #0d9488; } .ts-dropdown { border-radius: 0.5rem; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); margin-top: 4px; font-size: 0.875rem; z-index: 50; } .ts-dropdown .option { padding: 0.5rem 0.75rem; cursor: pointer; } .ts-dropdown .active { background-color: #f0fdfa; color: #0f766e; font-weight: 500; } .ts-wrapper.single .ts-control:after { border-color: #94a3b8 transparent transparent transparent; border-width: 5px 4px 0 4px; right: 12px; } .ts-wrapper.single.focus .ts-control:after { border-color: transparent transparent #94a3b8 transparent; border-width: 0 4px 5px 4px; }</style>@vite(['resources/css/app.css','resources/js/app.js'])</head><body class="min-h-full" x-data="{menu:false, collapsed: localStorage.getItem('sidebarCollapsed') === 'true', hoverExpand: false, get isCollapsed() { return this.collapsed && !this.hoverExpand; } }" x-init="$watch('collapsed', val => localStorage.setItem('sidebarCollapsed', val))"><div class="min-h-screen lg:flex">
<div x-show="menu" x-cloak class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" @click="menu=false"></div><aside @mouseenter="if(collapsed) hoverExpand = true" @mouseleave="hoverExpand = false" :class="[menu?'translate-x-0':'-translate-x-full', isCollapsed ? 'w-20' : 'w-72']" class="fixed inset-y-0 left-0 z-40 flex flex-col bg-ink text-white transition-all duration-300 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"><div class="flex h-20 items-center border-b border-white/10 transition-all duration-300" :class="isCollapsed ? 'justify-center px-0' : 'justify-between px-4'"><div x-show="!isCollapsed" x-transition.opacity.duration.300ms class="flex items-center gap-3 overflow-hidden"><div class="grid h-10 min-w-[40px] w-10 place-items-center rounded-xl bg-teal font-serif text-xl shrink-0">R</div><div class="whitespace-nowrap"><div class="font-semibold">{{ \App\Models\BusinessSetting::read('business_name','Reathi Gallery') }}</div><div class="text-xs text-slate-400">Textile commerce suite</div></div></div><button @click="collapsed = !collapsed; hoverExpand = false;" class="hidden lg:block shrink-0 text-slate-400 hover:text-white p-2 rounded-lg hover:bg-white/10 transition" :class="isCollapsed ? '' : '-mr-2'"><i class="ti ti-menu-2 text-xl"></i></button></div>
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
        ['Multiple units', 'unit-presets.index'],
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

    ['Store Stock', [
        ['Main Inventory', 'inventory.index'],
        ['Remnants', 'remnants.index'],
        ['Stock Transfers', 'transfers.index'],
    ], 'tabler-building-store'],
    ['Expenses', [
        ['Expenses List', 'expenses.index'],
        ['Expense Categories', 'expenses.categories'],
    ], 'tabler-currency-dollar'],

    ['Cheque Management', [
        ['Dashboard', 'cheques.dashboard'],
        ['Received Cheques', 'cheques.received'],
        ['Issued Cheques', 'cheques.issued'],
        ['Endorsed / Transferred', 'cheques.endorsed'],
        ['Returned Cheques', 'cheques.returned'],
        ['Cheque History', 'cheques.history'],
    ], 'tabler-receipt'],
    ['Reports', [
        ['Sales Report', 'reports.sales'],
        ['Profit/Loss', 'reports.profit'],
        ['Stock Report', 'reports.stock'],
        ['Purchase Report', 'reports.purchases'],
        ['Expense Report', 'reports.expenses'],
        ['Due Bills Report', 'reports.due-bills'],
        ['Customer Due Report', 'reports.customer-due'],
        ['Daily Closing Report', 'reports.daily-closing'],
        ['Dead Stock', 'reports.dead'],
    ], 'tabler-chart-bar'],
    ['Settings', [
        ['General Settings', 'settings.show', ['section' => 'general']],
        ['Business Profile', 'settings.show', ['section' => 'business-profile']],
        // ['Branch / Store Settings', 'settings.show', ['section' => 'branches']],
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
        // ['Tax & Discount Settings', 'settings.show', ['section' => 'taxes']],
        ['Payment Method Settings', 'settings.show', ['section' => 'payments']],
        ['SMS & WhatsApp Settings', 'settings.show', ['section' => 'sms']],
        ['Report Settings', 'settings.show', ['section' => 'reports']],
        ['Backup Settings', 'settings.show', ['section' => 'backups']],
        ['System Security', 'settings.show', ['section' => 'security']],
        ['Audit Log Settings', 'settings.show', ['section' => 'audit']],
    ], 'tabler-settings'],
]; 
@endphp
<nav class="flex-1 space-y-1 overflow-y-auto p-4 custom-scrollbar">
    @foreach($nav as [$label, $route, $icon])
        @if(is_array($route))
            @php 
                $isActive = collect($route)->contains(fn($r) => request()->routeIs($r[1]) && (empty($r[2]) || request()->segment(2) === ($r[2]['section'] ?? null))); 
            @endphp
            <div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
                <button @click="open = !open; if(collapsed) { collapsed = false; open = true; }" class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors {{ $isActive ? 'bg-white/12 text-white' : 'text-slate-300 hover:bg-white/8 hover:text-white' }}" :class="isCollapsed ? 'justify-center' : ''" :title="isCollapsed ? '{{ $label }}' : ''">
                    <i class="ti ti-{{ str_replace('tabler-', '', $icon) }} text-[20px] shrink-0"></i>
                    <span x-show="!isCollapsed" class="whitespace-nowrap flex-1 text-left font-medium">{{ $label }}</span>
                    <svg x-show="!isCollapsed" class="ml-auto h-4 w-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div x-show="open && !isCollapsed" x-collapse class="pl-11 pr-3 py-1 space-y-1">
                    @foreach($route as $sub)
                        @php
                            $subLabel = $sub[0];
                            $subRoute = $sub[1];
                            $subParams = $sub[2] ?? ($subRoute === 'inventory.index' ? ['type' => 'main'] : []);
                            $isSubActive = request()->routeIs($subRoute) && (empty($subParams['section']) || request()->segment(2) === $subParams['section']);
                        @endphp
                        <a href="{{ route($subRoute, $subParams) }}" class="block rounded-lg px-3 py-2 text-sm transition-colors {{ $isSubActive ? 'text-blue-400 font-medium bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                            {{ $subLabel }}
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors {{ request()->routeIs($route) ? 'bg-white/12 text-white font-medium' : 'text-slate-300 hover:bg-white/8 hover:text-white font-medium' }}" :class="isCollapsed ? 'justify-center' : ''" :title="isCollapsed ? '{{ $label }}' : ''">
                <i class="ti ti-{{ str_replace('tabler-', '', $icon) }} text-[20px] shrink-0"></i>
                <span x-show="!isCollapsed" class="whitespace-nowrap">{{ $label }}</span>
            </a>
        @endif
    @endforeach
</nav>
<div class="border-t border-white/10 p-4"><div class="mb-3 flex items-center gap-3" :class="isCollapsed ? 'justify-center' : ''"><div class="grid h-9 min-w-[36px] w-9 place-items-center rounded-full bg-white/10 text-sm font-semibold shrink-0">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div><div class="min-w-0" x-show="!isCollapsed"><div class="truncate text-sm font-medium">{{ auth()->user()->name }}</div><div class="text-xs capitalize text-slate-400">{{ str_replace('_',' ',auth()->user()->role) }}</div></div></div><form method="post" action="{{ route('logout') }}">@csrf<button class="w-full flex items-center justify-center gap-2 rounded-lg bg-white/5 py-2 text-xs text-slate-300 hover:bg-white/10 transition-colors" :title="isCollapsed ? 'Sign out' : ''"><i class="ti ti-logout text-lg" x-show="isCollapsed"></i><span x-show="!isCollapsed">Sign out</span></button></form></div></aside>
<main class="min-w-0 flex-1"><header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200/70 bg-ivory/90 px-4 backdrop-blur lg:px-8"><button class="rounded-lg p-2 lg:hidden" @click="menu=true">☰</button><div><div class="text-xs text-slate-400">{{ now()->format('l, d F Y') }}</div><div class="text-sm font-semibold">Main Store</div></div><div class="flex gap-2"><a href="{{ route('pos.remnant') }}" class="btn bg-amber-100 text-amber-800">Remnant POS</a><a href="{{ route('pos.main') }}" class="btn-teal">New Sale</a></div></header><div class="p-4 lg:p-8">@if(session('success'))<div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif @if($errors->any())<div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><strong>Please fix the following:</strong><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif @yield('content')</div></main></div>@stack('scripts')</body></html>
