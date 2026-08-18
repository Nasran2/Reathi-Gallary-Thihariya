@extends('layouts.app')
@section('title', isset($user) ? 'Edit User' : 'New User')

@section('content')
<div class="mb-6">
    <a href="{{ route('users.index') }}" class="text-sm font-medium text-slate-500 hover:text-ink">&larr; Back to users</a>
    <h1 class="mt-2 font-serif text-3xl text-ink">{{ isset($user) ? 'Edit User' : 'New User' }}</h1>
</div>

<form action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}" method="POST" class="card max-w-2xl p-6">
    @csrf
    @if(isset($user))
        @method('PUT')
    @endif

    <div class="grid gap-6 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Full Name</label>
            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="input" required>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Username</label>
            <input type="text" name="username" value="{{ old('username', $user->username ?? '') }}" class="input">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Email Address</label>
            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="input" required>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Role</label>
            <select name="role" class="input" required>
                <option value="">Select a role...</option>
                @foreach($roles as $role)
                    <option value="{{ $role }}" @selected(old('role', isset($user) && $user->roles->first() ? $user->roles->first()->name : '') === $role)>
                        {{ str_replace('_', ' ', Str::title($role)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">
                Password {!! isset($user) ? '<span class="text-xs text-slate-400 font-normal">(Leave blank to keep current)</span>' : '' !!}
            </label>
            <input type="password" name="password" class="input" {{ isset($user) ? '' : 'required' }}>
        </div>

        <div class="sm:col-span-2 mt-2">
            <label class="flex items-center gap-3">
                <input type="checkbox" name="active" value="1" class="h-5 w-5 rounded border-slate-300 text-teal focus:ring-teal" @checked(old('active', $user->active ?? true))>
                <span class="text-sm font-medium text-slate-700">Account is Active</span>
            </label>
        </div>
    </div>

    <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
        <a href="{{ route('users.index') }}" class="btn-soft">Cancel</a>
        <button type="submit" class="btn-teal">{{ isset($user) ? 'Save Changes' : 'Create User' }}</button>
    </div>
</form>
@endsection
