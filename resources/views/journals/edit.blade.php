@extends('layouts.app')

@section('title', 'Edit Journal - ' . $journal->entry_number)

@section('content')

<div class="max-w-4xl">

<form method="POST" action="/journals/{{ $journal->id }}">
    @csrf
    @method('PUT')

    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-700">Edit Journal - {{ $journal->entry_number }}</h2>
            <span class="text-xs bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full">DRAFT</span>
        </div>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Entry Date *</label>
                <input type="date" name="entry_date" value="{{ $journal->entry_date }}" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Reference</label>
                <input type="text" name="reference" value="{{ $journal->reference }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Description</label>
                <input type="text" name="description" value="{{ $journal->description }}" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <!-- Lines -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700 text-sm">Journal Lines</h3>
            <div class="flex items-center gap-3">
                <span id="balance-indicator" class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-500">Balanced</span>
                <button type="button" onclick="addLine()"
                    class="text-xs bg-green-700 text-white px-3 py-1 rounded hover:bg-green-800">➕ Add Line</button>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                    <th class="px-3 py-2 text-left">Account</th>
                    <th class="px-3 py-2 text-left">Description</th>
                    <th class="px-3 py-2 text-right w-36">Debit</th>
                    <th class="px-3 py-2 text-right w-36">Credit</th>
                    <th class="px-3 py-2 w-8"></th>
                </tr>
            </thead>
            <tbody id="lines-body"></tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t border-gray-200 font-semibold">
                    <td colspan="2" class="px-3 py-2 text-right text-xs text-gray-500">TOTALS</td>
                    <td class="px-3 py-2 text-right" id="total-debit">0.00</td>
                    <td class="px-3 py-2 text-right" id="total-credit">0.00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
            💾 Save Changes
        </button>
        <a href="/journals/{{ $journal->id }}" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
    </div>

</form>
</div>

<script>
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

const existingLines = [
    @foreach($lines as $line)
    {
        account_id: {{ $line->account_id ?? 'null' }},
        description: "{{ addslashes($line->description ?? '') }}",
        debit: {{ $line->debit_amount }},
        credit: {{ $line->credit_amount }},
    },
    @endforeach
];

function buildAccountSelect(index, selectedId = null) {
    let html = `<select name="accounts[${index}]" class="w-full border border-gray-200 rounded px-2 py-1 text-xs" onchange="updateTotals()">`;
    html += `<option value="">- Account -</option>`;
    for (const [type, accts] of Object.entries(accountGroups)) {
        html += `<optgroup label="${typeLabels[type] || type}">`;
        accts.forEach(a => {
            const sel = selectedId == a.id ? 'selected' : '';
            html += `<option value="${a.id}" ${sel}>${a.code} - ${a.name}</option>`;
        });
        html += `</optgroup>`;
    }
    html += `</select>`;
    return html;
}

let lineCount = 0;

function addLine(data = null) {
    const tbody = document.getElementById('lines-body');
    const i = lineCount++;
    const tr = document.createElement('tr');
    tr.className = 'border-b border-gray-50 hover:bg-gray-50';
    tr.id = `line-${i}`;
    tr.innerHTML = `
        <td class="px-3 py-2 w-64">${buildAccountSelect(i, data ? data.account_id : null)}</td>
        <td class="px-3 py-2">
            <input type="text" name="line_descriptions[${i}]" value="${data ? data.description : ''}"
                placeholder="Description"
                class="w-full border border-gray-200 rounded px-2 py-1 text-sm">
        </td>
        <td class="px-3 py-2">
            <input type="number" name="debits[${i}]" value="${data ? data.debit : '0.00'}"
                step="0.01" min="0"
                class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right"
                onchange="updateTotals()" onfocus="if(this.value=='0.00'||this.value=='0')this.value=''">
        </td>
        <td class="px-3 py-2">
            <input type="number" name="credits[${i}]" value="${data ? data.credit : '0.00'}"
                step="0.01" min="0"
                class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right"
                onchange="updateTotals()" onfocus="if(this.value=='0.00'||this.value=='0')this.value=''">
        </td>
        <td class="px-3 py-2 text-center">
            <button type="button" onclick="removeLine(${i})"
                class="text-red-400 hover:text-red-600 text-xs">✕</button>
        </td>
    `;
    tbody.appendChild(tr);
    updateTotals();
}

function removeLine(i) {
    const tr = document.getElementById(`line-${i}`);
    if (tr) tr.remove();
    updateTotals();
}

function updateTotals() {
    let totalDebit = 0, totalCredit = 0;
    document.querySelectorAll('[id^="line-"]').forEach(row => {
        const i = row.id.replace('line-', '');
        totalDebit  += parseFloat(document.querySelector(`[name="debits[${i}]"]`)?.value || 0);
        totalCredit += parseFloat(document.querySelector(`[name="credits[${i}]"]`)?.value || 0);
    });
    document.getElementById('total-debit').textContent  = totalDebit.toFixed(2);
    document.getElementById('total-credit').textContent = totalCredit.toFixed(2);
    const indicator = document.getElementById('balance-indicator');
    const balanced  = Math.abs(totalDebit - totalCredit) < 0.01;
    indicator.textContent  = balanced ? '✓ Balanced' : '✗ Not balanced';
    indicator.className    = `text-xs px-2 py-1 rounded-full ${balanced ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'}`;
}

existingLines.forEach(line => addLine(line));
if (existingLines.length === 0) { addLine(); addLine(); }
</script>

@endsection