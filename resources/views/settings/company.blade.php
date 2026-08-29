@extends('layouts.app')

@section('title', 'Company Setup')

@section('content')

<div class="max-w-3xl">

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded text-sm">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<form method="POST" action="/settings/company/{{ $company->id }}" class="space-y-4" enctype="multipart/form-data">
    @csrf

    <!-- Basic Info -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Company Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Company Name *</label>
                <input type="text" name="name" value="{{ old('name', $company->name) }}" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Arabic Name</label>
                <input type="text" name="name_arabic" value="{{ old('name_arabic', $company->name_arabic) }}"
                    dir="rtl" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">TRN</label>
                <input type="text" name="trn" value="{{ old('trn', $company->trn) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Trade License No.</label>
                <input type="text" name="trade_license_no" value="{{ old('trade_license_no', $company->trade_license_no) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">VAT Registration Date</label>
                <input type="date" name="vat_registration_date"
                    value="{{ old('vat_registration_date', $company->vat_registration_date) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">VAT Scheme</label>
                <select name="vat_scheme" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="standard" {{ $company->vat_scheme == 'standard' ? 'selected' : '' }}>Standard</option>
                    <option value="cash" {{ $company->vat_scheme == 'cash' ? 'selected' : '' }}>Cash Accounting</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">VAT Filing Cycle <span class="text-gray-400">(quarterly start month)</span></label>
                <select name="vat_quarter_start_month" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="1" {{ ($company->vat_quarter_start_month ?? 1) == 1 ? 'selected' : '' }}>Cycle 1 - Jan, Apr, Jul, Oct</option>
                    <option value="2" {{ ($company->vat_quarter_start_month ?? 1) == 2 ? 'selected' : '' }}>Cycle 2 - Feb, May, Aug, Nov</option>
                    <option value="3" {{ ($company->vat_quarter_start_month ?? 1) == 3 ? 'selected' : '' }}>Cycle 3 - Mar, Jun, Sep, Dec</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Based on your FTA registration - sets the 3-month filing periods.</p>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Default VAT Rate (%)</label>
                <input type="number" name="default_vat_rate"
                    value="{{ old('default_vat_rate', $company->default_vat_rate ?? 5) }}"
                    step="0.01" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <!-- Contact & Address -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Contact & Address</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $company->email) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $company->phone) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Website</label>
                <input type="text" name="website" value="{{ old('website', $company->website) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">PO Box</label>
                <input type="text" name="po_box" value="{{ old('po_box', $company->po_box) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="col-span-2">
                <label class="text-xs text-gray-500 block mb-1">Address Line 1</label>
                <input type="text" name="address_line1" value="{{ old('address_line1', $company->address_line1) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="col-span-2">
                <label class="text-xs text-gray-500 block mb-1">Address Line 2</label>
                <input type="text" name="address_line2" value="{{ old('address_line2', $company->address_line2) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">City</label>
                <input type="text" name="city" value="{{ old('city', $company->city) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Emirate</label>
                <select name="emirate" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">- Select -</option>
                    @foreach($emirates as $e)
                        <option value="{{ $e }}" {{ $company->emirate == $e ? 'selected' : '' }}>{{ $e }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Company Logo -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Company Logo</h3>
        <div class="flex items-start gap-6">
            @if($company->logo_path)
            <div>
                <img src="{{ asset($company->logo_path) }}" alt="Current Logo" class="h-16 object-contain border border-gray-200 rounded p-1">
                <p class="text-xs text-gray-400 mt-1">Current logo</p>
            </div>
            @endif
            <div class="flex-1">
                <label class="text-xs text-gray-500 block mb-1">Upload Logo <span class="text-gray-400">(PNG, JPG - max 2MB)</span></label>
                <input type="file" name="logo" accept="image/*"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm text-gray-600 file:mr-3 file:border-0 file:bg-gray-100 file:text-xs file:px-3 file:py-1 file:rounded">
                <p class="text-xs text-gray-400 mt-1">Leave blank to keep the existing logo. Uploading a new file will replace it.</p>
            </div>
        </div>
    </div>

    <!-- Banking Details -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Banking Details <span class="font-normal text-gray-400">(shown on invoice PDFs)</span></h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="text-xs text-gray-500 block mb-1">Bank Name</label>
                <input type="text" name="bank_name" value="{{ old('bank_name', $company->bank_name) }}"
                    placeholder="e.g. National Bank of Ras Al Khaimah"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="col-span-2">
                <label class="text-xs text-gray-500 block mb-1">Account Title</label>
                <input type="text" name="bank_account_title" value="{{ old('bank_account_title', $company->bank_account_title) }}"
                    placeholder="e.g. Blue Arrow Management Consultants FZC"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Account Number</label>
                <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $company->bank_account_number) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">IBAN</label>
                <input type="text" name="bank_iban" value="{{ old('bank_iban', $company->bank_iban) }}"
                    placeholder="e.g. AE700400000293106433001"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">SWIFT Code</label>
                <input type="text" name="bank_swift" value="{{ old('bank_swift', $company->bank_swift) }}"
                    placeholder="e.g. NRAKAEAK"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <!-- Default Accounts -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Default Accounts</h3>
        <div class="grid grid-cols-2 gap-4">
            @foreach([
                ['default_ar_account_id', 'Default AR Account', 'ASSET'],
                ['default_ap_account_id', 'Default AP Account', 'LIABILITY'],
                ['default_vat_output_id', 'VAT Output Account', 'LIABILITY'],
                ['default_vat_input_id',  'VAT Input Account',  'ASSET'],
                ['default_retained_earnings_id', 'Retained Earnings', 'EQUITY'],
            ] as [$field, $label, $type])
            <div>
                <label class="text-xs text-gray-500 block mb-1">{{ $label }}</label>
                <select name="{{ $field }}" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">- Select -</option>
                    @foreach($accounts->where('account_type', $type) as $a)
                        <option value="{{ $a->id }}"
                            {{ $company->$field == $a->id ? 'selected' : '' }}>
                            {{ $a->code }} - {{ $a->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endforeach
        </div>
    </div>

    @if(auth()->user()->is_super_admin)
    <!-- E-Invoicing -->
    <div class="bg-white rounded-lg border border-gray-200 p-5" x-data="{ enabled: {{ $company->einvoicing_enabled ? 'true' : 'false' }} }">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-700 text-sm">E-Invoicing Integration
                <span class="ml-2 font-normal text-gray-400">(Super Admin only)</span>
            </h3>
            <label class="flex items-center gap-2 cursor-pointer">
                <span class="text-xs text-gray-500">Enable</span>
                <input type="hidden" name="einvoicing_enabled" value="0">
                <input type="checkbox" name="einvoicing_enabled" value="1" x-model="enabled"
                    {{ $company->einvoicing_enabled ? 'checked' : '' }}
                    class="w-4 h-4 text-green-600 border-gray-300 rounded">
            </label>
        </div>

        <div x-show="enabled" x-transition class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Provider</label>
                <select name="einvoicing_provider" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="wafeq" {{ ($company->einvoicing_provider ?? 'wafeq') === 'wafeq' ? 'selected' : '' }}>Wafeq</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Additional providers can be added as needed.</p>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Seller ID <span class="text-gray-400">(optional — Wafeq contact UUID)</span></label>
                <input type="text" name="einvoicing_seller_id"
                    value="{{ old('einvoicing_seller_id', $company->einvoicing_seller_id) }}"
                    placeholder="Leave blank to use your Wafeq account default"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="col-span-2">
                <label class="text-xs text-gray-500 block mb-1">
                    Wafeq Revenue Account Code
                    <span class="text-gray-400">— use the account code from your Wafeq Chart of Accounts (e.g. 411 = Sales)</span>
                </label>
                <input type="text" name="einvoicing_revenue_account_id"
                    value="{{ old('einvoicing_revenue_account_id', $company->einvoicing_revenue_account_id ?? '') }}"
                    placeholder="e.g. 411"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm font-mono">
                <p class="text-xs text-gray-400 mt-1">Used for all invoice line items submitted to Wafeq.</p>
            </div>
            <div class="col-span-2">
                <label class="text-xs text-gray-500 block mb-1">API Key</label>
                @if($company->einvoicing_api_key)
                <p class="text-xs text-green-600 mb-1">✅ Key saved on file — paste a new key below only if you want to replace it.</p>
                @endif
                <div class="flex gap-2">
                    <input type="password" name="einvoicing_api_key" id="einvoicing_api_key"
                        placeholder="{{ $company->einvoicing_api_key ? 'Enter new key to replace existing' : 'Paste API key here' }}"
                        autocomplete="new-password"
                        class="flex-1 border border-gray-200 rounded px-3 py-1.5 text-sm font-mono">
                    <button type="button" onclick="testEInvoiceConnection()" id="btn-test-conn"
                        class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700 whitespace-nowrap">
                        Test Connection
                    </button>
                </div>
                <p id="conn-result" class="text-xs mt-1 hidden"></p>
            </div>
        </div>
    </div>
    @endif

    <div class="flex gap-3">
        <button type="submit" class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
            Save Changes
        </button>
        <a href="/settings/fiscal-years" class="text-sm border border-gray-200 text-gray-600 px-4 py-2 rounded hover:bg-gray-50">
            📅 Manage Fiscal Years →
        </a>
    </div>
</form>
</div>

@push('scripts')
<script>
function testEInvoiceConnection() {
    const keyInput = document.getElementById('einvoicing_api_key');
    const result   = document.getElementById('conn-result');
    const btn      = document.getElementById('btn-test-conn');
    const key      = keyInput.value.trim();

    if (!key) {
        result.textContent = 'Enter an API key to test.';
        result.className   = 'text-xs mt-1 text-yellow-600';
        result.classList.remove('hidden');
        return;
    }

    btn.textContent = 'Testing…';
    btn.disabled    = true;

    fetch('/settings/company/test-einvoice', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body:    JSON.stringify({ api_key: key, provider: document.querySelector('[name=einvoicing_provider]').value }),
    })
    .then(r => r.json())
    .then(data => {
        result.className = 'text-xs mt-1 ' + (data.success ? 'text-green-600' : 'text-red-600');
        result.classList.remove('hidden');

        if (data.success && data.accounts && data.accounts.length) {
            const revenue = data.accounts.filter(a =>
                (a.account_type || a.type || '').toLowerCase().includes('revenue') ||
                (a.account_type || a.type || '').toLowerCase().includes('income') ||
                (a.name || '').toLowerCase().includes('sales') ||
                (a.name || '').toLowerCase().includes('revenue')
            );
            const list = (revenue.length ? revenue : data.accounts).slice(0, 5);
            result.innerHTML = '✅ Connected. Revenue accounts found:<br>' +
                list.map(a => `<code style="background:#f3f4f6;padding:1px 4px;border-radius:3px">${a.id}</code> — ${a.name} (${a.account_type || a.type || '?'})`).join('<br>') +
                '<br><span style="color:#6b7280">Paste one of these IDs into the Revenue Account Code field above.</span>';
        } else {
            result.textContent = data.message;
        }
    })
    .catch(() => {
        result.textContent = 'Network error — check console.';
        result.className   = 'text-xs mt-1 text-red-600';
        result.classList.remove('hidden');
    })
    .finally(() => { btn.textContent = 'Test Connection'; btn.disabled = false; });
}
</script>
@endpush

@endsection