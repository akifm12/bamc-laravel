@extends('layouts.app')

@section('title', 'Customers')

@section('content')

<div class="mb-4 flex items-center justify-between">
    <p class="text-sm text-gray-500">{{ count($customers) }} customers</p>
    <a href="/customers/create" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">➕ New Customer</a>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-200 bg-gray-50">
                <th class="px-4 py-2 text-left">Code</th>
                <th class="px-4 py-2 text-left">Name</th>
                <th class="px-4 py-2 text-left">TRN</th>
                <th class="px-4 py-2 text-left">Email</th>
                <th class="px-4 py-2 text-left">Phone</th>
                <th class="px-4 py-2 text-left">Emirate</th>
                <th class="px-4 py-2 text-center">VAT</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $c)
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $c->code }}</td>
                <td class="px-4 py-2 font-medium text-gray-800">{{ $c->name }}</td>
                <td class="px-4 py-2 text-gray-500">{{ $c->trn ?? '—' }}</td>
                <td class="px-4 py-2 text-gray-500">{{ $c->email ?? '—' }}</td>
                <td class="px-4 py-2 text-gray-500">{{ $c->phone ?? $c->mobile ?? '—' }}</td>
                <td class="px-4 py-2 text-gray-500">{{ $c->emirate ?? '—' }}</td>
                <td class="px-4 py-2 text-center">
                    @if($c->is_vat_registered)
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">VAT Reg</span>
                    @else
                        <span class="text-xs text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-4 py-2 text-center">
                    @if($c->is_active)
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Active</span>
                    @else
                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Inactive</span>
                    @endif
                </td>
                <td class="px-4 py-2 text-right">
                    <a href="/customers/{{ $c->id }}" class="text-xs text-blue-500 hover:text-blue-700">View →</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-4 py-8 text-center text-gray-400">No customers found. <a href="/customers/create" class="text-green-600">Add one →</a></td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection