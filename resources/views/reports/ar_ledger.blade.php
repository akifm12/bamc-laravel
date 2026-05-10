@extends('layouts.app')

@section('title', 'AR Ledger')

@section('content')

<form method="GET" action="/reports/ar-ledger"
    class="bg-white rounded-lg border border-gray-200 p-4 mb-4 flex gap-3 items-end flex-wrap">
    <div>
        <label class="text-xs text-gray-500 block mb-1">As of Date</label>
        <input type="date" name="as_of" value="{{ $asOf }}"
            class="border border-gray-200 rounded px-3 py-1.5 text-sm">
    </div>
    <div>
        <label class="text-xs text-gray-500 block mb-1">Ledger From</label>
        <input type="date" name="date_from" value="{{ $dateFrom }}"
            class="border border-gray-200 rounded px-3 py-1.5 text-sm">
    </div>
    <div>
        <label class="text-xs text-gray-500 block mb-1">Ledger To</label>
        <input type="date" name="date_to" value="{{ $dateTo }}"
            class="border border-gray-200 rounded px-3 py-1.5 text-sm">
    </div>
    <div>
        <label class="text-xs text-gray-500 block mb-1">View</label>
        <select name="mode" class="border border-gray-200 rounded px-3 py-1.5 text-sm">
            <option value="balance" {{ $mode === 'balance' ? 'selected' : '' }}>Summary</option>
            <option value="ledger"  {{ $mode === 'ledger'  ? 'selected' : '' }}>Full Ledger</option>
        </select>
    </div>
    <button type="submit" class="bg-gray-700 text-white text-sm px-4 py-1.5 rounded">Run</button>
    <a href="/reports" class="text-sm text-gray-400 px-2 py-1.5">← Reports</a>
</form>

<!-- Summary -->
<div class="grid grid-cols-3 gap-4 mb-4">
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">Total Debits</p>
        <p class="text-xl font-bold text-gray-800">AED {{ number_format($totalDr, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">Total Credits</p>
        <p class="text-xl font-bold text-gray-800">AED {{ number_format($totalCr, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">Net AR Balance (as of {{ $asOf }})</p>
        <p class="text-xl font-bold {{ $totalBalance > 0 ? 'text-green-700' : 'text-gray-400' }}">
            AED {{ number_format($totalBalance, 2) }}
        </p>
    </div>
</div>

@if($mode === 'balance')
<!-- Summary Table -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between">
        <span class="font-semibold text-gray-700 text-sm">AR Balance by Client (as of {{ $asOf }})</span>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                <th class="px-4 py-2 text-left">Code</th>
                <th class="px-4 py-2 text-left">Client</th>
                <th class="px-4 py-2 text-right">Total Invoiced (Dr)</th>
                <th class="px-4 py-2 text-right">Total Received (Cr)</th>
                <th class="px-4 py-2 text-right">Balance Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse($arAccounts as $acc)
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $acc->code }}</td>
                <td class="px-4 py-2 font-medium text-gray-800">{{ $acc->name }}</td>
                <td class="px-4 py-2 text-right text-gray-600">{{ number_format($acc->total_dr, 2) }}</td>
                <td class="px-4 py-2 text-right text-gray-600">{{ number_format($acc->total_cr, 2) }}</td>
                <td class="px-4 py-2 text-right font-semibold {{ $acc->balance > 0 ? 'text-green-700' : ($acc->balance < 0 ? 'text-red-600' : 'text-gray-400') }}">
                    AED {{ number_format($acc->balance, 2) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-400">No AR balances found.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-gray-50 border-t border-gray-200 font-semibold">
                <td colspan="2" class="px-4 py-2 text-right text-xs text-gray-500">TOTAL</td>
                <td class="px-4 py-2 text-right">{{ number_format($totalDr, 2) }}</td>
                <td class="px-4 py-2 text-right">{{ number_format($totalCr, 2) }}</td>
                <td class="px-4 py-2 text-right text-green-700">AED {{ number_format($totalBalance, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

@else
<!-- Full Ledger -->
@foreach($arAccounts as $acc)
@if(isset($ledgerLines[$acc->code]))
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
        <div>
            <span class="font-semibold text-gray-700 text-sm">{{ $acc->name }}</span>
            <span class="text-xs text-gray-400 ml-2">{{ $acc->code }}</span>
        </div>
        <span class="font-semibold text-sm {{ $acc->balance > 0 ? 'text-green-700' : 'text-red-600' }}">
            Balance: AED {{ number_format($acc->balance, 2) }}
        </span>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                <th class="px-4 py-2 text-left">Date</th>
                <th class="px-4 py-2 text-left">Entry</th>
                <th class="px-4 py-2 text-left">Description</th>
                <th class="px-4 py-2 text-right">Debit</th>
                <th class="px-4 py-2 text-right">Credit</th>
                <th class="px-4 py-2 text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ledgerLines[$acc->code] as $line)
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 text-gray-600">{{ $line->date }}</td>
                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $line->entry_number }}</td>
                <td class="px-4 py-2 text-gray-700">{{ $line->description }}
                    @if($line->line_desc && $line->line_desc != $line->description)
                        <span class="text-gray-400 text-xs"> — {{ $line->line_desc }}</span>
                    @endif
                </td>
                <td class="px-4 py-2 text-right text-gray-800">
                    {{ $line->debit_amount > 0 ? number_format($line->debit_amount, 2) : '—' }}
                </td>
                <td class="px-4 py-2 text-right text-gray-800">
                    {{ $line->credit_amount > 0 ? number_format($line->credit_amount, 2) : '—' }}
                </td>
                <td class="px-4 py-2 text-right font-medium {{ $line->running_balance >= 0 ? 'text-gray-800' : 'text-red-600' }}">
                    {{ number_format($line->running_balance, 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endforeach
@endif

@endsection