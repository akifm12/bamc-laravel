@extends('layouts.app')
@section('title', 'Edit Account')
@section('content')
<div class="max-w-2xl">

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded text-sm">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded text-sm">
        {{ session('success') }}
    </div>
    @endif

    <form method="POST" action="/accounts/{{ $account->id }}/update">
        @csrf

        <!-- Core Info -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-4">Account Details</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Account Code *</label>
                    <input type="text" name="code" value="{{ old('code', $account->code) }}" required
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm font-mono"
                        placeholder="e.g. 1001">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Account Type *</label>
                    <select name="account_type" required onchange="setNormalBalance(this.value)"
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                        <option value="">— Select —</option>
                        <option value="ASSET"     {{ old('account_type', $account->account_type) == 'ASSET'     ? 'selected' : '' }}>Asset</option>
                        <option value="LIABILITY" {{ old('account_type', $account->account_type) == 'LIABILITY' ? 'selected' : '' }}>Liability</option>
                        <option value="EQUITY"    {{ old('account_type', $account->account_type) == 'EQUITY'    ? 'selected' : '' }}>Equity</option>
                        <option value="REVENUE"   {{ old('account_type', $account->account_type) == 'REVENUE'   ? 'selected' : '' }}>Revenue</option>
                        <option value="EXPENSE"   {{ old('account_type', $account->account_type) == 'EXPENSE'   ? 'selected' : '' }}>Expense</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="text-xs text-gray-500 block mb-1">Account Name *</label>
                    <input type="text" name="name" value="{{ old('name', $account->name) }}" required
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm"
                        placeholder="e.g. Cash at Bank">
                </div>
                <div class="col-span-2">
                    <label class="text-xs text-gray-500 block mb-1">Arabic Name</label>
                    <input type="text" name="name_arabic" value="{{ old('name_arabic', $account->name_arabic) }}"
                        dir="rtl" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Normal Balance *</label>
                    <select name="normal_balance" id="normal_balance" required
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                        <option value="DEBIT"  {{ old('normal_balance', $account->normal_balance) == 'DEBIT'  ? 'selected' : '' }}>Debit</option>
                        <option value="CREDIT" {{ old('normal_balance', $account->normal_balance) == 'CREDIT' ? 'selected' : '' }}>Credit</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Parent Account</label>
                    <select name="parent_id" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                        <option value="">— None (Top Level) —</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}"
                                {{ old('parent_id', $account->parent_id) == $acc->id ? 'selected' : '' }}>
                                {{ $acc->code }} — {{ $acc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Currency</label>
                    <input type="text" name="currency_code" value="{{ old('currency_code', $account->currency_code ?? 'AED') }}"
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm" maxlength="3">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Opening Balance</label>
                    <input type="number" name="opening_balance" value="{{ old('opening_balance', $account->opening_balance) }}"
                        step="0.01" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Opening Balance Date</label>
                    <input type="date" name="opening_balance_date" value="{{ old('opening_balance_date', $account->opening_balance_date) }}"
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="text-xs text-gray-500 block mb-1">Description</label>
                    <textarea name="description" rows="2"
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">{{ old('description', $account->description) }}</textarea>
                </div>
                <div class="col-span-2">
                    <label class="text-xs text-gray-500 block mb-1">Notes</label>
                    <textarea name="notes" rows="2"
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">{{ old('notes', $account->notes) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Flags -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 mb-6">
            <h3 class="font-semibold text-gray-700 text-sm mb-4">Account Flags</h3>
            <div class="grid grid-cols-3 gap-3">
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1"
                        {{ old('is_active', $account->is_active) ? 'checked' : '' }} class="accent-blue-600">
                    Active
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_control" value="1"
                        {{ old('is_control', $account->is_control) ? 'checked' : '' }} class="accent-blue-600">
                    Control Account
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_bank" value="1"
                        {{ old('is_bank', $account->is_bank) ? 'checked' : '' }} class="accent-blue-600">
                    Bank Account
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_cash" value="1"
                        {{ old('is_cash', $account->is_cash) ? 'checked' : '' }} class="accent-blue-600">
                    Cash Account
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_receivable" value="1"
                        {{ old('is_receivable', $account->is_receivable) ? 'checked' : '' }} class="accent-blue-600">
                    Receivable
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_payable" value="1"
                        {{ old('is_payable', $account->is_payable) ? 'checked' : '' }} class="accent-blue-600">
                    Payable
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_vat_account" value="1"
                        {{ old('is_vat_account', $account->is_vat_account) ? 'checked' : '' }} class="accent-blue-600">
                    VAT Account
                </label>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-blue-600 text-white text-sm px-5 py-2 rounded hover:bg-blue-700">
                Update Account
            </button>
            <a href="/accounts" class="bg-gray-100 text-gray-600 text-sm px-5 py-2 rounded hover:bg-gray-200">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function setNormalBalance(type) {
    const nb = document.getElementById('normal_balance');
    if (['ASSET', 'EXPENSE'].includes(type)) nb.value = 'DEBIT';
    else if (['LIABILITY', 'EQUITY', 'REVENUE'].includes(type)) nb.value = 'CREDIT';
}
</script>
@endsection@extends('layouts.app')
@section('title', 'New Account')
@section('content')
<div class="max-w-2xl">

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded text-sm">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <form method="POST" action="/accounts">
        @csrf

        <!-- Core Info -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-4">Account Details</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Account Code *</label>
                    <input type="text" name="code" value="{{ old('code') }}" required
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm font-mono"
                        placeholder="e.g. 1001">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Account Type *</label>
                    <select name="account_type" required onchange="setNormalBalance(this.value)"
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                        <option value="">— Select —</option>
                        <option value="ASSET"     {{ old('account_type') == 'ASSET'     ? 'selected' : '' }}>Asset</option>
                        <option value="LIABILITY" {{ old('account_type') == 'LIABILITY' ? 'selected' : '' }}>Liability</option>
                        <option value="EQUITY"    {{ old('account_type') == 'EQUITY'    ? 'selected' : '' }}>Equity</option>
                        <option value="REVENUE"   {{ old('account_type') == 'REVENUE'   ? 'selected' : '' }}>Revenue</option>
                        <option value="EXPENSE"   {{ old('account_type') == 'EXPENSE'   ? 'selected' : '' }}>Expense</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="text-xs text-gray-500 block mb-1">Account Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm"
                        placeholder="e.g. Cash at Bank">
                </div>
                <div class="col-span-2">
                    <label class="text-xs text-gray-500 block mb-1">Arabic Name</label>
                    <input type="text" name="name_arabic" value="{{ old('name_arabic') }}"
                        dir="rtl" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Normal Balance *</label>
                    <select name="normal_balance" id="normal_balance" required
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                        <option value="DEBIT"  {{ old('normal_balance') == 'DEBIT'  ? 'selected' : '' }}>Debit</option>
                        <option value="CREDIT" {{ old('normal_balance') == 'CREDIT' ? 'selected' : '' }}>Credit</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Parent Account</label>
                    <select name="parent_id" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                        <option value="">— None (Top Level) —</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}"
                                {{ (old('parent_id', $parentId) == $acc->id) ? 'selected' : '' }}>
                                {{ $acc->code }} — {{ $acc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Currency</label>
                    <input type="text" name="currency_code" value="{{ old('currency_code', 'AED') }}"
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm" maxlength="3">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Opening Balance</label>
                    <input type="number" name="opening_balance" value="{{ old('opening_balance') }}"
                        step="0.01" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Opening Balance Date</label>
                    <input type="date" name="opening_balance_date" value="{{ old('opening_balance_date') }}"
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="text-xs text-gray-500 block mb-1">Description</label>
                    <textarea name="description" rows="2"
                        class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Flags -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 mb-6">
            <h3 class="font-semibold text-gray-700 text-sm mb-4">Account Flags</h3>
            <div class="grid grid-cols-3 gap-3">
                @foreach(['is_control' => 'Control Account', 'is_bank' => 'Bank Account', 'is_cash' => 'Cash Account', 'is_receivable' => 'Receivable', 'is_payable' => 'Payable', 'is_vat_account' => 'VAT Account'] as $flag => $label)
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="{{ $flag }}" value="1"
                        {{ old($flag) ? 'checked' : '' }} class="accent-blue-600">
                    {{ $label }}
                </label>
                @endforeach
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-blue-600 text-white text-sm px-5 py-2 rounded hover:bg-blue-700">
                Create Account
            </button>
            <a href="/accounts" class="bg-gray-100 text-gray-600 text-sm px-5 py-2 rounded hover:bg-gray-200">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function setNormalBalance(type) {
    const nb = document.getElementById('normal_balance');
    if (['ASSET', 'EXPENSE'].includes(type)) nb.value = 'DEBIT';
    else if (['LIABILITY', 'EQUITY', 'REVENUE'].includes(type)) nb.value = 'CREDIT';
}
</script>
@endsection