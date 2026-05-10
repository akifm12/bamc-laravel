@extends('layouts.app')

@section('title', 'Inventory')

@section('content')

<div class="mb-4 flex items-center justify-between">
    <div class="flex gap-3">
        <a href="/inventory/movements" class="border border-gray-200 text-gray-600 text-sm px-4 py-2 rounded hover:bg-gray-50">📦 Stock Movements</a>
        <a href="/inventory/items/create" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">➕ New Item</a>
    </div>
</div>

<!-- Summary -->
<div class="grid grid-cols-3 gap-4 mb-4">
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">Total Items</p>
        <p class="text-xl font-bold text-gray-800">{{ $totalItems }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">Low Stock Items</p>
        <p class="text-xl font-bold {{ $lowStockItems > 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $lowStockItems }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">Total Inventory Value</p>
        <p class="text-xl font-bold text-gray-800">AED {{ number_format($totalValue, 2) }}</p>
    </div>
</div>

<!-- Items Table -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-200 bg-gray-50">
                <th class="px-4 py-2 text-left">Code</th>
                <th class="px-4 py-2 text-left">Name</th>
                <th class="px-4 py-2 text-left">Category</th>
                <th class="px-4 py-2 text-left">Type</th>
                <th class="px-4 py-2 text-right">Cost Price</th>
                <th class="px-4 py-2 text-right">Sale Price</th>
                <th class="px-4 py-2 text-right">Stock</th>
                <th class="px-4 py-2 text-right">Stock Value</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            @php
                $isLow = $item->current_stock <= $item->reorder_point && $item->reorder_point > 0;
            @endphp
            <tr class="border-b border-gray-50 hover:bg-gray-50 {{ $isLow ? 'bg-red-50' : '' }}">
                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $item->code }}</td>
                <td class="px-4 py-2 font-medium text-gray-800">{{ $item->name }}</td>
                <td class="px-4 py-2 text-gray-500">{{ $item->category_name ?? '—' }}</td>
                <td class="px-4 py-2 text-gray-500 capitalize">{{ $item->item_type }}</td>
                <td class="px-4 py-2 text-right">{{ number_format($item->cost_price, 2) }}</td>
                <td class="px-4 py-2 text-right">{{ number_format($item->standard_price, 2) }}</td>
                <td class="px-4 py-2 text-right {{ $isLow ? 'text-red-600 font-semibold' : 'text-gray-800' }}">
                    {{ number_format($item->current_stock, 2) }} {{ $item->unit_of_measure }}
                    @if($isLow) ⚠️ @endif
                </td>
                <td class="px-4 py-2 text-right text-gray-600">AED {{ number_format($item->current_stock * $item->cost_price, 2) }}</td>
                <td class="px-4 py-2 text-center">
                    @if($item->is_active)
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Active</span>
                    @else
                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Inactive</span>
                    @endif
                </td>
                <td class="px-4 py-2 text-right">
                    <a href="/inventory/items/{{ $item->id }}" class="text-xs text-blue-500 hover:text-blue-700">View →</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="px-4 py-8 text-center text-gray-400">
                    No items yet. <a href="/inventory/items/create" class="text-green-600">Add one →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection