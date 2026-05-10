@extends('layouts.app')

@section('title', 'Companies')

@section('content')

<div class="mb-4 flex items-center justify-between">
    <p class="text-sm text-gray-500">{{ count($companies) }} companies</p>
    <a href="/settings/companies/create"
        class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">
        ➕ New Company
    </a>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-200 bg-gray-50">
                <th class="px-4 py-2 text-left">Code</th>
                <th class="px-4 py-2 text-left">Name</th>
                <th class="px-4 py-2 text-left">TRN</th>
                <th class="px-4 py-2 text-left">Email</th>
                <th class="px-4 py-2 text-left">Emirate</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($companies as $company)
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $company->code }}</td>
                <td class="px-4 py-2 font-medium text-gray-800">{{ $company->name }}</td>
                <td class="px-4 py-2 text-gray-500">{{ $company->trn ?? '—' }}</td>
                <td class="px-4 py-2 text-gray-500">{{ $company->email ?? '—' }}</td>
                <td class="px-4 py-2 text-gray-500">{{ $company->emirate ?? '—' }}</td>
                <td class="px-4 py-2 text-center">
                    @if($company->is_active)
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Active</span>
                    @else
                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Inactive</span>
                    @endif
                </td>
                <td class="px-4 py-2 text-right">
                    <a href="/settings/company?company_id={{ $company->id }}"
                        class="text-xs text-blue-500 hover:text-blue-700">Edit →</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-400">No companies found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection