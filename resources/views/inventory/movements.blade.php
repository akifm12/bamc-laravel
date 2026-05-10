@extends('layouts.app')

@section('title', 'Stock Movements')

@section('content')

<div class="max-w-5xl">

<div class="grid grid-cols-2 gap-4 mb-4">

    <!-- Record Movement Form -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Record Stock Movement</h3>
        <form method="POST" action="/inventory/movements" class="space-y-3">
            @csrf
            <div>
                <label class="text-xs text-gray-500 block mb-1">Item *</label>
                <select name="item_id" required class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">— Select Item —</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}">{{ $item->code }} — {{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Movement Type *</label>
                <select name="movement_type" required class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <optgroup label="Inbound">
                        <option value="purchase">Purchase</option>
                        <option value="opening">Opening Stock</option>
                        <option value="adjustment_in">Adjustment (In)</option>
                        <option value="transfer_in">Transfer (In)</option>
                    </optgroup>
                    <optgroup label="Outbound">
                        <option value="sale">Sale</option>
                        <option value="adjustment_out">Adjustment (Out)</option>
                        <option value="transfer_out">Transfer (Out)</option>
                    </optgroup>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Quantity *</label>
                    <input type="number" name="quantity" step="0.01" min="0.01" required
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Unit Cost (AED)</label>
                    <input type="number" name="unit_cost" step="0.01" value="0"
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Date *</label>
                    <input type="date" name="movement_date" value="{{ date('Y-m-d') }}" required
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Warehouse</label>
                    <select name="warehouse_id" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                        <option value="">Default</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Reference</label>
                <input type="text" name="reference" placeholder="Invoice/PO number"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Notes</label>
                <input type="text" name="notes"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <button type="submit" class="w-full bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">
                Record Movement
            </button>
        </form>
    </div>

    <!-- Filter + Summary -->
    <div class="space-y-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <form method="GET" action="/inventory/movements" class="space-y-3">
                <h3 class="font-semibold text-gray-700 text-sm">Filter Movements</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">From</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                            class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">To</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                            class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    </div>
                </div>
                <button type="submit" class="w-full bg-gray-700 text-white text-sm px-4 py-1.5 rounded">Filter</button>
            </form>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-3">Period Summary</h3>
            @php
                $inbound  = $movements->filter(fn($m) => $m->quantity > 0)->sum('quantity');
                $outbound = abs($movements->filter(fn($m) => $m->quantity < 0)->sum('quantity'));
                $inValue  = $movements->filter(fn($m) => $m->quantity > 0)->sum('total_cost');
                $outValue = $movements->filter(fn($m) => $m->quantity < 0)->sum('total_cost');
            @endphp
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Inbound</span>
                    <span class="text-green-700 font-semibold">+{{ number_format($inbound, 2) }} units</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Outbound</span>
                    <span class="text-red-600 font-semibold">-{{ number_format($outbound, 2) }} units</span>
                </div>
                <div class="flex justify-between border-t border-gray-100 pt-2">
                    <span class="text-gray-500">Inbound Value</span>
                    <span class="font-semibold">AED {{ number_format($inValue, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Movements</span>
                    <span class="font-semibold">{{ count($movements) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Movements Table -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
        <span class="font-semibold text-gray-700 text-sm">Movement History</span>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                <th class="px-4 py-2 text-left">Date</th>
                <th class="px-4 py-2 text-left">Item</th>
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
                    <p class="font-medium text-gray-800">{{ $mov->item_name }}</p>
                    <p class="text-xs text-gray-400">{{ $mov->item_code }}</p>
                </td>
                <td class="px-4 py-2">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $mov->quantity > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                        {{ str_replace('_', ' ', strtoupper($mov->movement_type)) }}
                    </span>
                </td>
                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $mov->reference ?? '—' }}</td>
                <td class="px-4 py-2 text-right font-semibold {{ $mov->quantity > 0 ? 'text-green-700' : 'text-red-600' }}">
                    {{ $mov->quantity > 0 ? '+' : '' }}{{ number_format($mov->quantity, 2) }}
                </td>
                <td class="px-4 py-2 text-right">{{ number_format($mov->unit_cost, 2) }}</td>
                <td class="px-4 py-2 text-right">{{ number_format($mov->total_cost, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-400">No movements found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

</div>

@endsection