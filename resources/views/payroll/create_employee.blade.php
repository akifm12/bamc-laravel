@extends('layouts.app')

@section('title', 'New Employee')

@section('content')

<div class="max-w-3xl">

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded text-sm">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<form method="POST" action="/payroll/employees" class="space-y-4">
    @csrf

    <!-- Personal Info -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Personal Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">First Name *</label>
                <input type="text" name="first_name" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Last Name *</label>
                <input type="text" name="last_name" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Nationality</label>
                <input type="text" name="nationality"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Date of Birth</label>
                <input type="date" name="date_of_birth"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Gender</label>
                <select name="gender" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">— Select —</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Emirates ID</label>
                <input type="text" name="emirates_id" placeholder="784-XXXX-XXXXXXX-X"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Passport No.</label>
                <input type="text" name="passport_no"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Visa No.</label>
                <input type="text" name="visa_no"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Visa Expiry</label>
                <input type="date" name="visa_expiry"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Labour Card No.</label>
                <input type="text" name="labour_card_no"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <!-- Employment Info -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Employment Details</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Designation</label>
                <input type="text" name="designation"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Department</label>
                <select name="department_id" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">— Select —</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Join Date *</label>
                <input type="date" name="join_date" value="{{ date('Y-m-d') }}" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Payment Frequency</label>
                <select name="payment_frequency" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="monthly">Monthly</option>
                    <option value="weekly">Weekly</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Salary -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Salary Details</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Basic Salary (AED) *</label>
                <input type="number" name="basic_salary" step="0.01" required
                    id="basic_salary" oninput="calcGross()"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Housing Allowance (AED)</label>
                <input type="number" name="housing_allowance" value="0" step="0.01"
                    id="housing" oninput="calcGross()"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Transport Allowance (AED)</label>
                <input type="number" name="transport_allowance" value="0" step="0.01"
                    id="transport" oninput="calcGross()"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Other Allowances (AED)</label>
                <input type="number" name="other_allowances" value="0" step="0.01"
                    id="other" oninput="calcGross()"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="col-span-2 bg-green-50 border border-green-200 rounded p-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-700">Gross Salary</span>
                    <span id="gross_display" class="text-xl font-bold text-green-700">AED 0.00</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bank Details -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Bank & WPS Details</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Bank Name</label>
                <input type="text" name="bank_name"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Bank Account No.</label>
                <input type="text" name="bank_account_no"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">IBAN</label>
                <input type="text" name="iban" placeholder="AE..."
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">WPS Routing Code</label>
                <input type="text" name="wps_routing_code"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
            Create Employee
        </button>
        <a href="/payroll/employees" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
    </div>

</form>
</div>

<script>
function calcGross() {
    const basic    = parseFloat(document.getElementById('basic_salary').value) || 0;
    const housing  = parseFloat(document.getElementById('housing').value) || 0;
    const transport= parseFloat(document.getElementById('transport').value) || 0;
    const other    = parseFloat(document.getElementById('other').value) || 0;
    const gross    = basic + housing + transport + other;
    document.getElementById('gross_display').textContent = 'AED ' + gross.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

@endsection