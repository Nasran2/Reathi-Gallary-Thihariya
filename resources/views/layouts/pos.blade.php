<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','POS') · {{ \App\Models\BusinessSetting::read('legal_name') ?: \App\Models\BusinessSetting::read('business_name','Reathi Gallery') }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50">
    <main class="flex min-h-screen min-w-0 flex-1 flex-col">
        <header class="pos-app-header sticky top-0 z-20 flex min-h-14 items-center justify-between border-b border-slate-800 bg-ink px-3 py-2 text-white sm:px-4 lg:px-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 hover:text-slate-300">
                    <div class="grid h-8 w-8 place-items-center rounded bg-teal font-serif text-lg">R</div>
                    <div class="hidden sm:block font-semibold">Dashboard</div>
                </a>
                <div class="h-6 w-px bg-white/20"></div>
                <div>
                    <div class="text-xs text-slate-400">{{ now()->format('l, d F Y') }}</div>
                    <div class="text-sm font-semibold">Main Store POS</div>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="{{ route('pos.remnant') }}" class="btn bg-amber-100/10 text-amber-300 hover:bg-amber-100/20 text-xs py-1.5">Remnant POS</a>
                <a href="{{ route('pos.main') }}" class="btn-teal text-xs py-1.5">New Sale</a>
                <div class="h-6 w-px bg-white/20 ml-2"></div>
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <div class="text-sm font-medium">{{ auth()->user()->name }}</div>
                        <div class="text-[10px] capitalize text-slate-400">{{ str_replace('_',' ',auth()->user()->role) }}</div>
                    </div>
                    <div class="grid h-8 w-8 place-items-center rounded-full bg-white/10 text-xs font-semibold">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                </div>
            </div>
        </header>
        
        <div class="flex-1 p-4 lg:p-6">
            <!-- Toast Notifications -->
            <div class="fixed top-5 right-4 sm:right-5 z-50 flex flex-col gap-3 w-full sm:w-80 max-w-[calc(100vw-2rem)] sm:max-w-full pointer-events-none">
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(function(){ show = false }, 7000)" x-transition.opacity.duration.500ms class="pointer-events-auto flex items-start gap-3 p-4 rounded-xl shadow-lg border bg-emerald-50 border-emerald-200 text-emerald-800">
                        <div class="flex-1 text-sm">{{ session('success') }}</div>
                        <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                @endif 
                
                @if($errors->any())
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(function(){ show = false }, 7000)" x-transition.opacity.duration.500ms class="pointer-events-auto flex items-start gap-3 p-4 rounded-xl shadow-lg border bg-red-50 border-red-200 text-red-800">
                        <div class="flex-1 text-sm">
                            <strong>Please fix the following:</strong>
                            <ul class="mt-1 list-disc pl-5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button @click="show = false" class="text-red-500 hover:text-red-700 mt-0.5 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                @endif 
            </div> 
            
            @yield('content')
        </div>
        <footer class="border-t border-slate-200 bg-white px-4 py-2 text-center text-[10px] text-slate-400">
            Software powered by <a class="font-semibold text-teal hover:underline" href="https://twinsofte.com" target="_blank" rel="noopener">Twinsofte.com</a>
        </footer>
    </main>
    
    @stack('scripts')
</body>
</html>
