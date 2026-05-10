@extends('layouts.app')
@section('title', 'Edit User')
@section('content')

<div class="max-w-2xl">

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded text-sm">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <form method="POST" action="/users/{{ $user->id }}/update">
        @csrf

        <!-- Basic Info -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-4">User Details</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Full Name *</label>
                    <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" required
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Username *</label>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}" required
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="text-xs text-gray-500 block mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                </div>
            </div>
        </div>

        <!-- Password -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-1">Change Password</h3>
            <p class="text-xs text-gray-400 mb-4">Leave blank to keep current password.</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">New Password</label>
                    <input type="password" name="password"
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm"
                        placeholder="Min. 8 characters">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation"
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                </div>
            </div>
        </div>

        <!-- Permissions -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-4">Permissions</h3>
            <div class="flex gap-6">
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1"
                        {{ $user->is_active ? 'checked' : '' }}
                        class="accent-blue-600">
                    Active
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_super_admin" value="1"
                        {{ $user->is_super_admin ? 'checked' : '' }}
                        class="accent-purple-600">
                    Super Admin
                    <span class="text-xs text-gray-400">(access to all companies)</span>
                </label>
            </div>
        </div>

        <!-- Company Assignment -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 mb-6">
            <h3 class="font-semibold text-gray-700 text-sm mb-1">Company Access</h3>
            <p class="text-xs text-gray-400 mb-4">Super admins have access to all companies automatically. For non-admin users, select which companies they can access.</p>
            <div class="space-y-3">
                @foreach($companies as $company)
                <div class="flex items-center gap-4 p-3 border border-gray-100 rounded-lg">
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer min-w-48">
                        <input type="checkbox" name="companies[]" value="{{ $company->id }}"
                            {{ in_array($company->id, $assigned) ? 'checked' : '' }}
                            class="accent-blue-600">
                        {{ $company->name }}
                    </label>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-gray-400">Role:</label>
                        <select name="role_{{ $company->id }}"
                            class="border border-gray-200 rounded px-2 py-1 text-xs">
                            @php
                                $currentRole = DB::table('user_companies')
                                    ->where('user_id', $user->id)
                                    ->where('company_id', $company->id)
                                    ->value('role') ?? 'VIEWER';
                            @endphp
                           	<option value="VIEWER"      {{ $currentRole == 'VIEWER'      ? 'selected' : '' }}>Viewer</option>
						   	<option value="ADMIN"       {{ $currentRole == 'ADMIN'       ? 'selected' : '' }}>Admin</option>
							<option value="ACCOUNTANT"  {{ $currentRole == 'ACCOUNTANT'  ? 'selected' : '' }}>Accountant</option>
							<option value="AUDITOR"     {{ $currentRole == 'AUDITOR'     ? 'selected' : '' }}>Auditor</option>
							<option value="SALES"       {{ $currentRole == 'SALES'       ? 'selected' : '' }}>Sales</option>
							<option value="PURCHASE"    {{ $currentRole == 'PURCHASE'    ? 'selected' : '' }}>Purchase</option>
							<option value="PAYROLL"     {{ $currentRole == 'PAYROLL'     ? 'selected' : '' }}>Payroll</option>
                        </select>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="bg-blue-600 text-white text-sm px-5 py-2 rounded hover:bg-blue-700">
                Save Changes
            </button>
            <a href="/users" class="bg-gray-100 text-gray-600 text-sm px-5 py-2 rounded hover:bg-gray-200">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection