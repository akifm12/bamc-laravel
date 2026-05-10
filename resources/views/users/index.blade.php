@extends('layouts.app')

@section('title', 'User Management')

@section('content')

@php
    $pending = $users->where('is_active', false)->where('is_super_admin', false);
@endphp

@if($pending->count() > 0)
<div class="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
    <div class="flex items-center gap-2 mb-3">
        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span class="text-sm font-semibold text-amber-800">{{ $pending->count() }} user(s) pending approval</span>
    </div>
    <div class="space-y-2">
        @foreach($pending as $pu)
        <div class="flex items-center justify-between bg-white border border-amber-100 rounded px-3 py-2">
            <div>
                <span class="text-sm font-medium text-gray-800">{{ $pu->full_name }}</span>
                <span class="text-xs text-gray-400 ml-2">{{ $pu->username }}</span>
                <span class="text-xs text-gray-400 ml-2">{{ $pu->email }}</span>
                <span class="text-xs text-gray-400 ml-2">Registered: {{ \Carbon\Carbon::parse($pu->created_at)->format('d M Y, H:i') }}</span>
            </div>
            <div class="flex gap-3">
                <form method="POST" action="/users/{{ $pu->id }}/activate">
                    @csrf
                    <button class="text-xs bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">Approve</button>
                </form>
                <form method="POST" action="/users/{{ $pu->id }}"
                    onsubmit="return confirm('Reject and delete {{ $pu->full_name }}?')">
                    @csrf
                    @method('DELETE')
                    <button class="text-xs bg-red-500 text-white px-3 py-1 rounded hover:bg-red-700">Reject</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="mb-4">
    <a href="/users/create" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">➕ Create User</a>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-200 bg-gray-50">
                <th class="px-4 py-2 text-left">User</th>
                <th class="px-4 py-2 text-left">Username</th>
                <th class="px-4 py-2 text-left">Companies</th>
                <th class="px-4 py-2 text-center">Admin</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2 text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $user->full_name }}</p>
                    <p class="text-xs text-gray-400">{{ $user->email }}</p>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $user->username }}</td>
                <td class="px-4 py-3">
                    @if(isset($userCompanies[$user->id]))
                        @foreach($userCompanies[$user->id] as $uc)
                            <span class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full mr-1">
                                {{ $uc->name }} ({{ $uc->role }})
                            </span>
                        @endforeach
                    @else
                        <span class="text-xs text-gray-400">No companies</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    @if($user->is_super_admin)
                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">Super Admin</span>
                    @else
                        <form method="POST" action="/users/{{ $user->id }}/make-admin" class="inline">
                            @csrf
                            <button class="text-xs text-gray-400 hover:text-purple-600">Promote</button>
                        </form>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    @if($user->is_active)
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Active</span>
                    @else
                        <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">Inactive</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex gap-2 justify-center">
                             <form action="/users/{{ $user->id }}/edit" method="GET">
                                <button class="text-xs text-blue-600 hover:text-blue-800">Edit</button>
                            </form>
                        @if($user->is_active)
                            <form method="POST" action="/users/{{ $user->id }}/deactivate">
                                @csrf
                                <button class="text-xs text-yellow-600 hover:text-yellow-800">Deactivate</button>
                            </form>
                        @else
                            <form method="POST" action="/users/{{ $user->id }}/activate">
                                @csrf
                                <button class="text-xs text-green-600 hover:text-green-800">Activate</button>
                            </form>
                        @endif
                        @if($user->id !== auth()->id())
                            <form method="POST" action="/users/{{ $user->id }}"
                                onsubmit="return confirm('Delete {{ $user->full_name }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs text-red-500 hover:text-red-700">Delete</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection