@extends('layouts.app')
@section('title', 'Corporate Tax Return — ' . $year)
@section('content')

<div class="max-w-4xl">

<div class="flex gap-1 mb-5 border-b border-gray-200">
    <a href="/ct" class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">CT Returns</a>
    <a href="/ct/sbr" class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">Small Business Relief</a>
</div>

<form method="POST" action="/ct" id="ct-form">
@csrf
<input type="hidden" name="year" value="{{ $year }}">

{{-- Section header --}}
<div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-bold text-gray-800">UAE Corporate Tax Return</h2>
            <p class="text-xs text-gray-500 mt-0.5">Tax Period: {{ $periodStart }} to {{ $periodEnd }}</p>
        </div>
        <div class="text-right text-xs text-gray-500">
            <div>Taxpayer: <strong>{{ $company->name }}</strong></div>
            <div>TRN: {{ $company->trn ?? '—' }}</div>
            <div>TIN: {{ $company->tin ?? '—' }}</div>
        </div>
    </div>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="text-xs text-gray-500 block mb-1">Tax Period From</label>
            <input type="text" value="{{ $periodStart }}" readonly class="w-full border border-gray-100 bg-gray-50 rounded px-3 py-1.5 text-sm text-gray-600">
        </div>
        <div>
            <label class="text-xs text-gray-500 block mb-1">Tax Period To</label>
            <input type="text" value="{{ $periodEnd }}" readonly class="w-full border border-gray-100 bg-gray-50 rounded px-3 py-1.5 text-sm text-gray-600">
        </div>
        <div>
            <label class="text-xs text-gray-500 block mb-1">Accounting Basis</label>
            <input type="text" value="Accrual" readonly class="w-full border border-gray-100 bg-gray-50 rounded px-3 py-1.5 text-sm text-gray-600">
        </div>
    </div>
</div>

{{-- Small Business Relief Election --}}
<div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
    <div class="flex items-start gap-3">
        <input type="checkbox" name="sbr_elected" id="sbr_elected" value="1"
            {{ $revenue <= 3000000 && $revenue > 0 ? '' : 'disabled' }}
            onchange="toggleSBR(this.checked)"
            class="mt-1 rounded">
        <div>
            <label for="sbr_elected" class="text-sm font-semibold text-gray-700 cursor-pointer">
                Elect Small Business Relief (SBR)
            </label>
            <p class="text-xs text-gray-500 mt-0.5">
                Available if revenue ≤ AED 3,000,000 and not part of a Qualifying Multinational Enterprise Group.
                If elected, taxable income is treated as nil for this period.
            </p>
            @if($revenue > 3000000)
                <p class="text-xs text-red-500 mt-1">⚠ Not eligible — revenue exceeds AED 3M (AED {{ number_format($revenue, 2) }})</p>
            @elseif($revenue > 0)
                <p class="text-xs text-green-600 mt-1">✓ Eligible — revenue AED {{ number_format($revenue, 2) }} is within the AED 3M limit</p>
            @endif
        </div>
    </div>
</div>

<div id="ct-body">

{{-- Section 1: Revenue --}}
<div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
    <h3 class="text-sm font-bold text-gray-700 mb-3 pb-2 border-b border-gray-100">Section 1 — Revenue</h3>
    <div class="flex items-center justify-between">
        <label class="text-sm text-gray-600">Total Revenue for the Tax Period (AED)</label>
        <input type="number" name="total_revenue" value="{{ number_format($revenue, 2, '.', '') }}"
            step="0.01" class="w-48 border border-gray-200 rounded px-3 py-1.5 text-sm text-right"
            onchange="recalc()">
    </div>
</div>

{{-- Section 2: Accounting Income --}}
<div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
    <h3 class="text-sm font-bold text-gray-700 mb-3 pb-2 border-b border-gray-100">Section 2 — Accounting Income / (Loss)</h3>
    <div class="flex items-center justify-between">
        <div>
            <label class="text-sm text-gray-600">Net Profit / (Loss) per Financial Statements (AED)</label>
            <p class="text-xs text-gray-400 mt-0.5">Auto-calculated from your P&L journals for {{ $year }}</p>
        </div>
        <input type="number" name="accounting_income" id="accounting_income"
            value="{{ number_format($netProfit, 2, '.', '') }}"
            step="0.01" class="w-48 border border-gray-200 rounded px-3 py-1.5 text-sm text-right"
            onchange="recalc()">
    </div>
</div>

{{-- Section 3: Adjustments --}}
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
    <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-sm font-bold text-gray-700">Section 3 — Adjustments to Accounting Income</h3>
        <button type="button" onclick="addAdj()" class="text-xs bg-gray-700 text-white px-3 py-1 rounded hover:bg-gray-800">➕ Add Line</button>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                <th class="px-3 py-2 text-left w-24">Type</th>
                <th class="px-3 py-2 text-left">Category</th>
                <th class="px-3 py-2 text-left">Description</th>
                <th class="px-3 py-2 text-right w-40">Amount (AED)</th>
                <th class="px-3 py-2 w-8"></th>
            </tr>
        </thead>
        <tbody id="adj-body"></tbody>
    </table>
    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 grid grid-cols-2 gap-4 text-sm">
        <div class="flex justify-between">
            <span class="text-gray-500">Total Additions (non-deductible):</span>
            <span class="font-semibold text-red-600" id="total-add">0.00</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500">Total Deductions (exempt):</span>
            <span class="font-semibold text-green-700" id="total-less">0.00</span>
        </div>
    </div>
</div>

{{-- Section 4: Taxable Income + Tax Calc --}}
<div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
    <h3 class="text-sm font-bold text-gray-700 mb-4 pb-2 border-b border-gray-100">Section 4 — Taxable Income & Tax Calculation</h3>
    <div class="space-y-3">
        <div class="flex justify-between items-center py-2 border-b border-gray-50">
            <span class="text-sm text-gray-600">Accounting Income / (Loss)</span>
            <span class="font-medium text-sm" id="disp-accounting">{{ number_format($netProfit, 2) }}</span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-50">
            <span class="text-sm text-gray-600">Add: Non-deductible expenses</span>
            <span class="font-medium text-sm text-red-600" id="disp-add">0.00</span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-50">
            <span class="text-sm text-gray-600">Less: Exempt / deductible items</span>
            <span class="font-medium text-sm text-green-700" id="disp-less">0.00</span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-200 font-semibold">
            <span class="text-sm text-gray-800">Taxable Income</span>
            <span class="text-sm" id="disp-taxable">0.00</span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-50 text-xs text-gray-500">
            <span>@ 0% (up to AED 375,000)</span>
            <span id="disp-zero">0.00</span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-50 text-xs text-gray-500">
            <span>@ 9% (above AED 375,000)</span>
            <span id="disp-nine">0.00</span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-50">
            <span class="text-sm text-gray-600">Corporate Tax at 9%</span>
            <span class="font-medium text-sm text-red-600" id="disp-tax">0.00</span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-50">
            <span class="text-sm text-gray-600">Less: Foreign Tax Credit</span>
            <input type="number" name="foreign_tax_credit" id="foreign_tax_credit" value="0"
                step="0.01" min="0" class="w-40 border border-gray-200 rounded px-2 py-1 text-sm text-right"
                onchange="recalc()">
        </div>
        <div class="flex justify-between items-center py-3 bg-red-50 px-3 rounded font-bold">
            <span class="text-sm text-gray-800">Corporate Tax Payable</span>
            <span class="text-lg text-red-700" id="disp-payable">0.00</span>
        </div>
    </div>
</div>

{{-- Section 5: Notes --}}
<div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
    <h3 class="text-sm font-bold text-gray-700 mb-3">Notes</h3>
    <textarea name="notes" rows="2" placeholder="Optional notes..."
        class="w-full border border-gray-200 rounded px-3 py-2 text-sm"></textarea>
</div>

