@extends('layouts.app')
@section('title', 'New Bank Reconciliation')
@section('content')

<div class="max-w-2xl mx-auto">
    <div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
        <a href="/reconciliation" class="hover:text-gray-700">Bank Reconciliation</a>
        <span>›</span>
        <span class="text-gray-700">New Reconciliation</span>
    </div>

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="/reconciliation" enctype="multipart/form-data"
          class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-100">

        @csrf

        <!-- Account & Period -->
        <div class="px-6 py-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">1. Account & Period</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="text-xs text-gray-500 block mb-1">Bank Account <span class="text-red-500">*</span></label>
                    <select name="account_id" required
                        class="w-full border border-gray-200 rounded px-3 py-2 text-sm focus:outline-none focus:border-green-400">
                        <option value="">— Select bank account —</option>
                        @foreach($bankAccounts as $a)
                        <option value="{{ $a->id }}" {{ old('account_id') == $a->id ? 'selected' : '' }}>
                            {{ $a->name }} ({{ $a->account_code ?? 'No code' }})
                        </option>
                        @endforeach
                    </select>
                    @if($bankAccounts->isEmpty())
                    <p class="text-xs text-orange-500 mt-1">No bank/cash accounts found. Add an account with the "is_bank" flag in Chart of Accounts.</p>
                    @endif
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Period From <span class="text-red-500">*</span></label>
                    <input type="date" name="period_from" value="{{ old('period_from', date('Y-m-01')) }}" required
                        class="w-full border border-gray-200 rounded px-3 py-2 text-sm focus:outline-none focus:border-green-400">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Period To <span class="text-red-500">*</span></label>
                    <input type="date" name="period_to" value="{{ old('period_to', date('Y-m-t')) }}" required
                        class="w-full border border-gray-200 rounded px-3 py-2 text-sm focus:outline-none focus:border-green-400">
                </div>
            </div>
        </div>

        <!-- Balances -->
        <div class="px-6 py-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">2. Statement Balances</h3>
            <p class="text-xs text-gray-400 mb-3">Enter the opening and closing balances as shown on your bank statement.</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Opening Balance (AED)</label>
                    <input type="number" name="opening_balance" step="0.01" value="{{ old('opening_balance', '0.00') }}"
                        class="w-full border border-gray-200 rounded px-3 py-2 text-sm focus:outline-none focus:border-green-400">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Closing Balance (AED) <span class="text-red-500">*</span></label>
                    <input type="number" name="closing_balance_statement" step="0.01" value="{{ old('closing_balance_statement', '') }}" required
                        class="w-full border border-gray-200 rounded px-3 py-2 text-sm focus:outline-none focus:border-green-400">
                </div>
            </div>
        </div>

        <!-- CSV Upload -->
        <div class="px-6 py-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">3. Upload Bank Statement (CSV)</h3>

            <div class="mb-4 bg-blue-50 border border-blue-100 rounded px-4 py-3 text-xs text-blue-700 leading-relaxed">
                <strong>Accepted CSV formats:</strong><br>
                Most UAE bank exports (RAKBANK, Emirates NBD, FAB, ADCB) are supported automatically.<br>
                Required columns: <strong>Date</strong>, <strong>Debit</strong> (money out), <strong>Credit</strong> (money in).<br>
                Optional: Description, Reference, Balance (ignored).<br><br>
                <strong>Example:</strong><br>
                <code class="bg-blue-100 px-1 py-0.5 rounded">Date,Description,Reference,Debit,Credit</code><br>
                <code class="bg-blue-100 px-1 py-0.5 rounded">01/11/2026,Invoice payment - ABC Corp,INV-001,,5000.00</code><br>
                <code class="bg-blue-100 px-1 py-0.5 rounded">05/11/2026,Bank charges,FEE,,25.00</code>
            </div>

            <label class="text-xs text-gray-500 block mb-1">CSV File <span class="text-red-500">*</span></label>
            <input type="file" name="csv_file" accept=".csv,.txt" required
                class="w-full border border-gray-200 rounded px-3 py-2 text-sm focus:outline-none focus:border-green-400">
        </div>

        <!-- Notes -->
        <div class="px-6 py-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">4. Notes (optional)</h3>
            <textarea name="notes" rows="2" placeholder="e.g. November 2026 — RAKBANK current account"
                class="w-full border border-gray-200 rounded px-3 py-2 text-sm focus:outline-none focus:border-green-400">{{ old('notes') }}</textarea>
        </div>

        <!-- Actions -->
        <div class="px-6 py-4 bg-gray-50 flex items-center justify-between">
            <a href="/reconciliation" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            <button type="submit" class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
                Import & Auto-Match →
            </button>
        </div>
    </form>
</div>

@endsection
