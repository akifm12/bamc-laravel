@extends('layouts.app')

@section('title', 'Record Payment - ' . $invoice->invoice_number)

@section('content')

<div class="max-w-lg">

    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <h2 class="font-semibold text-gray-700 mb-1">Record Payment</h2>
        <p class="text-sm text-gray-500">{{ $invoice->invoice_number }} - {{ $invoice->customer_name }}</p>
        <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
            <div>
                <p class="text-xs text-gray-400">Invoice Total</p>
                <p class="font-semibold">AED {{ number_format($invoice->total_amount, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Paid So Far</p>
                <p class="font-semibold text-green-600">AED {{ number_format($invoice->amount_paid, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Amount Due</p>
                <p class="font-semibold text-red-600">AED {{ number_format($invoice->amount_due, 2) }}</p>
            </div>
        </div>
    </div>

    <form method="POST" action="/invoices/{{ $invoice->id }}/payment"
        class="bg-white rounded-lg border border-gray-200 p-5 space-y-4">
        @csrf

        <div>
            <label class="text-xs text-gray-500 block mb-1">Payment Date *</label>
            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
        </div>

        <div>
            <label class="text-xs text-gray-500 block mb-1">Amount (AED) *</label>
            <input type="number" name="amount" value="{{ number_format($invoice->amount_due, 2, '.', '') }}"
                step="0.01" min="0.01" required
                class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
        </div>

        <div>
            <label class="text-xs text-gray-500 block mb-2">Received Into *</label>

            @if($bankAccounts->count() > 0)
            <p class="text-xs text-gray-400 mb-1">Bank Accounts</p>
            @foreach($bankAccounts as $bank)
            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded mb-2 cursor-pointer hover:bg-gray-50">
                <input type="radio" name="gl_account_id" value="{{ $bank->gl_account_id }}" required>
                <div>
                    <p class="text-sm font-medium text-gray-700">{{ $bank->account_name }}</p>
                    <p class="text-xs text-gray-400">{{ $bank->bank_name }} - {{ $bank->account_number }}</p>
                </div>
            </label>
            @endforeach
            @endif

            @if($cashAccounts->count() > 0)
            <p class="text-xs text-gray-400 mb-1 mt-2">Cash Accounts</p>
            @foreach($cashAccounts as $cash)
            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded mb-2 cursor-pointer hover:bg-gray-50">
                <input type="radio" name="gl_account_id" value="{{ $cash->id }}" required>
                <div>
                    <p class="text-sm font-medium text-gray-700">{{ $cash->name }}</p>
                    <p class="text-xs text-gray-400">{{ $cash->code }}</p>
                </div>
            </label>
            @endforeach
            @endif
        </div>

        <div>
            <label class="text-xs text-gray-500 block mb-1">Reference / Cheque No.</label>
            <input type="text" name="reference" placeholder="Optional"
                class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
                ✅ Record Payment
            </button>
            <a href="/invoices/{{ $invoice->id }}"
                class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</div>

@endsection