<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','POS') · {{ \App\Models\BusinessSetting::read('business_name','Reathi Gallery') }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50">
    <main class="min-w-0 flex-1">
        <header class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-slate-800 bg-ink px-4 lg:px-8 text-white">
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
        
        <div class="p-4 lg:p-6 min-h-[calc(100vh-3.5rem)]">
            @if(session('success'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif 
            
            @if($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <strong>Please fix the following:</strong>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif 
            
            @yield('content')
        </div>
    </main>
    
    @stack('scripts')
</body>
</html>
