<!DOCTYPE html><html lang="en" class="h-full"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title','Dashboard') · {{ \App\Models\BusinessSetting::read('legal_name') ?: \App\Models\BusinessSetting::read('business_name','Reathi Gallery') }}</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css"><link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet"><script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script><style>.ts-control { border-radius: 0.5rem; border: 1px solid #e2e8f0; padding: 0.5rem 0.75rem; box-shadow: none; font-size: 0.875rem; line-height: 1.25rem; min-height: 2.5rem; background-color: #fff; } .ts-control.focus { border-color: #0d9488; box-shadow: 0 0 0 1px #0d9488; } .ts-dropdown { border-radius: 0.5rem; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); margin-top: 4px; font-size: 0.875rem; z-index: 50; } .ts-dropdown .option { padding: 0.5rem 0.75rem; cursor: pointer; } .ts-dropdown .active { background-color: #f0fdfa; color: #0f766e; font-weight: 500; } .ts-wrapper.single .ts-control:after { border-color: #94a3b8 transparent transparent transparent; border-width: 5px 4px 0 4px; right: 12px; } .ts-wrapper.single.focus .ts-control:after { border-color: transparent transparent #94a3b8 transparent; border-width: 0 4px 5px 4px; }</style>@vite(['resources/css/app.css','resources/js/app.js'])</head><body class="min-h-full bg-ink" x-data="appLayout"><div class="min-h-screen lg:flex">
<div x-show="menu" x-cloak class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" @click="closeMenu()"></div><aside @mouseenter="startSidebarHover()" @mouseleave="endSidebarHover()" :class="[menu?'translate-x-0':'-translate-x-full', isCollapsed ? 'w-20' : 'w-72']" class="fixed inset-y-0 left-0 z-40 flex flex-col bg-ink text-white transition-all duration-300 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"><div class="flex h-20 items-center border-b border-white/10 transition-all duration-300" :class="isCollapsed ? 'justify-center px-0' : 'justify-between px-4'"><div x-show="!isCollapsed" x-transition.opacity.duration.300ms class="flex items-center gap-3 overflow-hidden"><div class="grid h-10 min-w-[40px] w-10 place-items-center rounded-xl bg-teal font-serif text-xl shrink-0">R</div><div class="whitespace-nowrap"><div class="font-semibold">{{ \App\Models\BusinessSetting::read('legal_name') ?: \App\Models\BusinessSetting::read('business_name','Reathi Gallery') }}</div><div class="text-xs text-slate-400">Textile commerce suite</div></div></div><button @click="toggleSidebar()" class="hidden lg:block shrink-0 text-slate-400 hover:text-white p-2 rounded-lg hover:bg-white/10 transition" :class="isCollapsed ? '' : '-mr-2'"><i class="ti ti-menu-2 text-xl"></i></button></div>
@php 
$nav = [
    ['Dashboard', 'dashboard', 'tabler-dashboard', 'dashboard.view'],
    ['User Management', [
        ['Users', 'users.index', [], 'users.view'],
        ['Roles', 'roles.index', [], 'roles.view'],
    ], 'tabler-users'],
    ['Products', [
        ['Products List', 'products.index', [], 'products.view'],
        ['Categories', 'categories.index', [], 'categories.manage'],
        ['Units', 'units.index', [], 'units.manage'],
        ['Multiple Unit', 'unit-presets.index', [], 'unit_presets.manage'],
        ['Brands', 'brands.index', [], 'brands.manage'],
    ], 'tabler-box'],
    ['Purchase', [
        ['Purchase List', 'purchases.index', [], 'purchases.view'],
        ['Add Purchase', 'purchases.create', [], 'purchases.create'],
        ['Purchase Returns', 'purchases.returns', [], 'purchases.return'],
    ], 'tabler-shopping-cart'],
    ['Sales', [
        ['Main POS', 'pos.main', [], 'pos.main.access'],
        ['Remnant POS', 'pos.remnant', [], 'pos.remnant.access'],
        ['Sales List', 'sales.index', [], 'sales.view'],
        ['Sales Returns', 'sales.returns', [], 'sales.return'],
    ], 'tabler-cash'],
    ['Customers', 'customers.index', 'tabler-user-check', 'customers.view'],
    ['Suppliers', 'suppliers.index', 'tabler-truck', 'suppliers.view'],

    ['Store Stock', [
        ['Main Inventory', 'inventory.index', [], 'inventory.view'],
        ['Remnants', 'remnants.index', [], 'inventory.view'],
        ['Stock Transfers', 'transfers.index', [], 'inventory.transfer'],
    ], 'tabler-building-store'],
    ['Expenses', [
        ['Expenses List', 'expenses.index', [], 'expenses.view'],
        ['Expense Categories', 'expenses.categories', [], 'expense_categories.manage'],
    ], 'tabler-currency-dollar'],

    ['Cheque Management', [
        ['Dashboard', 'cheques.dashboard', [], 'cheques.view'],
        ['Received Cheques', 'cheques.received', [], 'cheques.view'],
        ['Issued Cheques', 'cheques.issued', [], 'cheques.view'],
        ['Endorsed / Transferred', 'cheques.endorsed', [], 'cheques.view'],
        ['Returned Cheques', 'cheques.returned', [], 'cheques.view'],
        ['Cheque History', 'cheques.history', [], 'cheques.view'],
    ], 'tabler-receipt'],
    ['Reports', [
        ['Sales Report', 'reports.sales', [], 'reports.sales'],
        ['Profit/Loss', 'reports.profit', [], 'reports.profit_loss'],
        ['Stock Report', 'reports.stock', [], 'reports.stock'],
        ['Low Stock Report', 'reports.low-stock', [], 'reports.low_stock'],
        ['Purchase Report', 'reports.purchases', [], 'reports.purchase'],
        ['Expense Report', 'reports.expenses', [], 'reports.expenses'],
        ['Due Bills Report', 'reports.due-bills', [], 'reports.customer_due'],
        ['Customer Due Report', 'reports.customer-due', [], 'reports.customer_due'],
        ['Supplier Due Report', 'reports.supplier-due', [], 'reports.supplier_due'],
        ['Cheque Report', 'reports.cheques', [], 'reports.cheques'],
        ['Daily Closing Report', 'reports.daily-closing', [], 'reports.daily_closing'],
        ['Dead Stock', 'reports.dead', [], 'reports.dead_stock'],
    ], 'tabler-chart-bar'],
    ['Settings', [
        ['General Settings', 'settings.show', ['section' => 'general'], 'settings.general'],
        ['Business Profile', 'settings.show', ['section' => 'business-profile'], 'settings.business'],
        // ['Branch / Store Settings', 'settings.show', ['section' => 'branches']],
        ['Invoice Settings', 'settings.show', ['section' => 'invoice'], 'settings.invoice'],
        ['POS Settings', 'settings.show', ['section' => 'pos'], 'settings.pos'],
        ['Product Settings', 'settings.show', ['section' => 'products'], 'settings.product'],
        ['Stock Settings', 'settings.show', ['section' => 'stock'], 'settings.stock'],
        ['Barcode Settings', 'settings.show', ['section' => 'barcode'], 'settings.product'],
        ['Purchase Settings', 'settings.show', ['section' => 'purchase'], 'settings.purchase'],
        ['Sales Settings', 'settings.show', ['section' => 'sales'], 'settings.sales'],
        ['Customer Settings', 'settings.show', ['section' => 'customers'], 'settings.customer'],
        ['Supplier Settings', 'settings.show', ['section' => 'suppliers'], 'settings.supplier'],
        ['Account Settings', 'settings.show', ['section' => 'accounts'], 'settings.account'],
        ['Expense Settings', 'settings.show', ['section' => 'expenses'], 'settings.account'],
        // ['Tax & Discount Settings', 'settings.show', ['section' => 'taxes']],
        ['Payment Method Settings', 'settings.show', ['section' => 'payments'], 'settings.payment_methods'],
        ['SMS & WhatsApp Settings', 'settings.show', ['section' => 'sms'], 'settings.sms'],
        ['Report Settings', 'settings.show', ['section' => 'reports'], 'settings.report'],
        ['Backup Settings', 'settings.show', ['section' => 'backups'], 'settings.backup'],
        ['System Security', 'settings.show', ['section' => 'security'], 'settings.security'],
        ['Audit Log Settings', 'settings.show', ['section' => 'audit'], 'settings.audit'],
    ], 'tabler-settings'],
]; 
@endphp
<nav class="flex-1 space-y-1 overflow-y-auto p-4 custom-scrollbar">
    @foreach($nav as $navItem)
        @php [$label, $route, $icon] = $navItem; $permission = $navItem[3] ?? null; @endphp
        @if(is_array($route))
            @php 
                $visibleRoutes = collect($route)->filter(fn($r) => auth()->user()->can($r[3]));
                $isActive = $visibleRoutes->contains(fn($r) => request()->routeIs($r[1]) && (empty($r[2]) || request()->segment(2) === ($r[2]['section'] ?? null))); 
            @endphp
            @continue($visibleRoutes->isEmpty())
            <div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
                <button @click="toggleNavGroup()" class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors {{ $isActive ? 'bg-white/12 text-white' : 'text-slate-300 hover:bg-white/8 hover:text-white' }}" :class="isCollapsed ? 'justify-center' : ''" :title="isCollapsed ? '{{ $label }}' : ''">
                    <i class="ti ti-{{ str_replace('tabler-', '', $icon) }} text-[20px] shrink-0"></i>
                    <span x-show="!isCollapsed" class="whitespace-nowrap flex-1 text-left font-medium">{{ $label }}</span>
                    <svg x-show="!isCollapsed" class="ml-auto h-4 w-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div x-show="open && !isCollapsed" x-collapse class="pl-11 pr-3 py-1 space-y-1">
                    @foreach($visibleRoutes as $sub)
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
            @continue(!auth()->user()->can($permission))
            <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors {{ request()->routeIs($route) ? 'bg-white/12 text-white font-medium' : 'text-slate-300 hover:bg-white/8 hover:text-white font-medium' }}" :class="isCollapsed ? 'justify-center' : ''" :title="isCollapsed ? '{{ $label }}' : ''">
                <i class="ti ti-{{ str_replace('tabler-', '', $icon) }} text-[20px] shrink-0"></i>
                <span x-show="!isCollapsed" class="whitespace-nowrap">{{ $label }}</span>
            </a>
        @endif
    @endforeach
