@extends('layouts.app')

@section('title', 'Add Fixed Asset')

@section('content')

<div class="max-w-3xl">

<form method="POST" action="/assets" class="space-y-4">
    @csrf

    <!-- Basic Info -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Asset Details</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Asset Name *</label>
                <input type="text" name="name" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Category</label>
                <select name="category_id" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">- Select -</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Serial Number</label>
                <input type="text" name="serial_number"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Location</label>
                <input type="text" name="location"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Purchase Date *</label>
                <input type="date" name="purchase_date" value="{{ date('Y-m-d') }}" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">In Service Date</label>
                <input type="date" name="in_service_date" value="{{ date('Y-m-d') }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Supplier</label>
                <select name="supplier_id" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">- Select -</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}">{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Purchase Invoice Ref</label>
                <input type="text" name="purchase_invoice_ref"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="col-span-2">
                <label class="text-xs text-gray-500 block mb-1">Description</label>
                <textarea name="description" rows="2"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm"></textarea>
            </div>
        </div>
    </div>

    <!-- Depreciation -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Valuation & Depreciation</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Purchase Cost (AED) *</label>
                <input type="number" name="purchase_cost" step="0.01" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Residual Value (AED)</label>
                <input type="number" name="residual_value" value="0" step="0.01"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Depreciation Method</label>
                <select name="depreciation_method" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="straight_line">Straight Line</option>
                    <option value="declining_balance">Declining Balance</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Useful Life (Years)</label>
                <input type="number" name="useful_life_years" value="5" step="0.5"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <!-- GL Accounts -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">GL Accounts</h3>
        <div class="grid grid-cols-2 gap-4">
            @foreach([
                ['asset_account_id',        'Asset Account',                 'ASSET'],
                ['accum_depr_account_id',   'Accumulated Depreciation',      'ASSET'],
                ['depr_expense_account_id', 'Depreciation Expense Account',  'EXPENSE'],
            ] as [$field, $label, $type])
            <div>
                <label class="text-xs text-gray-500 block mb-1">{{ $label }}</label>
                <select name="{{ $field }}" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">- Select -</option>
                    @foreach($accounts->get($type, collect()) as $a)
                        <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Insurance -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Insurance</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Policy Number</label>
                <input type="text" name="insurance_policy_no"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Policy Expiry</label>
                <input type="date" name="insurance_expiry"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
            Create Asset
        </button>
        <a href="/assets" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
    </div>

</form>
</div>

@endsection