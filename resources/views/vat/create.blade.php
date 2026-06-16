@extends('layouts.app')

@section('title', 'New VAT Return')

@section('content')

<div class="max-w-3xl">

<!-- Period selector -->
<form method="GET" action="/vat/create" class="bg-white rounded-lg border border-gray-200 p-4 mb-4 flex gap-3 items-end">
    <div>
        <label class="text-xs text-gray-500 block mb-1">Period From</label>
        <input type="date" name="date_from" value="{{ $dateFrom }}"
            class="border border-gray-200 rounded px-3 py-1.5 text-sm">
    </div>
    <div>
        <label class="text-xs text-gray-500 block mb-1">Period To</label>
        <input type="date" name="date_to" value="{{ $dateTo }}"
            class="border border-gray-200 rounded px-3 py-1.5 text-sm">
    </div>
    <button type="submit" class="bg-gray-700 text-white text-sm px-4 py-1.5 rounded hover:bg-gray-800">
        Calculate
    </button>
</form>

<form method="POST" action="/vat" class="space-y-4">
    @csrf

    <input type="hidden" name="period_from" value="{{ $dateFrom }}">
    <input type="hidden" name="period_to" value="{{ $dateTo }}">

    <!-- Period & Due Date -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Return Details</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Due Date</label>
                <input type="date" name="due_date"
                    value="{{ date('Y-m-d', strtotime($dateTo . ' +28 days')) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Notes</label>
                <input type="text" name="notes" placeholder="Optional"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <!-- Output Tax -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-1">VAT on Sales (Output Tax)</h3>
        <p class="text-xs text-gray-400 mb-4">Auto-calculated from approved invoices in the period. You can adjust if needed.</p>

        <div class="space-y-3">
            <div class="grid grid-cols-3 gap-4 items-center">
                <label class="text-sm text-gray-600 col-span-1">Box 1 - Standard Rated Sales</label>
                <input type="number" name="box1_standard_rated_sales"
                    value="{{ number_format($standardSales, 2, '.', '') }}"
                    step="0.01" class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
                <input type="number" name="box1_vat_amount"
                    value="{{ number_format($outputVatAmount, 2, '.', '') }}"
                    step="0.01" placeholder="VAT Amount"
                    class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
            </div>
            <div class="grid grid-cols-3 gap-4 items-center">
                <label class="text-sm text-gray-600">Box 2 - Zero Rated Sales</label>
                <input type="number" name="box2_zero_rated_sales" value="0"
                    step="0.01" class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
                <span class="text-xs text-gray-400">0%</span>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center">
                <label class="text-sm text-gray-600">Box 3 - Exempt Sales</label>
                <input type="number" name="box3_exempt_sales" value="0"
                    step="0.01" class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
                <span class="text-xs text-gray-400">Exempt</span>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center">
                <label class="text-sm text-gray-600">Box 4 - Goods Imported</label>
                <input type="number" name="box4_goods_imported" value="0"
                    step="0.01" class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
                <input type="number" name="box4_vat_amount" value="0"
                    step="0.01" placeholder="VAT Amount"
                    class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
            </div>
            <div class="grid grid-cols-3 gap-4 items-center">
                <label class="text-sm text-gray-600">Box 5 - Reverse Charge</label>
                <input type="number" name="box5_reverse_charge" value="0"
                    step="0.01" class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
                <input type="number" name="box5_vat_amount" value="0"
                    step="0.01" placeholder="VAT Amount"
                    class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
            </div>
            <div class="grid grid-cols-3 gap-4 items-center border-t border-gray-100 pt-3">
                <label class="text-sm font-semibold text-gray-700">Box 6 - Total Output Tax</label>
                <span></span>
                <input type="number" name="box6_total_output_tax"
                    id="box6"
                    value="{{ number_format($outputVatAmount, 2, '.', '') }}"
                    step="0.01"
                    class="border border-green-300 rounded px-3 py-1.5 text-sm text-right font-semibold bg-green-50">
            </div>
        </div>
    </div>

    <!-- Input Tax -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-1">VAT on Purchases (Input Tax)</h3>
        <p class="text-xs text-gray-400 mb-4">Auto-calculated from approved bills in the period.</p>

        <div class="space-y-3">
            <div class="grid grid-cols-3 gap-4 items-center">
                <label class="text-sm text-gray-600">Box 7 - Standard Rated Purchases</label>
                <input type="number" name="box7_standard_purchases"
                    value="{{ number_format($standardPurchases, 2, '.', '') }}"
                    step="0.01" class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
                <input type="number" name="box7_recoverable_vat"
                    value="{{ number_format($inputVatAmount, 2, '.', '') }}"
                    step="0.01" placeholder="Recoverable VAT"
                    class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
            </div>
            <div class="grid grid-cols-3 gap-4 items-center">
                <label class="text-sm text-gray-600">Box 8 - Reverse Charge Purchases</label>
                <input type="number" name="box8_reverse_charge_purchases" value="0"
                    step="0.01" class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
                <input type="number" name="box8_recoverable_vat" value="0"
                    step="0.01" placeholder="Recoverable VAT"
                    class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
            </div>
            <div class="grid grid-cols-3 gap-4 items-center border-t border-gray-100 pt-3">
                <label class="text-sm font-semibold text-gray-700">Box 9 - Total Input Tax</label>
                <span></span>
                <input type="number" name="box9_total_input_tax"
                    id="box9"
                    value="{{ number_format($inputVatAmount, 2, '.', '') }}"
                    step="0.01"
                    class="border border-blue-300 rounded px-3 py-1.5 text-sm text-right font-semibold bg-blue-50">
            </div>
        </div>
    </div>

    <!-- Net VAT -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Net VAT Calculation</h3>
        <div class="space-y-3">
            <div class="grid grid-cols-3 gap-4 items-center">
                <label class="text-sm text-gray-600">Box 10 - Adjustment</label>
                <span></span>
                <input type="number" name="box10_adjustment" value="0" step="0.01"
                    class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
            </div>
            <div class="grid grid-cols-3 gap-4 items-center">
                <label class="text-sm font-semibold text-gray-700">Box 11 - Net VAT Due</label>
                <span></span>
                <div class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right font-semibold bg-gray-50">
                    AED {{ number_format($netVat, 2) }}
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center">
                <label class="text-sm text-gray-600">Box 12 - VAT on Imports</label>
                <span></span>
                <input type="number" name="box12_vat_on_imports" value="0" step="0.01"
                    class="border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
            </div>
            <div class="grid grid-cols-3 gap-4 items-center border-t-2 border-gray-200 pt-3">
                <label class="text-base font-bold text-gray-800">Box 13 - Net VAT Payable</label>
                <span></span>
                <div class="border-2 border-{{ $netVat > 0 ? 'red' : 'green' }}-300 rounded px-3 py-2 text-sm text-right font-bold text-{{ $netVat > 0 ? 'red' : 'green' }}-700 bg-{{ $netVat > 0 ? 'red' : 'green' }}-50 text-lg">
                    AED {{ number_format($netVat, 2) }}
                    <span class="text-xs font-normal">{{ $netVat > 0 ? '(Payable to FTA)' : '(Refund due)' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
            💾 Save VAT Return
        </button>
        <a href="/vat" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
    </div>

</form>
</div>

@endsection