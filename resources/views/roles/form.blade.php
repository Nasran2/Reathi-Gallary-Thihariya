@extends('layouts.app')
@section('title', isset($role) ? 'Edit Role' : 'New Role')

@section('content')
<div class="mb-6">
    <a href="{{ route('roles.index') }}" class="text-sm font-medium text-slate-500 hover:text-ink">&larr; Back to roles</a>
    <h1 class="mt-2 font-serif text-3xl text-ink">{{ isset($role) ? 'Edit Role' : 'New Role' }}</h1>
</div>

<form action="{{ isset($role) ? route('roles.update', $role) : route('roles.store') }}" method="POST" class="card max-w-3xl p-6">
    @csrf
    @if(isset($role))
        @method('PUT')
    @endif

    <div class="mb-6">
        <label class="mb-1 block text-sm font-medium text-slate-700">Role Name</label>
        <input type="text" name="name" value="{{ old('name', $role->name ?? '') }}" class="input max-w-md" required {{ isset($role) && $role->name === 'super_admin' ? 'readonly' : '' }}>
        @if(isset($role) && $role->name === 'super_admin')
            <p class="mt-1 text-xs text-slate-400">The super_admin role name cannot be changed.</p>
        @endif
    </div>

    @if(!isset($role) || $role->name !== 'super_admin')
        <div class="mb-6">
            <h3 class="mb-3 font-semibold text-ink">Permissions</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($permissions as $permission)
                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="h-4 w-4 rounded border-slate-300 text-teal focus:ring-teal"
                            @checked(is_array(old('permissions')) ? in_array($permission->name, old('permissions')) : (isset($role) && $role->hasPermissionTo($permission->name)))>
                        <span class="text-sm font-medium text-slate-700">{{ str_replace('-', ' ', Str::title($permission->name)) }}</span>
                    </label>
                @endforeach
                @if($permissions->isEmpty())
                    <p class="col-span-full text-sm text-slate-400">No specific permissions have been defined in the system yet. Roles will only have access to their hardcoded basic abilities.</p>
                @endif
            </div>
        </div>
    @else
        <div class="mb-6 rounded-lg bg-purple-50 p-4 text-sm text-purple-800">
            <strong>Super Admin Role:</strong> This role automatically has all permissions in the system.
        </div>
    @endif

    <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
        <a href="{{ route('roles.index') }}" class="btn-soft">Cancel</a>
        <button type="submit" class="btn-teal">{{ isset($role) ? 'Save Changes' : 'Create Role' }}</button>
    </div>
</form>
@endsection
