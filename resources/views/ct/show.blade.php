@extends('layouts.app')
@section('title', 'CT Return — ' . $return->fiscal_year)
@section('content')

<div class="max-w-4xl">

<div class="flex gap-1 mb-5 border-b border-gray-200">
    <a href="/ct" class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">CT Returns</a>
    <a href="/ct/sbr" class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">Small Business Relief</a>
</div>

{{-- Header --}}
<div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
    <div class="flex items-start justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-800">UAE Corporate Tax Return — {{ $return->fiscal_year }}</h2>
            <p class="text-xs text-gray-500 mt-0.5">
                Ref: {{ $return->return_number }} &nbsp;·&nbsp;
                Period: {{ $return->period_start }} to {{ $return->period_end }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @php
                $statusColor = match($return->status) {
                    'PAID'  => 'bg-green-100 text-green-700',
                    'FILED' => 'bg-blue-100 text-blue-700',
                    default => 'bg-yellow-100 text-yellow-700',
                };
            @endphp
            <span class="text-sm px-3 py-1 rounded-full font-medium {{ $statusColor }}">{{ $return->status }}</span>
        </div>
    </div>
    <div class="mt-3 grid grid-cols-3 gap-4 text-xs text-gray-500">
        <div>Taxpayer: <strong class="text-gray-700">{{ $company->name }}</strong></div>
        <div>TRN: <strong class="text-gray-700">{{ $company->trn ?? '—' }}</strong></div>
        <div>TIN: <strong class="text-gray-700">{{ $company->tin ?? '—' }}</strong></div>
    </div>
</div>

@if($return->sbr_elected)
{{-- SBR banner --}}
<div class="bg-green-50 border border-green-200 rounded-lg p-5 mb-4">
    <p class="text-sm font-semibold text-green-800">Small Business Relief Elected</p>
    <p class="text-xs text-green-700 mt-1">Taxable income is treated as nil. No Corporate Tax is due for this period.</p>
</div>
@endif

{{-- Income summary --}}
<div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
    <h3 class="text-sm font-bold text-gray-700 mb-4 pb-2 border-b border-gray-100">Income & Tax Summary</h3>
    <div class="space-y-3">
        <div class="flex justify-between items-center py-1.5 border-b border-gray-50">
            <span class="text-sm text-gray-600">Total Revenue</span>
            <span class="text-sm font-medium">AED {{ number_format($return->total_revenue, 2) }}</span>
        </div>
        <div class="flex justify-between items-center py-1.5 border-b border-gray-50">
            <span class="text-sm text-gray-600">Accounting Income / (Loss)</span>
            <span class="text-sm font-medium {{ $return->accounting_income < 0 ? 'text-red-600' : '' }}">
                AED {{ number_format($return->accounting_income, 2) }}
            </span>
        </div>
        @if($return->total_additions > 0)
        <div class="flex justify-between items-center py-1.5 border-b border-gray-50">
            <span class="text-sm text-gray-600">Add: Non-deductible expenses</span>
            <span class="text-sm text-red-600">+ AED {{ number_format($return->total_additions, 2) }}</span>
        </div>
        @endif
        @if($return->total_deductions > 0)
        <div class="flex justify-between items-center py-1.5 border-b border-gray-50">
            <span class="text-sm text-gray-600">Less: Exempt / deductible items</span>
            <span class="text-sm text-green-700">− AED {{ number_format($return->total_deductions, 2) }}</span>
        </div>
        @endif
        <div class="flex justify-between items-center py-1.5 border-b border-gray-100 font-semibold">
            <span class="text-sm text-gray-800">Taxable Income</span>
            <span class="text-sm">AED {{ number_format($return->taxable_income, 2) }}</span>
        </div>
        @if(!$return->sbr_elected)
        <div class="flex justify-between items-center py-1 text-xs text-gray-400">
            <span>@ 0% (up to AED 375,000)</span>
            <span>AED {{ number_format($return->income_at_zero_rate, 2) }}</span>
        </div>
        <div class="flex justify-between items-center py-1 text-xs text-gray-400">
            <span>@ 9% (above AED 375,000)</span>
            <span>AED {{ number_format($return->income_at_nine_pct, 2) }}</span>
        </div>
        <div class="flex justify-between items-center py-1.5 border-b border-gray-50">
            <span class="text-sm text-gray-600">Corporate Tax at 9%</span>
            <span class="text-sm text-red-600">AED {{ number_format($return->tax_at_nine_pct, 2) }}</span>
        </div>
        @if($return->foreign_tax_credit > 0)
        <div class="flex justify-between items-center py-1.5 border-b border-gray-50">
            <span class="text-sm text-gray-600">Less: Foreign Tax Credit</span>
            <span class="text-sm text-green-700">− AED {{ number_format($return->foreign_tax_credit, 2) }}</span>
        </div>
        @endif
        <div class="flex justify-between items-center py-3 bg-red-50 px-3 rounded font-bold">
            <span class="text-sm text-gray-800">Corporate Tax Payable</span>
            <span class="text-lg text-red-700">AED {{ number_format($return->ct_payable, 2) }}</span>
        </div>
        @else
        <div class="flex justify-between items-center py-3 bg-green-50 px-3 rounded font-bold">
            <span class="text-sm text-gray-800">Corporate Tax Payable</span>
            <span class="text-lg text-green-700">AED 0.00 (SBR)</span>
        </div>
        @endif
    </div>
</div>

{{-- Adjustments --}}
@if($adjustments->count())
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
    <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
        <h3 class="text-sm font-bold text-gray-700">Adjustments to Accounting Income</h3>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                <th class="px-4 py-2 text-left">Type</th>
                <th class="px-4 py-2 text-left">Category</th>
                <th class="px-4 py-2 text-left">Description</th>
                <th class="px-4 py-2 text-right">Amount (AED)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($adjustments as $adj)
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2">
                    <span class="text-xs px-2 py-0.5 rounded {{ $adj->type === 'ADD' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                        {{ $adj->type === 'ADD' ? 'Add (+)' : 'Less (−)' }}
                    </span>
                </td>
                <td class="px-4 py-2 text-xs text-gray-600">{{ $adj->category }}</td>
                <td class="px-4 py-2 text-xs text-gray-500">{{ $adj->description ?: '—' }}</td>
                <td class="px-4 py-2 text-right text-xs font-medium {{ $adj->type === 'ADD' ? 'text-red-600' : 'text-green-700' }}">
                    {{ $adj->type === 'ADD' ? '+' : '−' }} {{ number_format($adj->amount, 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Filing & Payment info --}}
@if($return->status !== 'DRAFT')
<div class="bg-white rounded-lg border border-gray-200 p-5 mb-4 grid grid-cols-2 gap-6">
    @if($return->filing_date)
    <div>
        <p class="text-xs text-gray-500 mb-1">Filed On</p>
        <p class="text-sm font-medium text-gray-700">{{ $return->filing_date }}</p>
        @if($return->filing_reference)
            <p class="text-xs text-gray-400 mt-0.5">Ref: {{ $return->filing_reference }}</p>
        @endif
    </div>
    @endif
    @if($return->amount_paid)
    <div>
        <p class="text-xs text-gray-500 mb-1">Payment</p>
        <p class="text-sm font-medium text-green-700">AED {{ number_format($return->amount_paid, 2) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">on {{ $return->payment_date }}</p>
    </div>
    @endif
</div>
@endif

@if($return->notes)
<div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
    <p class="text-xs text-gray-500 mb-1">Notes</p>
    <p class="text-sm text-gray-700">{{ $return->notes }}</p>
</div>
@endif

{{-- Actions --}}
<div class="flex gap-3 flex-wrap">
    @if($return->status === 'DRAFT')
    <button onclick="document.getElementById('file-modal').classList.remove('hidden')"
        class="bg-blue-600 text-white text-sm px-4 py-2 rounded hover:bg-blue-700">
        📤 Mark as Filed
    </button>
    @endif

    @if(in_array($return->status, ['DRAFT', 'FILED']) && $return->ct_payable > 0)
    <button onclick="document.getElementById('pay-modal').classList.remove('hidden')"
        class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">
        💳 Record Payment
    </button>
    @endif

    <a href="/ct" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">← Back to CT Returns</a>
</div>

</div>

{{-- File modal --}}
<div id="file-modal" class="hidden fixed inset-0 bg-black bg-opacity-30 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg border border-gray-200 w-96 p-6 shadow-xl">
        <h3 class="text-sm font-bold text-gray-700 mb-4">Mark CT Return as Filed</h3>
        <form method="POST" action="/ct/{{ $return->id }}/submit">
        @csrf
            <div class="mb-3">
                <label class="text-xs text-gray-500 block mb-1">Filing Date</label>
                <input type="date" name="filing_date" value="{{ date('Y-m-d') }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="mb-4">
                <label class="text-xs text-gray-500 block mb-1">FTA Reference Number (optional)</label>
                <input type="text" name="filing_reference" placeholder="e.g. FTA-CT-2024-XXXXX"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white text-sm px-4 py-2 rounded hover:bg-blue-700">Confirm</button>
                <button type="button" onclick="document.getElementById('file-modal').classList.add('hidden')"
                    class="text-sm text-gray-500 px-4 py-2">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Pay modal --}}
<div id="pay-modal" class="hidden fixed inset-0 bg-black bg-opacity-30 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg border border-gray-200 w-96 p-6 shadow-xl">
        <h3 class="text-sm font-bold text-gray-700 mb-4">Record CT Payment</h3>
        <form method="POST" action="/ct/{{ $return->id }}/pay">
        @csrf
            <div class="mb-3">
                <label class="text-xs text-gray-500 block mb-1">Amount Paid (AED)</label>
                <input type="number" name="amount_paid" value="{{ $return->ct_payable }}" step="0.01"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm text-right">
            </div>
            <div class="mb-4">
                <label class="text-xs text-gray-500 block mb-1">Payment Date</label>
                <input type="date" name="payment_date" value="{{ date('Y-m-d') }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">Save</button>
                <button type="button" onclick="document.getElementById('pay-modal').classList.add('hidden')"
                    class="text-sm text-gray-500 px-4 py-2">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection
