@extends('layouts.app')
@section('title', 'Roles & Permissions')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="font-serif text-3xl text-ink">Roles & Permissions</h1>
        <p class="mt-1 text-slate-500">Manage user roles and their access levels.</p>
    </div>
    <a href="{{ route('roles.create') }}" class="btn-teal">
        <i class="ti ti-plus mr-2"></i>New Role
    </a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-slate-50/50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-6 py-4 font-medium">Role Name</th>
                    <th class="px-6 py-4 font-medium">Permissions</th>
                    <th class="px-6 py-4 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($roles as $role)
                    <tr class="transition-colors hover:bg-slate-50/50">
                        <td class="px-6 py-4 font-medium text-ink capitalize">{{ str_replace('_', ' ', $role->name) }}</td>
                        <td class="px-6 py-4">
                            @if($role->name === 'super_admin')
                                <span class="badge bg-purple-50 text-purple-700">All Permissions</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @forelse($role->permissions as $permission)
                                        <span class="badge bg-slate-100 text-slate-700">{{ $permission->name }}</span>
                                    @empty
                                        <span class="text-xs text-slate-400">No permissions</span>
                                    @endforelse
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('roles.edit', $role) }}" class="btn-soft !px-3 !py-1.5 text-xs">Edit</a>
                                @if($role->name !== 'super_admin')
                                    <form action="{{ route('roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this role?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn !bg-red-50 !text-red-600 !px-3 !py-1.5 text-xs hover:!bg-red-100">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-slate-400">No roles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
