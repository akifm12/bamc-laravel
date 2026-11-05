@extends('layouts.app')
@section('title', 'Account Statement — ' . $customer->name)
@section('content')

<div class="max-w-5xl">

    <!-- Filter bar -->
    <form method="GET" action="/customers/{{ $customer->id }}/statement"
          class="bg-white rounded-lg border border-gray-200 p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="text-xs text-gray-500 block mb-1">Customer</label>
            <div class="text-sm font-semibold text-gray-700 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded">
                {{ $customer->name }}
            </div>
        </div>
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
        <button type="submit"
            class="bg-green-700 text-white text-sm px-4 py-1.5 rounded hover:bg-green-800">
            Apply
        </button>
        <a href="/customers/{{ $customer->id }}"
            class="text-sm text-gray-500 px-3 py-1.5 hover:text-gray-700">← Back</a>
        <a href="/customers/{{ $customer->id }}/statement?date_from={{ $dateFrom }}&date_to={{ $dateTo }}&print=1"
            onclick="window.print(); return false;"
            class="ml-auto text-sm text-gray-500 border border-gray-200 px-3 py-1.5 rounded hover:bg-gray-50">
            🖨 Print
        </a>
    </form>

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-lg font-bold text-gray-800">{{ $customer->name }}</h2>
                @if($customer->trn)<p class="text-xs text-gray-500">TRN: {{ $customer->trn }}</p>@endif
                @if($customer->email)<p class="text-xs text-gray-500">{{ $customer->email }}</p>@endif
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500">Statement Period</p>
                <p class="text-sm font-semibold text-gray-700">{{ $dateFrom }} to {{ $dateTo }}</p>
                <p class="text-xs text-gray-400 mt-1">Generated: {{ date('d M Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Statement table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-2 text-left w-24">Date</th>
                    <th class="px-4 py-2 text-left w-20">Type</th>
                    <th class="px-4 py-2 text-left">Reference</th>
                    <th class="px-4 py-2 text-left">Description</th>
                    <th class="px-4 py-2 text-right w-28">Debit (AED)</th>
                    <th class="px-4 py-2 text-right w-28">Credit (AED)</th>
                    <th class="px-4 py-2 text-right w-32">Balance (AED)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening balance row -->
                <tr class="bg-blue-50 border-b border-blue-100 font-medium">
                    <td class="px-4 py-2 text-xs text-gray-500">{{ $dateFrom }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500">—</td>
                    <td class="px-4 py-2 text-xs text-gray-500" colspan="2">Opening Balance</td>
                    <td class="px-4 py-2 text-right text-xs"></td>
                    <td class="px-4 py-2 text-right text-xs"></td>
                    <td class="px-4 py-2 text-right text-xs font-semibold
                        {{ $openingBalance >= 0 ? 'text-gray-700' : 'text-green-700' }}">
                        {{ number_format(abs($openingBalance), 2) }}
                        {{ $openingBalance < 0 ? 'Cr' : ($openingBalance > 0 ? 'Dr' : '') }}
                    </td>
                </tr>

                @forelse($transactions as $txn)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 text-xs text-gray-500">{{ $txn->date }}</td>
                    <td class="px-4 py-2">
                        <span class="text-xs px-1.5 py-0.5 rounded
                            {{ $txn->type === 'Invoice' ? 'bg-blue-50 text-blue-700' : 'bg-green-50 text-green-700' }}">
                            {{ $txn->type }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-xs font-mono text-gray-700">
                        @if($txn->type === 'Invoice')
                            <a href="/invoices/{{ DB::table('invoices')->where('invoice_number', $txn->reference)->value('id') }}"
                               class="hover:underline text-blue-600">{{ $txn->reference }}</a>
                        @else
                            {{ $txn->reference }}
                        @endif
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600">{{ $txn->description }}</td>
                    <td class="px-4 py-2 text-right text-xs {{ $txn->debit > 0 ? 'text-gray-800' : 'text-gray-300' }}">
                        {{ $txn->debit > 0 ? number_format($txn->debit, 2) : '—' }}
                    </td>
                    <td class="px-4 py-2 text-right text-xs {{ $txn->credit > 0 ? 'text-green-700' : 'text-gray-300' }}">
                        {{ $txn->credit > 0 ? number_format($txn->credit, 2) : '—' }}
                    </td>
                    <td class="px-4 py-2 text-right text-xs font-medium
                        {{ $txn->balance >= 0 ? 'text-gray-700' : 'text-green-700' }}">
                        {{ number_format(abs($txn->balance), 2) }}
                        {{ $txn->balance < 0 ? 'Cr' : ($txn->balance > 0 ? 'Dr' : '') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-400">
                        No transactions in this period.
                    </td>
                </tr>
                @endforelse

                <!-- Closing balance -->
                <tr class="bg-gray-50 border-t-2 border-gray-300 font-semibold">
                    <td class="px-4 py-2 text-xs text-gray-500" colspan="4">Closing Balance</td>
                    <td class="px-4 py-2 text-right text-xs">
                        {{ number_format($transactions->sum('debit'), 2) }}
                    </td>
                    <td class="px-4 py-2 text-right text-xs text-green-700">
                        {{ number_format($transactions->sum('credit'), 2) }}
                    </td>
                    <td class="px-4 py-2 text-right text-sm font-bold
                        {{ $closingBalance >= 0 ? 'text-gray-800' : 'text-green-700' }}">
                        AED {{ number_format(abs($closingBalance), 2) }}
                        {{ $closingBalance < 0 ? 'Cr' : ($closingBalance > 0 ? 'Dr' : '') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
@endsection
