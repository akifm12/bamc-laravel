@extends('layouts.app')

@section('title', 'New Invoice')

@section('content')

<div class="max-w-5xl">

@if(session('error'))
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded text-sm">
    {{ session('error') }}
</div>
@endif

<form method="POST" action="/invoices" id="invoice-form" onsubmit="buildTxnFlags()">
    @csrf

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Customer *</label>
                <select name="customer_id" id="customer_id" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm"
                    onchange="fetchCustomerDetails(this.value)">
                    <option value="">- Select Customer -</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}"
                            {{ (isset($selectedCustomer) && $selectedCustomer->id == $c->id) ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
                <p id="customer-info" class="text-xs text-gray-400 mt-1"></p>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Invoice Date *</label>
                <input type="date" name="invoice_date" value="{{ date('Y-m-d') }}" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Due Date</label>
                <input type="date" name="due_date"
                    value="{{ date('Y-m-d', strtotime('+30 days')) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">PO Number</label>
                <input type="text" name="po_number" placeholder="Optional"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Payment Method</label>
                <select name="payment_means_code" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="30" selected>Bank Transfer</option>
                    <option value="10">Cash</option>
                    <option value="48">Bank Card</option>
                    <option value="42">Bank Account</option>
                </select>
            </div>
            <div class="col-span-2">
                <label class="text-xs text-gray-500 block mb-1">Notes</label>
                <input type="text" name="notes" placeholder="Optional notes on invoice"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>

        <!-- Transaction Type Flags (e-Invoice) -->
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs font-medium text-gray-500 mb-2">Transaction Type Flags <span class="text-gray-400 font-normal">(e-Invoice — check all that apply)</span></p>
            <div class="grid grid-cols-4 gap-2">
                @foreach([
                    ['free_trade_zone',    'Free Trade Zone',      0],
                    ['deemed_supply',      'Deemed Supply',        1],
                    ['margin_scheme',      'Margin Scheme',        2],
                    ['summary_invoice',    'Summary Invoice',      3],
                    ['continuous_supply',  'Continuous Supply',    4],
                    ['disclosed_agent',    'Disclosed Agent',      5],
                    ['ecommerce',          'E-Commerce',           6],
                    ['exports',            'Exports',              7],
                ] as [$key, $label, $pos])
                <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                    <input type="checkbox" name="txn_flags[{{ $pos }}]" value="1" class="rounded">
                    {{ $label }}
                </label>
                @endforeach
            </div>
            <input type="hidden" name="transaction_type_flags" id="transaction_type_flags" value="00000000">
        </div>
    </div>

    <!-- Lines -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-700 text-sm">Invoice Lines</h3>
                <p class="text-xs text-gray-400 mt-0.5">Select the revenue account for each line. AR account is assigned automatically from the customer.</p>
            </div>
            <button type="button" onclick="addLine()"
                class="text-xs bg-green-700 text-white px-3 py-1 rounded hover:bg-green-800">➕ Add Line</button>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                    <th class="px-3 py-2 text-left">Description</th>
                    <th class="px-3 py-2 text-left w-48">Revenue Account</th>
                    <th class="px-3 py-2 text-right w-24">Qty</th>
                    <th class="px-3 py-2 text-right w-24">UOM</th>
                    <th class="px-3 py-2 text-right w-36">Unit Price</th>
                    <th class="px-3 py-2 text-right w-20">VAT %</th>
                    <th class="px-3 py-2 text-right w-36">Tax Cat.</th>
                    <th class="px-3 py-2 text-right w-28">VAT Amt</th>
                    <th class="px-3 py-2 text-right w-28">Total</th>
                    <th class="px-3 py-2 w-8"></th>
                </tr>
            </thead>
            <tbody id="lines-body"></tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t border-gray-200">
                    <td colspan="5" class="px-3 py-2 text-right text-xs text-gray-500">Subtotal</td>
                    <td></td>
                    <td></td>
                    <td class="px-3 py-2 text-right" id="total-vat">0.00</td>
                    <td class="px-3 py-2 text-right font-semibold" id="total-subtotal">0.00</td>
                    <td></td>
                </tr>
                <tr class="bg-gray-50 font-bold">
                    <td colspan="8" class="px-3 py-2 text-right text-sm">TOTAL (AED)</td>
                    <td class="px-3 py-2 text-right text-green-700" id="total-amount">0.00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Actions -->
    <div class="flex gap-3">
        <button type="submit"
            class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
            Save as Draft
        </button>
        <a href="/invoices" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
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

const presetServices = [
    @foreach($presetServices as $svc)
    "{{ addslashes($svc) }}",
    @endforeach
];

// Stored revenue account from customer selection
let defaultRevenueAccountId = null;

async function fetchCustomerDetails(customerId) {
    if (!customerId) {
        document.getElementById('customer-info').textContent = '';
        defaultRevenueAccountId = null;
        return;
    }
    try {
        const res  = await fetch(`/customers/${customerId}/details`);
        const data = await res.json();
        const info = document.getElementById('customer-info');
        if (data.client_number) {
            info.textContent = `Client #${String(data.client_number).padStart(2,'0')} - ${data.client_acronym}`;
        }
        if (data.revenue_account) {
            defaultRevenueAccountId = data.revenue_account.id;
            // Update all existing line account selects to this revenue account
            document.querySelectorAll('[id^="line-"]').forEach(row => {
                const i   = row.id.replace('line-', '');
                const sel = document.querySelector(`[name="accounts[${i}]"]`);
                if (sel && (!sel.value || sel.value == '')) {
                    sel.value = defaultRevenueAccountId;
                }
            });
        }
    } catch(e) {
        console.error('Error fetching customer details', e);
    }
}

function buildAccountSelect(index, selectedId = null) {
    const useId = selectedId ?? defaultRevenueAccountId;
    let html = `<select name="accounts[${index}]" class="w-full border border-gray-200 rounded px-2 py-1 text-xs" onchange="updateTotals()">`;
    html += `<option value="">- Account -</option>`;
    for (const [type, accounts] of Object.entries(accountGroups)) {
        html += `<optgroup label="${typeLabels[type] || type}">`;
        accounts.forEach(a => {
            const sel = useId == a.id ? 'selected' : '';
            html += `<option value="${a.id}" ${sel}>${a.code} - ${a.name}</option>`;
        });
        html += `</optgroup>`;
    }
    html += `</select>`;
    return html;
}

function buildDescriptionField(index) {
    return `
        <div class="flex gap-1">
            <input type="text" name="descriptions[${index}]" id="desc-${index}"
                placeholder="Enter or select service..."
                class="flex-1 border border-gray-200 rounded px-2 py-1 text-sm">
            <select onchange="applyPreset(${index}, this)" 
                class="border border-gray-200 rounded px-1 py-1 text-xs text-gray-500 w-6"
                title="Select preset service">
                <option value="">▾</option>
                ${presetServices.map(s => `<option value="${s}">${s}</option>`).join('')}
            </select>
        </div>`;
}

function applyPreset(index, select) {
    if (!select.value) return;
    document.getElementById(`desc-${index}`).value = select.value;
    select.value = ''; // reset dropdown
}

let lineCount = 0;

function addLine() {
    const tbody = document.getElementById('lines-body');
    const i = lineCount++;
    const tr = document.createElement('tr');
    tr.className = 'border-b border-gray-50 hover:bg-gray-50';
    tr.id = `line-${i}`;
    tr.innerHTML = `
        <td class="px-3 py-2">${buildDescriptionField(i)}</td>
        <td class="px-3 py-2">${buildAccountSelect(i)}</td>
        <td class="px-3 py-2">
            <input type="number" name="quantities[${i}]" value="1" min="0" step="0.01"
                class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right"
                onchange="updateTotals()">
        </td>
        <td class="px-3 py-2">
            <select name="uom[${i}]" class="w-full border border-gray-200 rounded px-2 py-1 text-xs">
                <option value="PCE">PCE</option>
                <option value="HUR">HUR</option>
                <option value="DAY">DAY</option>
                <option value="MON">MON</option>
                <option value="KGM">KGM</option>
                <option value="MTR">MTR</option>
                <option value="LS">LS</option>
            </select>
        </td>
        <td class="px-3 py-2">
            <input type="number" name="unit_prices[${i}]" value="0.00" min="0" step="0.01"
                class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right"
                onchange="updateTotals()" onfocus="if(this.value=='0.00')this.value=''">
        </td>
        <td class="px-3 py-2">
            <select name="vat_rates[${i}]" class="w-full border border-gray-200 rounded px-2 py-1 text-xs" onchange="syncTaxCategory(${i}); updateTotals()">
                <option value="0">0%</option>
                <option value="5" selected>5%</option>
            </select>
        </td>
        <td class="px-3 py-2">
            <select name="tax_category[${i}]" id="taxcat-${i}" class="w-full border border-gray-200 rounded px-2 py-1 text-xs" onchange="syncExemptReason(${i})">
                <option value="S" selected>S – Standard</option>
                <option value="Z">Z – Zero-rated</option>
                <option value="E">E – Exempt</option>
                <option value="O">O – Out of Scope</option>
            </select>
            <input type="hidden" name="tax_exemption_reason[${i}]" id="exemption-${i}" value="">
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
        subtotal += lineAmount;
        totalVat += vatAmt;
        const vatEl = document.getElementById(`vat-${i}`);
        const totEl = document.getElementById(`linetotal-${i}`);
        if (vatEl) vatEl.textContent  = vatAmt.toFixed(2);
        if (totEl) totEl.textContent  = (lineAmount + vatAmt).toFixed(2);
    });
    document.getElementById('total-subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('total-vat').textContent      = totalVat.toFixed(2);
    document.getElementById('total-amount').textContent   = (subtotal + totalVat).toFixed(2);
}

function syncTaxCategory(i) {
    const vatSel = document.querySelector(`[name="vat_rates[${i}]"]`);
    const catSel = document.getElementById(`taxcat-${i}`);
    if (!vatSel || !catSel) return;
    const rate = parseFloat(vatSel.value);
    if (rate === 5) catSel.value = 'S';
    else if (rate === 0) catSel.value = 'Z';
    syncExemptReason(i);
}

function syncExemptReason(i) {
    const catSel = document.getElementById(`taxcat-${i}`);
    const exemp  = document.getElementById(`exemption-${i}`);
    if (!catSel || !exemp) return;
    if (catSel.value === 'E') {
        exemp.value = 'VATEX-AE-ABU';
    } else {
        exemp.value = '';
    }
}

function buildTxnFlags() {
    const flags = ['0','0','0','0','0','0','0','0'];
    document.querySelectorAll('[name^="txn_flags["]').forEach(cb => {
        const pos = parseInt(cb.name.match(/\[(\d+)\]/)[1]);
        if (cb.checked) flags[pos] = '1';
    });
    document.getElementById('transaction_type_flags').value = flags.join('');
}

// Init - if customer pre-selected, fetch details
@if(isset($selectedCustomer))
fetchCustomerDetails({{ $selectedCustomer->id }});
@endif

addLine();
</script>

@endsection