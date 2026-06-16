@extends('layouts.app')

@section('title', 'Invoice - ' . $invoice->invoice_number)

@section('content')

<div class="max-w-4xl">

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $invoice->invoice_number }}</h2>
                <p class="text-gray-500 text-sm mt-1">{{ $invoice->customer_name }}</p>
                @if($invoice->po_number)
                    <p class="text-xs text-gray-400 mt-1">PO: {{ $invoice->po_number }}</p>
                @endif
            </div>
            <div class="text-right flex flex-col items-end gap-2">
                @php
                    $status = strtolower($invoice->status ?? 'draft');
                    $statusColor = match($status) {
                        'approved' => 'bg-green-100 text-green-700',
                        'draft'    => 'bg-yellow-100 text-yellow-700',
                        'void'     => 'bg-gray-100 text-gray-500',
                        default    => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <span class="text-xs px-3 py-1 rounded-full {{ $statusColor }}">{{ strtoupper($status) }}</span>
                <p class="text-sm text-gray-500">Date: {{ $invoice->invoice_date }}</p>
                <p class="text-sm text-gray-500">Due: {{ $invoice->due_date ?? '-' }}</p>

                <div class="flex gap-2 mt-2">
                    <a href="/invoices/{{ $invoice->id }}/pdf"
                        class="text-xs bg-gray-700 text-white px-3 py-1.5 rounded hover:bg-gray-800">
                        📑 Download PDF
                    </a>
               		@if($status === 'approved' || $status === 'partial')
    					<form method="POST" action="/invoices/{{ $invoice->id }}/send-email">
       				  		@csrf
						<button style="background-color:#3b82f6; color:white; font-size:0.75rem; padding:0.375rem 0.75rem; border-radius:0.25rem; border:none; cursor:pointer;">
    						📧 Send Email
						</button>
    					</form>
					@endif
                    @if($status === 'draft')
                        <a href="/invoices/{{ $invoice->id }}/edit"
                            class="text-xs border border-gray-200 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50">
                            ✏️ Edit
                        </a>
                        <form method="POST" action="/invoices/{{ $invoice->id }}/approve">
                            @csrf
                            <button class="text-xs bg-green-700 text-white px-3 py-1.5 rounded hover:bg-green-800">
                                ✅ Approve
                            </button>
                        </form>
                        @if(auth()->user()->is_super_admin)
                        <form method="POST" action="/invoices/{{ $invoice->id }}/void">
                            @csrf
                            <button onclick="return confirm('Void this invoice?')"
                                class="text-xs bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700">
                                🔴 Void
                            </button>
                        </form>
                        @endif
                    @endif
                    @if($status === 'approved' || $status === 'partial')
                        <a href="/invoices/{{ $invoice->id }}/payment"
                            class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700">
                            💰 Record Payment
                        </a>
                        @if(auth()->user()->is_super_admin)
                        <form method="POST" action="/invoices/{{ $invoice->id }}/void">
                            @csrf
                            <button onclick="return confirm('Void this invoice?')"
                                class="text-xs bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700">
                                🔴 Void
                            </button>
                        </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Amounts -->
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Subtotal</p>
            <p class="text-lg font-bold text-gray-800">AED {{ number_format($invoice->subtotal, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">VAT (5%)</p>
            <p class="text-lg font-bold text-gray-800">AED {{ number_format($invoice->total_vat_amount, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Total Amount</p>
            <p class="text-lg font-bold text-green-700">AED {{ number_format($invoice->total_amount, 2) }}</p>
        </div>
    </div>

    <!-- Lines -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">Invoice Lines</span>
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
                    <td class="px-4 py-2 text-gray-500 text-xs">{{ $line->account_name ?? '-' }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($line->quantity, 2) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($line->unit_price, 2) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($line->vat_amount, 2) }}</td>
                    <td class="px-4 py-2 text-right font-medium">{{ number_format($line->line_amount + $line->vat_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t border-gray-200">
                    <td colspan="6" class="px-4 py-2 text-right text-xs text-gray-500">Subtotal</td>
                    <td class="px-4 py-2 text-right font-semibold">AED {{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                <tr class="bg-gray-50">
                    <td colspan="6" class="px-4 py-2 text-right text-xs text-gray-500">VAT</td>
                    <td class="px-4 py-2 text-right font-semibold">AED {{ number_format($invoice->total_vat_amount, 2) }}</td>
                </tr>
                <tr class="bg-gray-50 font-bold">
                    <td colspan="6" class="px-4 py-2 text-right">TOTAL</td>
                    <td class="px-4 py-2 text-right text-green-700">AED {{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($invoice->notes)
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-4">
        <p class="text-xs text-gray-400 mb-1">Notes</p>
        <p class="text-sm text-gray-600">{{ $invoice->notes }}</p>
    </div>
    @endif

    <a href="/invoices" class="text-sm text-gray-500 hover:text-gray-700">← Back to Invoices</a>

</div>

@endsection