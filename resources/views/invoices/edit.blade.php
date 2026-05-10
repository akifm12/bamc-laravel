@extends('layouts.app')

@section('title', 'Edit Invoice — ' . $invoice->invoice_number)

@section('content')

<div class="max-w-5xl">

<form method="POST" action="/invoices/{{ $invoice->id }}">
    @csrf
    @method('PUT')

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-700">Edit Invoice — {{ $invoice->invoice_number }}</h2>
            <span class="text-xs bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full">DRAFT</span>
        </div>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Customer *</label>
                <select name="customer_id" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ $invoice->customer_id == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Invoice Date *</label>
                <input type="date" name="invoice_date" value="{{ $invoice->invoice_date }}" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Due Date</label>
                <input type="date" name="due_date" value="{{ $invoice->due_date }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">PO Number</label>
                <input type="text" name="po_number" value="{{ $invoice->po_number }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="col-span-2">
                <label class="text-xs text-gray-500 block mb-1">Notes</label>
                <input type="text" name="notes" value="{{ $invoice->notes }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <!-- Lines -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-700 text-sm">Invoice Lines</h3>
                <p class="text-xs text-gray-400 mt-0.5">Select the revenue account for each line. AR account is assigned automatically.</p>
            </div>
            <button type="button" onclick="addLine()"
                class="text-xs bg-green-700 text-white px-3 py-1 rounded hover:bg-green-800">➕ Add Line</button>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                    <th class="px-3 py-2 text-left">Description</th>
                    <th class="px-3 py-2 text-left w-48">Revenue Account</th>
                    <th class="px-3 py-2 text-right w-20">Qty</th>
                    <th class="px-3 py-2 text-right w-28">Unit Price</th>
                    <th class="px-3 py-2 text-right w-20">VAT %</th>
                    <th class="px-3 py-2 text-right w-28">VAT Amt</th>
                    <th class="px-3 py-2 text-right w-28">Total</th>
                    <th class="px-3 py-2 w-8"></th>
                </tr>
            </thead>
            <tbody id="lines-body"></tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t border-gray-200">
                    <td colspan="5" class="px-3 py-2 text-right text-xs text-gray-500">Subtotal</td>
                    <td class="px-3 py-2 text-right" id="total-vat">0.00</td>
                    <td class="px-3 py-2 text-right font-semibold" id="total-subtotal">0.00</td>
                    <td></td>
                </tr>
                <tr class="bg-gray-50 font-bold">
                    <td colspan="6" class="px-3 py-2 text-right text-sm">TOTAL (AED)</td>
                    <td class="px-3 py-2 text-right text-green-700" id="total-amount">0.00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
            💾 Save Changes
        </button>
        <a href="/invoices/{{ $invoice->id }}" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
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

// Existing lines from DB
const existingLines = [
    @foreach($lines as $line)
    {
        description: "{{ addslashes($line->description) }}",
        account_id: {{ $line->account_id ?? 'null' }},
        quantity: {{ $line->quantity }},
        unit_price: {{ $line->unit_price }},
        vat_rate: {{ $line->vat_rate }},
    },
    @endforeach
];

function buildAccountSelect(index, selectedId = null) {
    let html = `<select name="accounts[${index}]" class="w-full border border-gray-200 rounded px-2 py-1 text-xs" onchange="updateTotals()">`;
    html += `<option value="">— Account —</option>`;
    for (const [type, accounts] of Object.entries(accountGroups)) {
        html += `<optgroup label="${typeLabels[type] || type}">`;
        accounts.forEach(a => {
            const sel = selectedId == a.id ? 'selected' : '';
            html += `<option value="${a.id}" ${sel}>${a.code} — ${a.name}</option>`;
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
        <td class="px-3 py-2">
            <input type="text" name="descriptions[${i}]" value="${data ? data.description : ''}" placeholder="Description"
                class="w-full border border-gray-200 rounded px-2 py-1 text-sm">
        </td>
        <td class="px-3 py-2">${buildAccountSelect(i, data ? data.account_id : null)}</td>
        <td class="px-3 py-2">
            <input type="number" name="quantities[${i}]" value="${data ? data.quantity : 1}" min="0" step="0.01"
                class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right"
                onchange="updateTotals()">
        </td>
        <td class="px-3 py-2">
            <input type="number" name="unit_prices[${i}]" value="${data ? data.unit_price : '0.00'}" min="0" step="0.01"
                class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right"
                onchange="updateTotals()">
        </td>
        <td class="px-3 py-2">
            <select name="vat_rates[${i}]" class="w-full border border-gray-200 rounded px-2 py-1 text-xs" onchange="updateTotals()">
                <option value="0" ${data && data.vat_rate == 0 ? 'selected' : ''}>0%</option>
                <option value="5" ${!data || data.vat_rate == 5 ? 'selected' : ''}>5%</option>
            </select>
        </td>
        <td class="px-3 py-2 text-right text-xs text-gray-600" id="vat-${i}">0.00</td>
        <td class="px-3 py-2 text-right text-xs font-medium" id="linetotal-${i}">0.00</td>
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
    let subtotal = 0, totalVat = 0;
    document.querySelectorAll('[id^="line-"]').forEach(row => {
        const i = row.id.replace('line-', '');
        const qty   = parseFloat(document.querySelector(`[name="quantities[${i}]"]`)?.value || 0);
        const price = parseFloat(document.querySelector(`[name="unit_prices[${i}]"]`)?.value || 0);
        const vat   = parseFloat(document.querySelector(`[name="vat_rates[${i}]"]`)?.value || 0);
        const lineAmount = qty * price;
        const vatAmt     = lineAmount * (vat / 100);
        subtotal  += lineAmount;
        totalVat  += vatAmt;
        const vatEl = document.getElementById(`vat-${i}`);
        const totEl = document.getElementById(`linetotal-${i}`);
        if (vatEl) vatEl.textContent = vatAmt.toFixed(2);
        if (totEl) totEl.textContent = (lineAmount + vatAmt).toFixed(2);
    });
    document.getElementById('total-subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('total-vat').textContent      = totalVat.toFixed(2);
    document.getElementById('total-amount').textContent   = (subtotal + totalVat).toFixed(2);
}

// Load existing lines
existingLines.forEach(line => addLine(line));
if (existingLines.length === 0) addLine();
</script>

@endsection