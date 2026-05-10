@extends('layouts.app')

@section('title', $item->name)

@section('content')

<div class="max-w-4xl">

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $item->name }}</h2>
                <p class="text-gray-500 text-sm mt-1">{{ $item->code }} — {{ $item->category_name ?? 'No category' }}</p>
                @if($item->description)
                    <p class="text-xs text-gray-400 mt-1">{{ $item->description }}</p>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="/inventory/movements?item_id={{ $item->id }}"
                    class="text-sm bg-green-700 text-white px-3 py-1.5 rounded hover:bg-green-800">
                    ➕ Record Movement
                </a>
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-4 gap-4 mb-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Total Stock</p>
            <p class="text-xl font-bold {{ $totalStock <= $item->reorder_point && $item->reorder_point > 0 ? 'text-red-600' : 'text-gray-800' }}">
                {{ number_format($totalStock, 2) }} {{ $item->unit_of_measure }}
            </p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Cost Price</p>
            <p class="text-xl font-bold text-gray-800">AED {{ number_format($item->cost_price, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Sale Price</p>
            <p class="text-xl font-bold text-gray-800">AED {{ number_format($item->standard_price, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Stock Value</p>
            <p class="text-xl font-bold text-green-700">AED {{ number_format($totalStock * $item->cost_price, 2) }}</p>
        </div>
    </div>

    <!-- Stock by Warehouse -->
    @if($stockByWarehouse->count() > 0)
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">Stock by Warehouse</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                    <th class="px-4 py-2 text-left">Warehouse</th>
                    <th class="px-4 py-2 text-right">Quantity</th>
                    <th class="px-4 py-2 text-right">Avg Cost</th>
                    <th class="px-4 py-2 text-right">Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stockByWarehouse as $stock)
                <tr class="border-b border-gray-50">
                    <td class="px-4 py-2">{{ $stock->warehouse_name ?? 'Default' }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($stock->quantity, 2) }}</td>
                    <td class="px-4 py-2 text-right">AED {{ number_format($stock->average_cost, 2) }}</td>
                    <td class="px-4 py-2 text-right">AED {{ number_format($stock->quantity * $stock->average_cost, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Movement History -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">Stock Movement History</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-left">Type</th>
                    <th class="px-4 py-2 text-left">Reference</th>
                    <th class="px-4 py-2 text-right">Quantity</th>
                    <th class="px-4 py-2 text-right">Unit Cost</th>
                    <th class="px-4 py-2 text-right">Total Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $mov)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 text-gray-600">{{ $mov->movement_date }}</td>
                    <td class="px-4 py-2">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $mov->quantity > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                            {{ str_replace('_', ' ', strtoupper($mov->movement_type)) }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-gray-500 font-mono text-xs">{{ $mov->reference ?? '—' }}</td>
                    <td class="px-4 py-2 text-right {{ $mov->quantity > 0 ? 'text-green-700' : 'text-red-600' }} font-semibold">
                        {{ $mov->quantity > 0 ? '+' : '' }}{{ number_format($mov->quantity, 2) }}
                    </td>
                    <td class="px-4 py-2 text-right">AED {{ number_format($mov->unit_cost, 2) }}</td>
                    <td class="px-4 py-2 text-right">AED {{ number_format($mov->total_cost, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">No movements yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <a href="/inventory" class="text-sm text-gray-500 hover:text-gray-700">← Back to Inventory</a>

</div>

@endsection