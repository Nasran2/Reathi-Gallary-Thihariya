@extends('layouts.app')
@section('title', 'Manage Account')

@section('content')
<div class="mb-6 flex flex-col justify-between sm:flex-row sm:items-end">
    <div>
        <h1 class="font-serif text-3xl text-ink">Manage Account</h1>
        <p class="mt-1 text-sm text-slate-500">Update your account details and password.</p>
    </div>
</div>

<form action="{{ route('profile.update') }}" method="POST" class="card max-w-2xl p-6">
    @csrf
    @method('PUT')

    <div class="grid gap-6 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Full Name</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="input" required>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Username</label>
            <input type="text" name="username" value="{{ old('username', $user->username) }}" class="input" required>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Email Address</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required>
        </div>

        <div class="sm:col-span-2 mt-4">
            <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wide border-b border-slate-100 pb-2 mb-4">Change Password</h3>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">New Password <span class="text-xs text-slate-400 font-normal">(Leave blank to keep current)</span></label>
            <input type="password" name="password" class="input">
        </div>
        
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Confirm New Password</label>
            <input type="password" name="password_confirmation" class="input">
        </div>
    </div>

    <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
        <button type="submit" class="btn-teal">Save Changes</button>
    </div>
</form>
@endsection
