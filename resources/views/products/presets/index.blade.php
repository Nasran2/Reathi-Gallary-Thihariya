@extends('layouts.app') 

@section('title', 'Multiple Units') 

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="font-serif text-3xl text-ink">Multiple Units</h1>
        <p class="text-sm text-slate-500">Global presets for unit conversions to speed up product creation.</p>
    </div>
    <a href="{{ route('unit-presets.create') }}" class="btn-teal">+ Add preset</a>
</div>

<div class="card overflow-x-auto">
    <table>
        <thead>
            <tr>
                <th>Preset Name</th>
                <th>Base Unit</th>
                <th>Conversions</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($presets as $p)
            <tr>
                <td class="font-bold text-slate-700">{{ $p->name }}</td>
                <td class="font-semibold text-teal">{{ $p->baseUnit->name }} ({{ $p->baseUnit->symbol }})</td>
                <td>
                    @foreach($p->conversions as $c)
                    <span class="mr-1 inline-block rounded-lg bg-slate-100 px-2 py-1 text-xs">
                        1 {{ $p->baseUnit->symbol }} = {{ $c->unit_quantity + 0 }} {{ $c->unit->symbol }}
                    </span>
                    @endforeach
                </td>
                <td class="text-right">
                    <a class="font-semibold text-teal" href="{{ route('unit-presets.edit', $p) }}">Edit</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="py-12 text-center text-slate-400">No unit presets yet. Create one to speed up product entry.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
