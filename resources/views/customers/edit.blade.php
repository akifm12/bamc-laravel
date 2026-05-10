@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')

<div class="max-w-3xl">

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded text-sm">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<form method="POST" action="/customers/{{ $customer->id }}" class="space-y-4">
    @csrf
    @method('PUT')

    <!-- Basic Info -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Basic Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Customer Name *</label>
                <input type="text" name="name" value="{{ old('name', $customer->name) }}" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Arabic Name</label>
                <input type="text" name="name_arabic" value="{{ old('name_arabic', $customer->name_arabic) }}"
                    dir="rtl" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">TRN</label>
                <input type="text" name="trn" value="{{ old('trn', $customer->trn) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="flex items-center gap-2 mt-4">
                <input type="checkbox" name="is_vat_registered" id="is_vat_registered"
                    {{ $customer->is_vat_registered ? 'checked' : '' }} class="rounded">
                <label for="is_vat_registered" class="text-sm text-gray-600">VAT Registered</label>
            </div>
        </div>
    </div>

    <!-- Contact -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Contact Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Contact Person</label>
                <input type="text" name="contact_person" value="{{ old('contact_person', $customer->contact_person) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Mobile</label>
                <input type="text" name="mobile" value="{{ old('mobile', $customer->mobile) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <!-- Address -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Address</h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="text-xs text-gray-500 block mb-1">Address Line 1</label>
                <input type="text" name="address_line1" value="{{ old('address_line1', $customer->address_line1) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="col-span-2">
                <label class="text-xs text-gray-500 block mb-1">Address Line 2</label>
                <input type="text" name="address_line2" value="{{ old('address_line2', $customer->address_line2) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">City</label>
                <input type="text" name="city" value="{{ old('city', $customer->city) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Emirate</label>
                <select name="emirate" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">— Select —</option>
                    @foreach($emirates as $e)
                        <option value="{{ $e }}" {{ $customer->emirate == $e ? 'selected' : '' }}>{{ $e }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Country</label>
                <input type="text" name="country" value="{{ old('country', $customer->country ?? 'UAE') }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <!-- Accounting -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Accounting Settings</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">AR Account</label>
                <select name="ar_account_id" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">— Select —</option>
                    @foreach($accounts as $a)
                        <option value="{{ $a->id }}" {{ $customer->ar_account_id == $a->id ? 'selected' : '' }}>
                            {{ $a->code }} — {{ $a->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Payment Terms (days)</label>
                <input type="number" name="payment_terms_days"
                    value="{{ old('payment_terms_days', $customer->payment_terms_days ?? 30) }}"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Credit Limit (AED)</label>
                <input type="number" name="credit_limit"
                    value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}"
                    step="0.01" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
        <div class="mt-4">
            <label class="text-xs text-gray-500 block mb-1">Notes</label>
            <textarea name="notes" rows="2"
                class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">{{ old('notes', $customer->notes) }}</textarea>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
            Save Changes
        </button>
        <a href="/customers/{{ $customer->id }}" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
    </div>

</form>
</div>

@endsection