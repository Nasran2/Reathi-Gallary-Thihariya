@extends('layouts.app')
@section('title', 'Users')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="font-serif text-3xl text-ink">User Management</h1>
        <p class="mt-1 text-slate-500">Manage system users and access.</p>
    </div>
    <a href="{{ route('users.create') }}" class="btn-teal">
        <i class="ti ti-plus mr-2"></i>New User
    </a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-slate-50/50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-6 py-4 font-medium">Name</th>
                    <th class="px-6 py-4 font-medium">Username</th>
                    <th class="px-6 py-4 font-medium">Email</th>
                    <th class="px-6 py-4 font-medium">Role</th>
                    <th class="px-6 py-4 font-medium">Status</th>
                    <th class="px-6 py-4 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                    <tr class="transition-colors hover:bg-slate-50/50">
                        <td class="px-6 py-4 font-medium text-ink">{{ $user->name }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $user->username ?? '-' }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            @foreach($user->roles as $role)
                                <span class="badge bg-teal-50 text-teal-700 capitalize">{{ str_replace('_', ' ', $role->name) }}</span>
                            @endforeach
                        </td>
                        <td class="px-6 py-4">
                            <span class="badge {{ $user->active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $user->active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('users.edit', $user) }}" class="btn-soft !px-3 !py-1.5 text-xs">Edit</a>
                                @if(auth()->id() !== $user->id)
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
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
                        <td colspan="6" class="px-6 py-8 text-center text-slate-400">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div class="border-t border-slate-100 p-4">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
