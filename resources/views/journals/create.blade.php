@extends('layouts.app')

@section('title', 'New Journal Entry')

@section('content')

<div class="max-w-5xl">

@if(session('error'))
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded text-sm">
    {{ session('error') }}
</div>
@endif

<form method="POST" action="/journals" id="je-form">
    @csrf

    <!-- Header fields -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Date *</label>
                <input type="date" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm" required>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Type</label>
                <select name="journal_type" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
    				<option value="GENERAL">General</option>
    				<option value="CASH_PAYMENT">Cash Payment</option>
    				<option value="CASH_RECEIPT">Cash Receipt</option>
    				<option value="BANK_PAYMENT">Bank Payment</option>
    				<option value="BANK_RECEIPT">Bank Receipt</option>
    				<option value="ADJUSTMENT">Adjustment</option>
    				<option value="DEPRECIATION">Depreciation</option>
    				<option value="PAYROLL">Payroll</option>
				</select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Reference</label>
                <input type="text" name="reference" value="{{ old('reference') }}"
                    placeholder="CHQ-001, TRF-123..."
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Description *</label>
                <input type="text" name="description" value="{{ old('description') }}"
                    placeholder="Monthly rent payment..."
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm" required>
            </div>
        </div>
    </div>

    <!-- Lines -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700 text-sm">Journal Lines</h3>
            <div class="flex gap-2">
                <button type="button" onclick="addLine()"
                    class="text-xs bg-green-700 text-white px-3 py-1 rounded hover:bg-green-800">➕ Add Line</button>
                <button type="button" onclick="removeLine()"
                    class="text-xs bg-gray-200 text-gray-700 px-3 py-1 rounded hover:bg-gray-300">➖ Remove Line</button>
            </div>
        </div>

        <table class="w-full text-sm" id="lines-table">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                    <th class="px-4 py-2 text-left w-8">#</th>
                    <th class="px-4 py-2 text-left">Account</th>
                    <th class="px-4 py-2 text-left">Description</th>
                    <th class="px-4 py-2 text-right w-36">Debit (AED)</th>
                    <th class="px-4 py-2 text-right w-36">Credit (AED)</th>
                </tr>
            </thead>
            <tbody id="lines-body">
                <!-- Lines added by JS -->
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t border-gray-200 font-semibold">
                    <td colspan="3" class="px-4 py-2 text-right text-xs text-gray-500">TOTALS</td>
                    <td class="px-4 py-2 text-right" id="total-debit">0.00</td>
                    <td class="px-4 py-2 text-right" id="total-credit">0.00</td>
                </tr>
                <tr id="balance-row">
                    <td colspan="5" class="px-4 py-2 text-center text-xs" id="balance-indicator">
                        —
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Actions -->
    <div class="flex gap-3">
        <button type="submit" name="save_draft" value="1"
            class="bg-gray-600 text-white text-sm px-5 py-2 rounded hover:bg-gray-700">
            💾 Save as Draft
        </button>
        <button type="submit" name="auto_post" value="1"
            class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
            ✅ Save & Post
        </button>
        <a href="/journals" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
    </div>

</form>
</div>

<script>
// Build accounts data from PHP
const accountGroups = {
    @foreach($accounts as $type => $accts)
    "{{ $type }}": [
        @foreach($accts as $a)
        { id: {{ $a->id }}, code: "{{ $a->code }}", name: "{{ addslashes($a->name) }}" },
        @endforeach
    ],
    @endforeach
};

const typeLabels = {
    'ASSET': 'Assets', 'LIABILITY': 'Liabilities',
    'EQUITY': 'Equity', 'REVENUE': 'Revenue', 'EXPENSE': 'Expenses'
};

function buildSelect(name, index) {
    let html = `<select name="${name}[${index}]" class="w-full border border-gray-200 rounded px-2 py-1 text-sm" onchange="updateTotals()">`;
    html += `<option value="">— Select Account —</option>`;
    for (const [type, accounts] of Object.entries(accountGroups)) {
        html += `<optgroup label="── ${typeLabels[type] || type} ──">`;
        accounts.forEach(a => {
            html += `<option value="${a.id}">${a.code} — ${a.name}</option>`;
        });
        html += `</optgroup>`;
    }
    html += `</select>`;
    return html;
}

let lineCount = 0;

function addLine() {
    const tbody = document.getElementById('lines-body');
    const i = lineCount++;
    const tr = document.createElement('tr');
    tr.className = 'border-b border-gray-50 hover:bg-gray-50';
    tr.id = `line-${i}`;
    tr.innerHTML = `
        <td class="px-4 py-2 text-gray-400 text-xs">${i + 1}</td>
        <td class="px-4 py-2">${buildSelect('accounts', i)}</td>
        <td class="px-4 py-2">
            <input type="text" name="line_descriptions[${i}]"
                class="w-full border border-gray-200 rounded px-2 py-1 text-sm"
                placeholder="Optional description">
        </td>
        <td class="px-4 py-2">
            <input type="number" name="debits[${i}]" value="0.00"
                step="0.01" min="0"
                class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right"
                onchange="updateTotals()" onfocus="if(this.value=='0.00')this.value=''">
        </td>
        <td class="px-4 py-2">
            <input type="number" name="credits[${i}]" value="0.00"
                step="0.01" min="0"
                class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right"
                onchange="updateTotals()" onfocus="if(this.value=='0.00')this.value=''" >
        </td>
    `;
    tbody.appendChild(tr);
    updateTotals();
}

function removeLine() {
    const tbody = document.getElementById('lines-body');
    if (tbody.children.length > 2) {
        tbody.removeChild(tbody.lastChild);
        lineCount--;
        updateTotals();
    }
}

function updateTotals() {
    let debit = 0, credit = 0;
    document.querySelectorAll('[name^="debits"]').forEach(el  => debit  += parseFloat(el.value || 0));
    document.querySelectorAll('[name^="credits"]').forEach(el => credit += parseFloat(el.value || 0));

    document.getElementById('total-debit').textContent  = debit.toFixed(2);
    document.getElementById('total-credit').textContent = credit.toFixed(2);

    const diff = Math.abs(debit - credit);
    const indicator = document.getElementById('balance-indicator');
    if (debit === 0 && credit === 0) {
        indicator.innerHTML = '—';
        indicator.className = 'px-4 py-2 text-center text-xs text-gray-400';
    } else if (diff < 0.01) {
        indicator.innerHTML = '✅ Balanced';
        indicator.className = 'px-4 py-2 text-center text-xs text-green-600 font-semibold';
    } else {
        indicator.innerHTML = `⚠️ Out of balance by AED ${diff.toFixed(2)}`;
        indicator.className = 'px-4 py-2 text-center text-xs text-red-600 font-semibold';
    }
}

// Start with 2 lines
addLine();
addLine();
</script>

@endsection