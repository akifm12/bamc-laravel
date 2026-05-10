@extends('layouts.app')

@section('title', 'New Vendor')

@section('content')

<div class="max-w-3xl">

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded text-sm">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<form method="POST" action="/vendors" class="space-y-4">
    @csrf

    <!-- Basic Info -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Basic Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Vendor Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Vendor Code *</label>
                <input type="text" name="code" value="{{ old('code') }}" required
                    placeholder="VEND-001"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Arabic Name</label>
                <input type="text" name="name_arabic" value="{{ old('name_arabic') }}" dir="rtl"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">TRN</label>
                <input type="text" name="trn" value="{{ old('trn') }}" placeholder="100XXXXXXXXX00003"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="flex items-center gap-4 mt-2">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_vat_registered" id="is_vat_registered" class="rounded">
                    <label for="is_vat_registered" class="text-sm text-gray-600">VAT Registered</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_foreign_vendor" id="is_foreign_vendor" class="rounded">
                    <label for="is_foreign_vendor" class="text-sm text-gray-600">Foreign Vendor</label>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Contact Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Contact Person</label>
                <input type="text" name="contact_person" value="{{ old('contact_person') }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone') }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Address</label>
                <input type="text" name="address_line1" value="{{ old('address_line1') }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">City</label>
                <input type="text" name="city" value="{{ old('city') }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Emirate</label>
                <select name="emirate" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">— Select —</option>
                    @foreach($emirates as $e)
                        <option value="{{ $e }}" {{ old('emirate') == $e ? 'selected' : '' }}>{{ $e }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Country</label>
                <input type="text" name="country" value="{{ old('country', 'UAE') }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <!-- Bank Details -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Bank Details</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Bank Name</label>
                <input type="text" name="bank_name" value="{{ old('bank_name') }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Account Number</label>
                <input type="text" name="bank_account_no" value="{{ old('bank_account_no') }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">IBAN</label>
                <input type="text" name="iban" value="{{ old('iban') }}"
                    placeholder="AE..."
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">SWIFT Code</label>
                <input type="text" name="swift_code" value="{{ old('swift_code') }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <!-- Accounting -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Accounting Settings</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">AP Account</label>
                <select name="ap_account_id" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">— Select —</option>
                    @foreach($accounts as $a)
                        <option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Payment Terms (days)</label>
                <input type="number" name="payment_terms_days" value="{{ old('payment_terms_days', 30) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
        <div class="mt-4">
            <label class="text-xs text-gray-500 block mb-1">Notes</label>
            <textarea name="notes" rows="2"
                class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">{{ old('notes') }}</textarea>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
            Create Vendor
        </button>
        <a href="/vendors" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
    </div>

</form>
</div>

@endsection