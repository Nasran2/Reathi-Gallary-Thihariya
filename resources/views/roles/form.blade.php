@extends('layouts.app')
@section('title', isset($role) ? 'Edit Role' : 'New Role')

@section('content')
<div class="mb-6">
    <a href="{{ route('roles.index') }}" class="text-sm font-medium text-slate-500 hover:text-ink">&larr; Back to roles</a>
    <h1 class="mt-2 font-serif text-3xl text-ink">{{ isset($role) ? 'Edit Role' : 'New Role' }}</h1>
</div>

<form id="role-form" action="{{ isset($role) ? route('roles.update', $role) : route('roles.store') }}" method="POST" class="card max-w-5xl p-6">
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
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div><h3 class="font-semibold text-ink">Permissions</h3><p class="text-sm text-slate-500">Choose exactly what users assigned to this role may access.</p></div>
                <div class="flex gap-2"><button type="button" class="btn-soft permission-select-all">Select All</button><button type="button" class="btn-soft permission-clear-all">Clear All</button></div>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach($permissionGroups as $group => $permissions)
                    <section class="rounded-xl border border-slate-200 p-4" data-permission-group>
                        <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                            <h4 class="font-semibold text-ink">{{ $group }}</h4>
                            <button type="button" class="text-xs font-semibold text-teal hover:underline permission-select-group">Select module</button>
                        </div>
                        <div class="space-y-2">
                            @foreach($permissions as $permission => $description)
                                <label class="flex items-start gap-3 rounded-lg p-2 hover:bg-slate-50">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal focus:ring-teal permission-checkbox"
                                        @checked(is_array(old('permissions')) ? in_array($permission, old('permissions')) : (isset($role) && $role->hasPermissionTo($permission)))>
                                    <span><span class="block text-sm font-medium text-slate-700">{{ $description }}</span><span class="block text-[11px] text-slate-400">{{ $permission }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach
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
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('role-form');
    if (!form) return;
    const boxes = () => form.querySelectorAll('.permission-checkbox');
    form.querySelector('.permission-select-all')?.addEventListener('click', () => boxes().forEach(box => box.checked = true));
    form.querySelector('.permission-clear-all')?.addEventListener('click', () => boxes().forEach(box => box.checked = false));
    form.querySelectorAll('.permission-select-group').forEach(button => button.addEventListener('click', () => {
        button.closest('[data-permission-group]').querySelectorAll('.permission-checkbox').forEach(box => box.checked = true);
    }));
});
</script>
@endpush
@endsection
