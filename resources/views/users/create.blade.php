@extends('layouts.app')

@section('title', 'Create User')

@section('content')

<div class="max-w-lg">

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded text-sm">
    <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="/users" class="bg-white rounded-lg border border-gray-200 p-5">
    @csrf

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            <label class="text-xs text-gray-500 block mb-1">Full Name *</label>
            <input type="text" name="full_name" value="{{ old('full_name') }}" required
                class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
        </div>
        <div>
            <label class="text-xs text-gray-500 block mb-1">Username *</label>
            <input type="text" name="username" value="{{ old('username') }}" required
                class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
        </div>
        <div>
            <label class="text-xs text-gray-500 block mb-1">Email *</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
        </div>
        <div>
            <label class="text-xs text-gray-500 block mb-1">Password *</label>
            <input type="password" name="password" required minlength="8"
                class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
        </div>
        <div>
            <label class="text-xs text-gray-500 block mb-1">Confirm Password *</label>
            <input type="password" name="password_confirmation" required
                class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
        </div>
        <div class="flex items-center gap-2 mt-4">
            <input type="checkbox" name="is_super_admin" id="is_super_admin" class="rounded">
            <label for="is_super_admin" class="text-sm text-gray-600">Super Admin</label>
        </div>
    </div>

    <div class="border-t border-gray-100 pt-4 mb-4">
        <p class="text-xs text-gray-500 mb-2">Assign to Company (optional)</p>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Company</label>
                <select name="company_id" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">- None -</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Role</label>
                <select name="role" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="ADMIN">Admin</option>
                    <option value="ACCOUNTANT">Accountant</option>
                    <option value="VIEWER">Viewer</option>
                </select>
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
            Create User
        </button>
        <a href="/users" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
    </div>
</form>

</div>

@endsection