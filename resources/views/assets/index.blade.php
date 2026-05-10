@extends('layouts.app')

@section('title', 'Fixed Assets')

@section('content')

<div class="mb-4 flex items-center justify-between">
    <p class="text-sm text-gray-500">{{ count($assets) }} assets</p>
    <a href="/assets/create" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">➕ Add Asset</a>
</div>

<!-- Summary -->
<div class="grid grid-cols-3 gap-4 mb-4">
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">Total Cost</p>
        <p class="text-xl font-bold text-gray-800">AED {{ number_format($totalCost, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">Accumulated Depreciation</p>
        <p class="text-xl font-bold text-red-600">AED {{ number_format($totalAccum, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">Net Book Value</p>
        <p class="text-xl font-bold text-green-700">AED {{ number_format($totalNBV, 2) }}</p>
    </div>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-200 bg-gray-50">
                <th class="px-4 py-2 text-left">Asset No.</th>
                <th class="px-4 py-2 text-left">Name</th>
                <th class="px-4 py-2 text-left">Category</th>
                <th class="px-4 py-2 text-left">Purchase Date</th>
                <th class="px-4 py-2 text-right">Cost</th>
                <th class="px-4 py-2 text-right">Accum. Depr.</th>
                <th class="px-4 py-2 text-right">NBV</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($assets as $asset)
            @php
                $statusColor = match($asset->status) {
                    'active'            => 'bg-green-100 text-green-700',
                    'fully_depreciated' => 'bg-gray-100 text-gray-500',
                    'disposed'          => 'bg-red-100 text-red-600',
                    default             => 'bg-gray-100 text-gray-500',
                };
            @endphp
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $asset->asset_number }}</td>
                <td class="px-4 py-2 font-medium text-gray-800">{{ $asset->name }}</td>
                <td class="px-4 py-2 text-gray-500">{{ $asset->category_name ?? '—' }}</td>
                <td class="px-4 py-2 text-gray-600">{{ $asset->purchase_date }}</td>
                <td class="px-4 py-2 text-right">{{ number_format($asset->purchase_cost, 2) }}</td>
                <td class="px-4 py-2 text-right text-red-500">{{ number_format($asset->accumulated_depreciation, 2) }}</td>
                <td class="px-4 py-2 text-right font-semibold text-green-700">{{ number_format($asset->net_book_value, 2) }}</td>
                <td class="px-4 py-2 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $statusColor }}">{{ str_replace('_', ' ', strtoupper($asset->status)) }}</span>
                </td>
                <td class="px-4 py-2 text-right">
                    <a href="/assets/{{ $asset->id }}" class="text-xs text-blue-500 hover:text-blue-700">View →</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                    No assets yet. <a href="/assets/create" class="text-green-600">Add one →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection