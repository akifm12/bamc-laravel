@extends('layouts.app')

@section('title', 'Aged Payables')

@section('content')

<form method="GET" action="/reports/aged-ap" class="bg-white rounded-lg border border-gray-200 p-4 mb-4 flex gap-3 items-end">
    <div>
        <label class="text-xs text-gray-500 block mb-1">As of Date</label>
        <input type="date" name="as_of" value="{{ $asOf }}"
            class="border border-gray-200 rounded px-3 py-1.5 text-sm">
    </div>
    <button type="submit" class="bg-gray-700 text-white text-sm px-4 py-1.5 rounded hover:bg-gray-800">Run Report</button>
    <a href="/reports" class="text-sm text-gray-400 px-2 py-1.5">← Reports</a>
</form>

<!-- Summary buckets -->
<div class="grid grid-cols-5 gap-3 mb-4">
    @foreach($buckets as $key => $label)
    <div class="bg-white rounded-lg border border-gray-200 p-3">
        <p class="text-xs text-gray-400">{{ $label }}</p>
        <p class="text-lg font-bold {{ $summary[$key] > 0 ? 'text-red-600' : 'text-gray-400' }}">
            AED {{ number_format($summary[$key], 2) }}
        </p>
    </div>
    @endforeach
</div>

<div class="bg-white rounded-lg border border-gray-200 p-3 mb-4 flex justify-between items-center">
    <span class="font-semibold text-gray-700">Total Outstanding</span>
    <span class="font-bold text-xl text-red-600">AED {{ number_format($totalDue, 2) }}</span>
</div>

<!-- By Vendor -->
@foreach($byVendor as $vendorName => $billList)
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-3">
    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex justify-between">
        <span class="font-semibold text-gray-700 text-sm">{{ $vendorName }}</span>
        <span class="font-semibold text-sm text-red-600">AED {{ number_format($billList->sum('amount_due'), 2) }}</span>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                <th class="px-4 py-2 text-left">Bill</th>
                <th class="px-4 py-2 text-left">Bill Date</th>
                <th class="px-4 py-2 text-left">Due Date</th>
                <th class="px-4 py-2 text-right">Total</th>
                <th class="px-4 py-2 text-right">Paid</th>
                <th class="px-4 py-2 text-right">Due</th>
                <th class="px-4 py-2 text-center">Age</th>
            </tr>
        </thead>
        <tbody>
            @foreach($billList as $bill)
            @php
                $bucketColor = match($bill->bucket) {
                    'current' => 'bg-green-100 text-green-700',
                    '1_30'    => 'bg-yellow-100 text-yellow-700',
                    '31_60'   => 'bg-orange-100 text-orange-700',
                    '61_90'   => 'bg-red-100 text-red-600',
                    'over_90' => 'bg-red-200 text-red-800',
                    default   => 'bg-gray-100 text-gray-500',
                };
            @endphp
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 font-mono text-xs">{{ $bill->bill_number }}</td>
                <td class="px-4 py-2 text-gray-600">{{ $bill->bill_date }}</td>
                <td class="px-4 py-2 text-gray-600">{{ $bill->due_date ?? '—' }}</td>
                <td class="px-4 py-2 text-right">{{ number_format($bill->total_amount, 2) }}</td>
                <td class="px-4 py-2 text-right text-green-600">{{ number_format($bill->amount_paid, 2) }}</td>
                <td class="px-4 py-2 text-right font-semibold text-red-600">{{ number_format($bill->amount_due, 2) }}</td>
                <td class="px-4 py-2 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $bucketColor }}">
                        {{ $buckets[$bill->bucket] }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endforeach

@if($byVendor->isEmpty())
<div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-400">
    No outstanding payables as of {{ $asOf }}.
</div>
@endif

@endsection