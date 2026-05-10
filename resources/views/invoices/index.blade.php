@extends('layouts.app')

@section('title', 'Invoices')

@section('content')

<div class="mb-4 flex items-center justify-between">
    <a href="/invoices/create" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">➕ New Invoice</a>
</div>

<!-- Filters -->
<form method="GET" action="/invoices" class="bg-white rounded-lg border border-gray-200 p-4 mb-4 flex gap-3 items-end">
    <div>
        <label class="text-xs text-gray-500 block mb-1">From</label>
        <input type="date" name="date_from" value="{{ $dateFrom }}"
            class="border border-gray-200 rounded px-3 py-1.5 text-sm">
    </div>
    <div>
        <label class="text-xs text-gray-500 block mb-1">To</label>
        <input type="date" name="date_to" value="{{ $dateTo }}"
            class="border border-gray-200 rounded px-3 py-1.5 text-sm">
    </div>
    <div>
        <label class="text-xs text-gray-500 block mb-1">Status</label>
        <select name="status" class="border border-gray-200 rounded px-3 py-1.5 text-sm">
            <option value="all"     {{ $status == 'all'     ? 'selected' : '' }}>All</option>
            <option value="draft"   {{ $status == 'draft'   ? 'selected' : '' }}>Draft</option>
            <option value="approved"{{ $status == 'approved'? 'selected' : '' }}>Approved</option>
            <option value="void"    {{ $status == 'void'    ? 'selected' : '' }}>Void</option>
        </select>
    </div>
    <button type="submit" class="bg-gray-700 text-white text-sm px-4 py-1.5 rounded hover:bg-gray-800">Search</button>
</form>

<!-- Summary -->
<div class="grid grid-cols-3 gap-4 mb-4">
    <div class="bg-white rounded-lg border border-gray-200 p-3">
        <p class="text-xs text-gray-400">Invoices</p>
        <p class="text-xl font-bold text-gray-800">{{ count($invoices) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-3">
        <p class="text-xs text-gray-400">Total Billed</p>
        <p class="text-xl font-bold text-gray-800">AED {{ number_format($totalAmount, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-3">
        <p class="text-xs text-gray-400">Total Due</p>
        <p class="text-xl font-bold {{ $totalDue > 0 ? 'text-red-600' : 'text-green-600' }}">AED {{ number_format($totalDue, 2) }}</p>
    </div>
</div>

<!-- Table -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-200 bg-gray-50">
                <th class="px-4 py-2 text-left">Number</th>
                <th class="px-4 py-2 text-left">Customer</th>
                <th class="px-4 py-2 text-left">Date</th>
                <th class="px-4 py-2 text-left">Due Date</th>
                <th class="px-4 py-2 text-right">Amount</th>
                <th class="px-4 py-2 text-right">Due</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $inv)
            @php
                $status = strtolower($inv->status ?? 'draft');
                $statusColor = match($status) {
                    'approved' => 'bg-green-100 text-green-700',
                    'draft'    => 'bg-yellow-100 text-yellow-700',
                    'void'     => 'bg-gray-100 text-gray-500',
                    default    => 'bg-gray-100 text-gray-500',
                };
            @endphp
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $inv->invoice_number }}</td>
                <td class="px-4 py-2 font-medium text-gray-800">{{ $inv->customer_name }}</td>
                <td class="px-4 py-2 text-gray-600">{{ $inv->invoice_date }}</td>
                <td class="px-4 py-2 text-gray-600">{{ $inv->due_date ?? '—' }}</td>
                <td class="px-4 py-2 text-right text-gray-800">{{ number_format($inv->total_amount, 2) }}</td>
                <td class="px-4 py-2 text-right {{ $inv->amount_due > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ number_format($inv->amount_due, 2) }}
                </td>
                <td class="px-4 py-2 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $statusColor }}">{{ strtoupper($status) }}</span>
                </td>
                <td class="px-4 py-2 text-right">
                    <a href="/invoices/{{ $inv->id }}" class="text-xs text-blue-500 hover:text-blue-700">View →</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-400">No invoices found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection