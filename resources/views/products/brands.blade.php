@extends('layouts.app') 
@section('title', 'Brands') 
@section('content')

<div class="mb-6">
    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">
        <a href="{{ route('dashboard') }}" class="hover:text-slate-600 transition">Dashboard</a> 
        <span>/</span>
        <a href="{{ route('products.index') }}" class="hover:text-slate-600 transition">Products</a> 
        <span>/</span>
        <span class="text-slate-600">Brands</span>
    </div>
    <h1 class="font-serif text-3xl text-ink">Brands</h1>
    <p class="text-sm text-slate-500 mt-1">View all brands associated with your products.</p>
</div>

<div class="max-w-4xl">
    <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="grid h-10 w-10 place-items-center rounded-lg bg-orange-50 text-orange-600">
                <i class="ti ti-star text-xl"></i>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-lg">Product Brands</h2>
                <p class="text-xs text-slate-500 mt-0.5">Brands are automatically extracted from your product catalogue.</p>
            </div>
        </div>
        
        <div class="p-6">
            @if($brands->isEmpty())
                <div class="text-center py-10 bg-slate-50 rounded-xl border border-slate-100 border-dashed">
                    <i class="ti ti-star-off text-4xl text-slate-300 mb-3 block"></i>
                    <h3 class="text-sm font-semibold text-slate-700">No brands found</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">You haven't assigned any brands to your products yet. When you add a brand to a product, it will automatically appear here.</p>
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($brands as $brand)
                    <div class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 bg-white shadow-sm hover:shadow-md transition">
                        <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 font-bold text-slate-500 text-lg uppercase">
                            {{ substr($brand, 0, 1) }}
                        </div>
                        <div class="truncate font-semibold text-slate-700">
                            {{ $brand }}
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>

@endsection
