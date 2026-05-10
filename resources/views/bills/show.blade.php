@extends('layouts.app')

@section('title', 'Bill — ' . $bill->bill_number)

@section('content')

<div class="max-w-4xl">

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $bill->bill_number }}</h2>
                <p class="text-gray-500 text-sm mt-1">{{ $bill->vendor_name }}</p>
                @if($bill->vendor_ref)
                    <p class="text-xs text-gray-400 mt-1">Vendor Ref: {{ $bill->vendor_ref }}</p>
                @endif
            </div>
            <div class="text-right flex flex-col items-end gap-2">
                @php
                    $status = strtolower($bill->status ?? 'draft');
                    $statusColor = match($status) {
                        'approved' => 'bg-green-100 text-green-700',
                        'draft'    => 'bg-yellow-100 text-yellow-700',
                        'void'     => 'bg-gray-100 text-gray-500',
                        default    => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <span class="text-xs px-3 py-1 rounded-full {{ $statusColor }}">{{ strtoupper($status) }}</span>
                <p class="text-sm text-gray-500">Date: {{ $bill->bill_date }}</p>
                <p class="text-sm text-gray-500">Due: {{ $bill->due_date ?? '—' }}</p>

                <div class="flex gap-2 mt-2">
                
					@if($status === 'draft')
                        <form method="POST" action="/bills/{{ $bill->id }}/approve">
                            @csrf
                            <button class="text-xs bg-green-700 text-white px-3 py-1.5 rounded hover:bg-green-800">
                                ✅ Approve
                            </button>
                        </form>
                        <form method="POST" action="/bills/{{ $bill->id }}/void">
                            @csrf
                            <button onclick="return confirm('Void this bill?')"
                                class="text-xs bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700">
                                🔴 Void
                            </button>
                        </form>
                    @endif
                    @if($status === 'approved' || $status === 'partial')
                        <a href="/bills/{{ $bill->id }}/payment"
                            class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700">
                            💰 Record Payment
                        </a>
                    @endif
                
                </div>
            </div>
        </div>
    </div>

    <!-- Amounts -->
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Subtotal</p>
            <p class="text-lg font-bold text-gray-800">AED {{ number_format($bill->subtotal, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">VAT</p>
            <p class="text-lg font-bold text-gray-800">AED {{ number_format($bill->total_vat_amount, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Total Amount</p>
            <p class="text-lg font-bold text-gray-800">AED {{ number_format($bill->total_amount, 2) }}</p>
        </div>
    </div>

    <!-- Lines -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">Bill Lines</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                    <th class="px-4 py-2 text-left">#</th>
                    <th class="px-4 py-2 text-left">Description</th>
                    <th class="px-4 py-2 text-left">Account</th>
                    <th class="px-4 py-2 text-right">Qty</th>
                    <th class="px-4 py-2 text-right">Unit Price</th>
                    <th class="px-4 py-2 text-right">VAT</th>
                    <th class="px-4 py-2 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $line)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 text-gray-400 text-xs">{{ $line->line_number }}</td>
                    <td class="px-4 py-2 text-gray-800">{{ $line->description }}</td>
                    <td class="px-4 py-2 text-gray-500 text-xs">{{ $line->account_name ?? '—' }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($line->quantity, 2) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($line->unit_price, 2) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($line->vat_amount, 2) }}</td>
                    <td class="px-4 py-2 text-right font-medium">{{ number_format($line->total_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t border-gray-200">
                    <td colspan="6" class="px-4 py-2 text-right text-xs text-gray-500">Subtotal</td>
                    <td class="px-4 py-2 text-right font-semibold">AED {{ number_format($bill->subtotal, 2) }}</td>
                </tr>
                <tr class="bg-gray-50">
                    <td colspan="6" class="px-4 py-2 text-right text-xs text-gray-500">VAT</td>
                    <td class="px-4 py-2 text-right font-semibold">AED {{ number_format($bill->total_vat_amount, 2) }}</td>
                </tr>
                <tr class="bg-gray-50 font-bold">
                    <td colspan="6" class="px-4 py-2 text-right">TOTAL</td>
                    <td class="px-4 py-2 text-right text-gray-800">AED {{ number_format($bill->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($bill->notes)
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-4">
        <p class="text-xs text-gray-400 mb-1">Notes</p>
        <p class="text-sm text-gray-600">{{ $bill->notes }}</p>
    </div>
    @endif

    <a href="/bills" class="text-sm text-gray-500 hover:text-gray-700">← Back to Bills</a>

</div>

@endsection