</nav>
<div class="border-t border-white/10 p-4"><div class="mb-3 flex items-center gap-3" :class="isCollapsed ? 'justify-center' : ''"><div class="grid h-9 min-w-[36px] w-9 place-items-center rounded-full bg-white/10 text-sm font-semibold shrink-0">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div><div class="min-w-0" x-show="!isCollapsed"><div class="truncate text-sm font-medium">{{ auth()->user()->name }}</div><div class="text-xs capitalize text-slate-400">{{ str_replace('_',' ',auth()->user()->role) }}</div></div></div><a href="{{ route('profile') }}" class="w-full mb-2 flex items-center justify-center gap-2 rounded-lg bg-white/5 py-2 text-xs text-slate-300 hover:bg-white/10 transition-colors" :title="isCollapsed ? 'Manage Account' : ''"><i class="ti ti-user-edit text-lg" x-show="isCollapsed"></i><span x-show="!isCollapsed">Manage Account</span></a><form method="post" action="{{ route('logout') }}">@csrf<button class="w-full flex items-center justify-center gap-2 rounded-lg bg-white/5 py-2 text-xs text-slate-300 hover:bg-white/10 transition-colors" :title="isCollapsed ? 'Sign out' : ''"><i class="ti ti-logout text-lg" x-show="isCollapsed"></i><span x-show="!isCollapsed">Sign out</span></button></form></div></aside>
<main class="flex min-h-screen min-w-0 flex-1 flex-col bg-ivory"><header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200/70 bg-ivory/90 px-4 backdrop-blur lg:px-8"><button class="rounded-lg p-2 lg:hidden" @click="openMenu()">☰</button><div><div class="text-xs text-slate-400">{{ now()->format('l, d F Y') }}</div><div class="text-sm font-semibold">Main Store</div></div><div class="flex gap-2">@can('pos.remnant.access')<a href="{{ route('pos.remnant') }}" class="btn bg-amber-100 text-amber-800">Remnant POS</a>@endcan @can('pos.main.access')<a href="{{ route('pos.main') }}" class="btn-teal">New Sale</a>@endcan</div></header><div class="flex-1 p-4 lg:p-8"><div class="fixed top-5 right-4 sm:right-5 z-50 flex flex-col gap-3 w-full sm:w-80 max-w-[calc(100vw-2rem)] sm:max-w-full pointer-events-none">@if(session('success'))<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)" x-transition.opacity.duration.500ms class="pointer-events-auto flex items-start gap-3 p-4 rounded-xl shadow-lg border bg-emerald-50 border-emerald-200 text-emerald-800"><div class="flex-1 text-sm">{{ session('success') }}</div><button @click="show = false" class="text-emerald-500 hover:text-emerald-700 shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div>@endif @if(session('error'))<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)" x-transition.opacity.duration.500ms class="pointer-events-auto flex items-start gap-3 p-4 rounded-xl shadow-lg border bg-red-50 border-red-200 text-red-800"><div class="flex-1 text-sm">{{ session('error') }}</div><button @click="show = false" class="text-red-500 hover:text-red-700 mt-0.5 shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div>@endif @if($errors->any())<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)" x-transition.opacity.duration.500ms class="pointer-events-auto flex items-start gap-3 p-4 rounded-xl shadow-lg border bg-red-50 border-red-200 text-red-800"><div class="flex-1 text-sm"><strong>Please fix the following:</strong><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div><button @click="show = false" class="text-red-500 hover:text-red-700 mt-0.5 shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div>@endif</div>@yield('content')</div><footer class="border-t border-slate-200/70 px-4 py-3 text-center text-xs text-slate-400">Software powered by <a class="font-semibold text-teal hover:underline" href="https://twinsofte.com" target="_blank" rel="noopener">Twinsofte.com</a></footer></main></div>@stack('scripts')</body></html>