</div>{{-- end #ct-body --}}

{{-- SBR notice shown when elected --}}
<div id="sbr-notice" class="hidden bg-green-50 border border-green-200 rounded-lg p-5 mb-4">
    <p class="text-sm font-semibold text-green-800">Small Business Relief Elected</p>
    <p class="text-xs text-green-700 mt-1">Taxable income is treated as nil. No Corporate Tax is due for this period. This election will be recorded on your CT Return.</p>
</div>

<div class="flex gap-3">
    <button type="submit" class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
        Save CT Return
    </button>
    <a href="/ct" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
</div>

</form>
</div>

<script>
const ADD_CATEGORIES  = @json($addCategories);
const LESS_CATEGORIES = @json($lessCategories);
const THRESHOLD = 375000;
let adjCount = 0;

function addAdj(type = 'ADD', cat = '', desc = '', amt = '') {
    const tbody = document.getElementById('adj-body');
    const i = adjCount++;
    const tr = document.createElement('tr');
    tr.className = 'border-b border-gray-50';
    tr.id = `adj-${i}`;
    const cats = type === 'ADD' ? ADD_CATEGORIES : LESS_CATEGORIES;
    tr.innerHTML = `
        <td class="px-3 py-2">
            <select name="adj_type[${i}]" class="w-full border border-gray-200 rounded px-2 py-1 text-xs"
                onchange="switchCats(${i}, this.value); recalc()">
                <option value="ADD" ${type==='ADD'?'selected':''}>Add (+)</option>
                <option value="LESS" ${type==='LESS'?'selected':''}>Less (−)</option>
            </select>
        </td>
        <td class="px-3 py-2">
            <select name="adj_category[${i}]" id="adj-cat-${i}"
                class="w-full border border-gray-200 rounded px-2 py-1 text-xs">
                ${cats.map(c => `<option value="${c}" ${c===cat?'selected':''}>${c}</option>`).join('')}
                <option value="Other" ${'Other'===cat?'selected':''}>Other</option>
            </select>
        </td>
        <td class="px-3 py-2">
            <input type="text" name="adj_description[${i}]" value="${desc}"
                placeholder="Optional detail..."
                class="w-full border border-gray-200 rounded px-2 py-1 text-xs">
        </td>
        <td class="px-3 py-2">
            <input type="number" name="adj_amount[${i}]" value="${amt}"
                step="0.01" min="0" placeholder="0.00"
                class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right"
                onchange="recalc()">
        </td>
        <td class="px-3 py-2 text-center">
            <button type="button" onclick="this.closest('tr').remove(); recalc()"
                class="text-red-400 hover:text-red-600 text-xs">✕</button>
        </td>
    `;
    tbody.appendChild(tr);
}

function switchCats(i, type) {
    const sel  = document.getElementById(`adj-cat-${i}`);
    const cats = type === 'ADD' ? ADD_CATEGORIES : LESS_CATEGORIES;
    sel.innerHTML = cats.map(c => `<option value="${c}">${c}</option>`).join('') + '<option value="Other">Other</option>';
}

function recalc() {
    if (document.getElementById('sbr_elected').checked) return;
    const accounting = parseFloat(document.getElementById('accounting_income').value) || 0;
    let additions = 0, deductions = 0;
    document.querySelectorAll('[name^="adj_type["]').forEach((sel, idx) => {
        const amt = parseFloat(document.querySelectorAll('[name^="adj_amount["]')[idx]?.value) || 0;
        if (sel.value === 'ADD') additions += amt;
        else deductions += amt;
    });
    const taxable  = Math.max(0, accounting + additions - deductions);
    const zero     = Math.min(taxable, THRESHOLD);
    const nine     = Math.max(0, taxable - THRESHOLD);
    const tax      = +(nine * 0.09).toFixed(2);
    const credit   = parseFloat(document.getElementById('foreign_tax_credit').value) || 0;
    const payable  = Math.max(0, tax - credit);

    document.getElementById('total-add').textContent   = additions.toFixed(2);
    document.getElementById('total-less').textContent  = deductions.toFixed(2);
    document.getElementById('disp-accounting').textContent = accounting.toFixed(2);
    document.getElementById('disp-add').textContent   = additions.toFixed(2);
    document.getElementById('disp-less').textContent  = deductions.toFixed(2);
    document.getElementById('disp-taxable').textContent = taxable.toFixed(2);
    document.getElementById('disp-zero').textContent  = zero.toFixed(2);
    document.getElementById('disp-nine').textContent  = nine.toFixed(2);
    document.getElementById('disp-tax').textContent   = tax.toFixed(2);
    document.getElementById('disp-payable').textContent = 'AED ' + payable.toFixed(2);
}

function toggleSBR(elected) {
    document.getElementById('ct-body').style.display    = elected ? 'none' : '';
    document.getElementById('sbr-notice').classList.toggle('hidden', !elected);
}

recalc();
</script>

@endsection
