@extends('layouts.app')

@section('title', 'VAT Ledger')

@section('content')

<!-- Filters -->
<form method="GET" action="/reports/vat-ledger" class="bg-white rounded-lg border border-gray-200 p-4 mb-4 flex gap-3 items-end">
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

<!-- Summary Cards -->
<div class="grid grid-cols-5 gap-3 mb-5">
    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
        <p class="text-xs text-blue-500 font-medium mb-1">Output VAT Collected</p>
        <p class="text-lg font-bold text-blue-800">AED {{ number_format($outputVAT, 2) }}</p>
        <p class="text-xs text-blue-400 mt-1">From customer invoices</p>
    </div>
    <div class="bg-orange-50 border border-orange-100 rounded-lg p-4">
        <p class="text-xs text-orange-500 font-medium mb-1">Input VAT Paid</p>
        <p class="text-lg font-bold text-orange-800">AED {{ number_format($inputVAT, 2) }}</p>
        <p class="text-xs text-orange-400 mt-1">On purchases &amp; bills</p>
    </div>
    <div class="bg-purple-50 border border-purple-100 rounded-lg p-4">
        <p class="text-xs text-purple-500 font-medium mb-1">Net VAT Payable</p>
        <p class="text-lg font-bold {{ $netVATPayable >= 0 ? 'text-purple-800' : 'text-green-700' }}">
            AED {{ number_format(abs($netVATPayable), 2) }}
            @if($netVATPayable < 0) <span class="text-xs font-normal">(refund)</span>@endif
        </p>
        <p class="text-xs text-purple-400 mt-1">Output − Input</p>
    </div>
    <div class="bg-red-50 border border-red-100 rounded-lg p-4">
        <p class="text-xs text-red-500 font-medium mb-1">Paid to Government</p>
        <p class="text-lg font-bold text-red-800">AED {{ number_format($vatPaidToGovt, 2) }}</p>
        <p class="text-xs text-red-400 mt-1">VAT settlements to FTA</p>
    </div>
    <div class="bg-{{ $balance > 0.01 ? 'yellow' : 'green' }}-50 border border-{{ $balance > 0.01 ? 'yellow' : 'green' }}-100 rounded-lg p-4">
        <p class="text-xs text-{{ $balance > 0.01 ? 'yellow' : 'green' }}-600 font-medium mb-1">Balance Outstanding</p>
        <p class="text-lg font-bold text-{{ $balance > 0.01 ? 'yellow' : 'green' }}-800">AED {{ number_format(abs($balance), 2) }}</p>
        <p class="text-xs text-{{ $balance > 0.01 ? 'yellow' : 'green' }}-500 mt-1">
            {{ $balance > 0.01 ? 'Still owed to FTA' : ($balance < -0.01 ? 'Overpaid / refund due' : 'Fully settled') }}
        </p>
    </div>
</div>

<!-- Output VAT - collected from customers -->
<div class="mb-5 bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-4 py-2.5 bg-blue-50 border-b border-blue-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="font-semibold text-blue-800 text-sm">Output VAT - Collected from Customers</span>
            <span class="text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">{{ $outputLines->count() }} entries</span>
        </div>
        <span class="text-sm font-semibold text-blue-800">AED {{ number_format($outputVAT, 2) }} collected &nbsp;|&nbsp; AED {{ number_format($vatPaidToGovt, 2) }} paid to govt</span>
    </div>
    @if($outputLines->isEmpty())
    <p class="text-sm text-gray-400 px-4 py-4">No output VAT transactions in this period.</p>
    @else
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100 bg-gray-50">
                <th class="px-4 py-2 text-left w-28">Date</th>
                <th class="px-4 py-2 text-left w-36">Reference</th>
                <th class="px-4 py-2 text-left">Description</th>
                <th class="px-4 py-2 text-left w-40">Account</th>
                <th class="px-4 py-2 text-right w-36">VAT Collected (Cr)</th>
                <th class="px-4 py-2 text-right w-36">Paid to Govt (Dr)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($outputLines as $row)
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 text-gray-500 text-xs">{{ $row->entry_date }}</td>
                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $row->entry_number }}</td>
                <td class="px-4 py-2 text-gray-700">{{ $row->description ?: '-' }}</td>
                <td class="px-4 py-2 text-xs text-gray-500">{{ $row->account_name }}</td>
                <td class="px-4 py-2 text-right text-blue-700 font-medium">
                    {{ $row->credit_amount > 0 ? number_format($row->credit_amount, 2) : '-' }}
                </td>
                <td class="px-4 py-2 text-right text-red-600 font-medium">
                    {{ $row->debit_amount > 0 ? number_format($row->debit_amount, 2) : '-' }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-blue-50 border-t border-blue-100 font-semibold text-sm">
                <td colspan="4" class="px-4 py-2 text-right text-xs text-gray-500">TOTAL</td>
                <td class="px-4 py-2 text-right text-blue-800">{{ number_format($outputVAT, 2) }}</td>
                <td class="px-4 py-2 text-right text-red-700">{{ number_format($vatPaidToGovt, 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif
</div>

<!-- Input VAT - paid on purchases -->
<div class="mb-5 bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-4 py-2.5 bg-orange-50 border-b border-orange-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="font-semibold text-orange-800 text-sm">Input VAT - Paid on Purchases</span>
            <span class="text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full">{{ $inputLines->count() }} entries</span>
        </div>
        <span class="text-sm font-semibold text-orange-800">AED {{ number_format($inputVAT, 2) }} recoverable</span>
    </div>
    @if($inputLines->isEmpty())
    <p class="text-sm text-gray-400 px-4 py-4">No input VAT transactions in this period.</p>
    @else
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100 bg-gray-50">
                <th class="px-4 py-2 text-left w-28">Date</th>
                <th class="px-4 py-2 text-left w-36">Reference</th>
                <th class="px-4 py-2 text-left">Description</th>
                <th class="px-4 py-2 text-left w-40">Account</th>
                <th class="px-4 py-2 text-right w-36">VAT Paid (Dr)</th>
                <th class="px-4 py-2 text-right w-36">Recovered (Cr)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inputLines as $row)
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 text-gray-500 text-xs">{{ $row->entry_date }}</td>
                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $row->entry_number }}</td>
                <td class="px-4 py-2 text-gray-700">{{ $row->description ?: '-' }}</td>
                <td class="px-4 py-2 text-xs text-gray-500">{{ $row->account_name }}</td>
                <td class="px-4 py-2 text-right text-orange-700 font-medium">
                    {{ $row->debit_amount > 0 ? number_format($row->debit_amount, 2) : '-' }}
                </td>
                <td class="px-4 py-2 text-right text-green-600 font-medium">
                    {{ $row->credit_amount > 0 ? number_format($row->credit_amount, 2) : '-' }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-orange-50 border-t border-orange-100 font-semibold text-sm">
                <td colspan="4" class="px-4 py-2 text-right text-xs text-gray-500">TOTAL</td>
                <td class="px-4 py-2 text-right text-orange-800">{{ number_format($inputVAT, 2) }}</td>
                <td class="px-4 py-2 text-right text-green-700">{{ number_format($inputRecovered, 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif
</div>

<!-- Net Position Summary -->
<div class="bg-white rounded-lg border border-gray-200 p-5">
    <h3 class="font-semibold text-gray-700 text-sm mb-4">VAT Position Summary</h3>
    <table class="w-64 text-sm">
        <tr>
            <td class="py-1 text-gray-600">Output VAT Collected</td>
            <td class="py-1 text-right font-medium text-gray-800 pl-8">{{ number_format($outputVAT, 2) }}</td>
        </tr>
        <tr>
            <td class="py-1 text-gray-600">Less: Input VAT Recoverable</td>
            <td class="py-1 text-right font-medium text-gray-800">({{ number_format($inputVAT, 2) }})</td>
        </tr>
        <tr class="border-t border-gray-200">
            <td class="py-1.5 font-semibold text-gray-700">Net VAT Payable</td>
            <td class="py-1.5 text-right font-bold text-gray-800">{{ number_format($netVATPayable, 2) }}</td>
        </tr>
        <tr>
            <td class="py-1 text-gray-600">Less: Paid to Government</td>
            <td class="py-1 text-right font-medium text-gray-800">({{ number_format($vatPaidToGovt, 2) }})</td>
        </tr>
        <tr class="border-t-2 border-gray-300">
            <td class="py-2 font-bold text-gray-800">Balance Outstanding</td>
            <td class="py-2 text-right font-bold text-lg {{ $balance > 0.01 ? 'text-red-600' : 'text-green-600' }}">
                {{ number_format(abs($balance), 2) }}
                @if($balance < -0.01) <span class="text-xs">(CR)</span>@endif
            </td>
        </tr>
    </table>
    @if(abs($balance) < 0.01)
    <p class="text-xs text-green-600 mt-3">✅ All VAT liabilities are fully settled for this period.</p>
    @elseif($balance > 0)
    <p class="text-xs text-red-500 mt-3">⚠️ AED {{ number_format($balance, 2) }} remains payable to the FTA.</p>
    @else
    <p class="text-xs text-blue-500 mt-3">ℹ️ AED {{ number_format(abs($balance), 2) }} credit - may be refundable or carried forward.</p>
    @endif
</div>

@endsection
