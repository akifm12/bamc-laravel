@extends('layouts.app')

@section('title', $account->account_name)

@section('content')

<div class="max-w-5xl">

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $account->account_name }}</h2>
                <p class="text-gray-500 text-sm mt-1">{{ $account->bank_name }}
                    @if($account->account_number) — {{ $account->account_number }} @endif
                </p>
                @if($account->iban)
                    <p class="text-xs text-gray-400 mt-1">IBAN: {{ $account->iban }}</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">Current Balance</p>
                <p class="text-2xl font-bold {{ $runningBalance >= 0 ? 'text-green-700' : 'text-red-600' }}">
                    AED {{ number_format($runningBalance, 2) }}
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-4">

        <!-- Statement -->
        <div class="col-span-2">
            <form method="GET" action="/banking/{{ $account->id }}"
                class="bg-white rounded-lg border border-gray-200 p-4 mb-4 flex gap-3 items-end">
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
                <button type="submit" class="bg-gray-700 text-white text-sm px-4 py-1.5 rounded">Filter</button>
            </form>

            <!-- Summary -->
            <div class="grid grid-cols-3 gap-3 mb-3">
                <div class="bg-white rounded-lg border border-gray-200 p-3">
                    <p class="text-xs text-gray-400">Total Receipts</p>
                    <p class="text-lg font-bold text-green-700">AED {{ number_format($totalDebits, 2) }}</p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-3">
                    <p class="text-xs text-gray-400">Total Payments</p>
                    <p class="text-lg font-bold text-red-600">AED {{ number_format($totalCredits, 2) }}</p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-3">
                    <p class="text-xs text-gray-400">Net Movement</p>
                    <p class="text-lg font-bold {{ ($totalDebits - $totalCredits) >= 0 ? 'text-green-700' : 'text-red-600' }}">
                        AED {{ number_format($totalDebits - $totalCredits, 2) }}
                    </p>
                </div>
            </div>

            <!-- Transactions -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-left">Reference</th>
                            <th class="px-4 py-2 text-left">Description</th>
                            <th class="px-4 py-2 text-right">Debit</th>
                            <th class="px-4 py-2 text-right">Credit</th>
                            <th class="px-4 py-2 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                        <tr class="border-b border-gray-50 hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-600">{{ $txn->date }}</td>
                            <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $txn->entry_number }}</td>
                            <td class="px-4 py-2 text-gray-800">{{ $txn->description }}</td>
                            <td class="px-4 py-2 text-right text-green-700">
                                {{ $txn->debit_amount > 0 ? number_format($txn->debit_amount, 2) : '—' }}
                            </td>
                            <td class="px-4 py-2 text-right text-red-600">
                                {{ $txn->credit_amount > 0 ? number_format($txn->credit_amount, 2) : '—' }}
                            </td>
                            <td class="px-4 py-2 text-right font-medium {{ $txn->running_balance >= 0 ? 'text-gray-800' : 'text-red-600' }}">
                                {{ number_format($txn->running_balance, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">No transactions in this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 border-t border-gray-200 font-semibold">
                            <td colspan="3" class="px-4 py-2 text-right text-xs text-gray-500">TOTALS</td>
                            <td class="px-4 py-2 text-right text-green-700">AED {{ number_format($totalDebits, 2) }}</td>
                            <td class="px-4 py-2 text-right text-red-600">AED {{ number_format($totalCredits, 2) }}</td>
                            <td class="px-4 py-2 text-right">AED {{ number_format($runningBalance, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <a href="/banking" class="text-sm text-gray-500 hover:text-gray-700">← Back to Banking</a>

</div>

@endsection