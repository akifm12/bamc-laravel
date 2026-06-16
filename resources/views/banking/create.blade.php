@extends('layouts.app')

@section('title', 'Add Bank Account')

@section('content')

<div class="max-w-2xl">

<form method="POST" action="/banking" class="space-y-4">
    @csrf

    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Bank Account Details</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Account Name *</label>
                <input type="text" name="account_name" required placeholder="e.g. Current Account - RAK Bank"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Bank Name *</label>
                <input type="text" name="bank_name" required placeholder="e.g. RAK Bank"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Branch</label>
                <input type="text" name="branch"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Account Number</label>
                <input type="text" name="account_number"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">IBAN</label>
                <input type="text" name="iban" placeholder="AE..."
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">SWIFT Code</label>
                <input type="text" name="swift_code"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Currency</label>
                <select name="currency_code" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="AED" selected>AED</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="GBP">GBP</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">GL Account *</label>
                <select name="gl_account_id" required class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">- Select -</option>
                    @foreach($glAccounts as $a)
                        <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Opening Balance (AED)</label>
                <input type="number" name="opening_balance" value="0" step="0.01"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Opening Date</label>
                <input type="date" name="opening_date" value="{{ date('Y-m-d') }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="flex items-center gap-2 mt-2">
                <input type="checkbox" name="is_default" id="is_default" class="rounded">
                <label for="is_default" class="text-sm text-gray-600">Set as default bank account</label>
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
            Create Bank Account
        </button>
        <a href="/banking" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
    </div>
</form>
</div>

@endsection