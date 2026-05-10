@extends('layouts.app')

@section('title', 'Profit & Loss')

@section('content')

<!-- Filters -->
<form method="GET" action="/reports/pnl" class="bg-white rounded-lg border border-gray-200 p-4 mb-4 flex gap-3 items-end">
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
    <a href="/reports/pnl/export?date_from={{ $dateFrom }}&date_to={{ $dateTo }}&format=csv"
        class="text-xs bg-white border border-gray-200 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50">
        📄 CSV
    </a>
    <a href="/reports/pnl/export?date_from={{ $dateFrom }}&date_to={{ $dateTo }}&format=excel"
        class="text-xs bg-white border border-gray-200 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50">
        📊 Excel
    </a>
    <a href="/reports/pnl/export?date_from={{ $dateFrom }}&date_to={{ $dateTo }}&format=pdf"
        class="text-xs bg-white border border-gray-200 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50">
        📑 PDF
    </a>
</div>
<div class="max-w-3xl">

    <!-- Revenue -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">🟨 Revenue</span>
        </div>
        <table class="w-full text-sm">
            <tbody>
                @foreach($revenue as $row)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-400 w-28">{{ $row->code }}</td>
                    <td class="px-4 py-2 text-gray-800">{{ $row->name }}</td>
                    <td class="px-4 py-2 text-right text-gray-800 w-40">AED {{ number_format($row->balance, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t border-gray-200 font-semibold">
                    <td colspan="2" class="px-4 py-2 text-right text-xs text-gray-500">TOTAL REVENUE</td>
                    <td class="px-4 py-2 text-right text-green-700">AED {{ number_format($totalRevenue, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Expenses -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">🟧 Operating Expenses</span>
        </div>
        <table class="w-full text-sm">
            <tbody>
                @foreach($expenses as $row)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-400 w-28">{{ $row->code }}</td>
                    <td class="px-4 py-2 text-gray-800">{{ $row->name }}</td>
                    <td class="px-4 py-2 text-right text-gray-800 w-40">AED {{ number_format(abs($row->balance), 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t border-gray-200 font-semibold">
                    <td colspan="2" class="px-4 py-2 text-right text-xs text-gray-500">TOTAL EXPENSES</td>
                    <td class="px-4 py-2 text-right text-red-600">AED {{ number_format($totalExpenses, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Net Profit -->
    <div class="rounded-lg border p-4 {{ $netProfit >= 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
        <div class="flex justify-between items-center">
            <span class="font-bold text-gray-700">{{ $netProfit >= 0 ? '✅ Net Profit' : '⚠️ Net Loss' }}</span>
            <span class="font-bold text-xl {{ $netProfit >= 0 ? 'text-green-700' : 'text-red-700' }}">
                AED {{ number_format(abs($netProfit), 2) }}
            </span>
        </div>
        <p class="text-xs text-gray-400 mt-1">{{ $dateFrom }} to {{ $dateTo }}</p>
    </div>

</div>

@endsection