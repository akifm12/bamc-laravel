@extends('layouts.app')

@section('title', 'Trial Balance')

@section('content')

<!-- Filters -->
<form method="GET" action="/reports/trial-balance" class="bg-white rounded-lg border border-gray-200 p-4 mb-4 flex gap-3 items-end">
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
    <button type="submit" class="bg-gray-700 text-white text-sm px-4 py-1.5 rounded hover:bg-gray-800">Run Report</button>
    <a href="/reports" class="text-sm text-gray-400 px-2 py-1.5">← Reports</a>
</form>
<!-- Export buttons -->
<div class="flex gap-2 mb-4">
    <a href="/reports/trial-balance/export?date_from={{ $dateFrom }}&date_to={{ $dateTo }}&format=csv"
        class="text-xs bg-white border border-gray-200 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50">
        📄 CSV
    </a>
    <a href="/reports/trial-balance/export?date_from={{ $dateFrom }}&date_to={{ $dateTo }}&format=excel"
        class="text-xs bg-white border border-gray-200 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50">
        📊 Excel
    </a>
    <a href="/reports/trial-balance/export?date_from={{ $dateFrom }}&date_to={{ $dateTo }}&format=pdf"
        class="text-xs bg-white border border-gray-200 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50">
        📑 PDF
    </a>
</div>
@php
$typeLabels = [
    'ASSET'     => ['label' => 'Assets',      'icon' => '🟦'],
    'LIABILITY' => ['label' => 'Liabilities', 'icon' => '🟥'],
    'EQUITY'    => ['label' => 'Equity',      'icon' => '🟩'],
    'REVENUE'   => ['label' => 'Revenue',     'icon' => '🟨'],
    'EXPENSE'   => ['label' => 'Expenses',    'icon' => '🟧'],
];
@endphp

@foreach($typeLabels as $type => $meta)
@if(isset($grouped[$type]))
<div class="mb-4 bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center gap-2">
        <span>{{ $meta['icon'] }}</span>
        <span class="font-semibold text-gray-700 text-sm">{{ $meta['label'] }}</span>
        <span class="text-xs bg-gray-200 text-gray-500 px-2 py-0.5 rounded-full">{{ count($grouped[$type]) }}</span>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                <th class="px-4 py-2 text-left w-28">Code</th>
                <th class="px-4 py-2 text-left">Account Name</th>
                <th class="px-4 py-2 text-right w-36">Debit</th>
                <th class="px-4 py-2 text-right w-36">Credit</th>
                <th class="px-4 py-2 text-right w-36">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grouped[$type] as $row)
            @php
                $balance = $row->total_debit - $row->total_credit;
            @endphp
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $row->code }}</td>
                <td class="px-4 py-2 text-gray-800">{{ $row->name }}</td>
                <td class="px-4 py-2 text-right text-gray-700">{{ $row->total_debit > 0 ? number_format($row->total_debit, 2) : '—' }}</td>
                <td class="px-4 py-2 text-right text-gray-700">{{ $row->total_credit > 0 ? number_format($row->total_credit, 2) : '—' }}</td>
                <td class="px-4 py-2 text-right font-medium {{ $balance < 0 ? 'text-red-600' : 'text-gray-800' }}">
                    {{ $balance < 0 ? '(' . number_format(abs($balance), 2) . ')' : number_format($balance, 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-gray-50 border-t border-gray-200 font-semibold text-sm">
                <td colspan="2" class="px-4 py-2 text-right text-xs text-gray-500">SUBTOTAL</td>
                <td class="px-4 py-2 text-right">{{ number_format(collect($grouped[$type])->sum('total_debit'), 2) }}</td>
                <td class="px-4 py-2 text-right">{{ number_format(collect($grouped[$type])->sum('total_credit'), 2) }}</td>
                <td class="px-4 py-2 text-right">{{ number_format(collect($grouped[$type])->sum(fn($r) => $r->total_debit - $r->total_credit), 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endif
@endforeach

<!-- Grand Total -->
<div class="bg-white rounded-lg border border-gray-200 p-4">
    <div class="flex justify-between items-center">
        <span class="font-semibold text-gray-700">Grand Total</span>
        <div class="flex gap-8">
            <div class="text-right">
                <p class="text-xs text-gray-400">Total Debits</p>
                <p class="font-bold text-gray-800">AED {{ number_format($totalDebit, 2) }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">Total Credits</p>
                <p class="font-bold text-gray-800">AED {{ number_format($totalCredit, 2) }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">Status</p>
                @if(abs($totalDebit - $totalCredit) < 0.01)
                    <p class="font-bold text-green-600">✅ Balanced</p>
                @else
                    <p class="font-bold text-red-600">⚠️ Out of balance</p>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection