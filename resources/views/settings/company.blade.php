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

@endsection