@extends('layouts.app') 
@section('title', $config['title']) 
@section('content')

<div class="mb-6">
    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">
        <a href="{{ route('dashboard') }}" class="hover:text-slate-600 transition">Dashboard</a> 
        <span>/</span>
        <span>Settings</span>
        <span>/</span>
        <span class="text-slate-600">{{ $config['title'] }}</span>
    </div>
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-serif text-3xl text-ink">{{ $config['title'] }}</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $config['description'] }}</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition">Reset Defaults</button>
            <button type="submit" form="settings-form" class="px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 hover:shadow transition flex items-center gap-2">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                Save Changes
            </button>
        </div>
    </div>
</div>

<form id="settings-form" method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6 w-full">
    @csrf 
    @method('PUT')
    <input type="hidden" name="_section" value="{{ $section }}">

    @foreach($config['sections'] as $sec)
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="grid h-10 w-10 place-items-center rounded-lg bg-blue-50 text-blue-500">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-lg">{{ $sec['title'] }}</h2>
                @if(!empty($sec['description']))
                <p class="text-xs text-slate-500 mt-0.5">{{ $sec['description'] }}</p>
                @endif
            </div>
        </div>
        
        <div class="grid gap-6 {{ count($sec['fields']) > 1 ? 'md:grid-cols-2' : '' }}">
            @foreach($sec['fields'] as $field)
            @php $value = \App\Models\BusinessSetting::read($field['name'], $field['default'] ?? null); @endphp
            <div class="{{ !empty($field['full']) ? 'md:col-span-2' : '' }}">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5 flex items-center gap-1">{{ $field['label'] }} <span class="grid h-4 w-4 place-items-center rounded-full bg-blue-50 text-[10px] text-blue-500 font-bold">i</span></label>
                
                @if($field['type'] === 'select')
                    <select class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 py-2.5" name="{{ $field['name'] }}">
                        @foreach($field['options'] as $val => $label)
                        <option value="{{ $val }}" @selected($value == $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                
                @elseif($field['type'] === 'textarea')
                    <textarea class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 py-2.5" rows="4" name="{{ $field['name'] }}">{{ $value }}</textarea>
                
                @elseif($field['type'] === 'checkbox')
                    <label class="flex items-center gap-3 mt-3 normal-case cursor-pointer bg-slate-50 px-4 py-3 rounded-lg border border-slate-100">
                        <input type="checkbox" name="{{ $field['name'] }}" value="1" @checked($value) class="rounded h-5 w-5 text-blue-600 focus:ring-blue-500 border-slate-300">
                        <span class="text-sm font-semibold text-slate-700">{{ $field['label'] }}</span>
                    </label>
                
                @elseif($field['type'] === 'file')
                    <div class="flex items-center gap-4 rounded-lg border border-dashed border-slate-300 p-4 bg-slate-50">
                        @if($value)
                            <img src="{{ asset($value) }}" class="h-12 w-12 rounded-lg object-cover bg-white shadow-sm border border-slate-200">
                        @endif
                        <input type="file" name="{{ $field['name'] }}" class="text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                    </div>
                
                @else
                    <input type="{{ $field['type'] }}" 
                           class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 py-2.5 shadow-sm" 
                           name="{{ $field['name'] }}" 
                           value="{{ rtrim(str_replace('"', '', $value), '\\') }}" 
                           @if(!empty($field['min'])) min="{{ $field['min'] }}" @endif
                           @if(!empty($field['max'])) max="{{ $field['max'] }}" @endif
                           @if(!empty($field['step'])) step="{{ $field['step'] }}" @endif
                           @if(!empty($field['required'])) required @endif
                           placeholder="{{ $field['placeholder'] ?? '' }}">
                @endif
                
                @if(!empty($field['help']))
                    <p class="mt-1.5 text-xs text-slate-400">{{ $field['help'] }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</form>

@endsection
