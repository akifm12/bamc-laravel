@extends('layouts.app')

@section('title', 'New Director Payment')

@section('content')

<div class="max-w-xl">

    <div class="mb-4">
        <a href="/payroll?tab=directors" class="text-sm text-gray-500 hover:text-gray-700">← Back to Directors</a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-5">Director Remuneration / Bonus</h3>

        <form method="POST" action="/payroll/directors">
            @csrf

            @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded text-sm">
                {{ $errors->first() }}
            </div>
            @endif

            <!-- Director -->
            <div class="mb-4">
                <label class="block text-xs text-gray-500 mb-1">Director <span class="text-red-500">*</span></label>
                <select name="director_name" required
                    class="w-full border border-gray-200 rounded px-3 py-2 text-sm" id="directorSelect"
                    onchange="suggestAmount()">
                    <option value="">— Select Director —</option>
                    <option value="Akif Mushtaq" {{ old('director_name') === 'Akif Mushtaq' ? 'selected' : '' }}>Akif Mushtaq</option>
                    <option value="Amita Parulekar" {{ old('director_name') === 'Amita Parulekar' ? 'selected' : '' }}>Amita Parulekar</option>
                </select>
            </div>

            <!-- Type -->
            <div class="mb-4">
                <label class="block text-xs text-gray-500 mb-1">Payment Type <span class="text-red-500">*</span></label>
                <div class="flex gap-4 mt-1">
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="radio" name="payment_type" value="remuneration"
                            {{ old('payment_type', 'remuneration') === 'remuneration' ? 'checked' : '' }}
                            class="text-green-700" onchange="suggestAmount()">
                        Remuneration (monthly)
                    </label>
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="radio" name="payment_type" value="bonus"
                            {{ old('payment_type') === 'bonus' ? 'checked' : '' }}
                            class="text-green-700" onchange="suggestAmount()">
                        Bonus (ad-hoc)
                    </label>
                </div>
            </div>

            <!-- Amount -->
            <div class="mb-4">
                <label class="block text-xs text-gray-500 mb-1">Amount (AED) <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">AED</span>
                    <input type="number" name="amount" id="amountField" step="0.01" min="0.01"
                        value="{{ old('amount') }}" required
                        placeholder="0.00"
                        class="w-full border border-gray-200 rounded pl-12 pr-3 py-2 text-sm">
                </div>
                <p id="amountHint" class="text-xs text-gray-400 mt-1 hidden"></p>
            </div>

            <!-- Pay Date -->
            <div class="mb-4">
                <label class="block text-xs text-gray-500 mb-1">Pay Date <span class="text-red-500">*</span></label>
                <input type="date" name="pay_date" value="{{ old('pay_date', date('Y-m-d')) }}" required
                    class="w-full border border-gray-200 rounded px-3 py-2 text-sm">
            </div>

            <!-- Expense Account -->
            <div class="mb-4">
                <label class="block text-xs text-gray-500 mb-1">Expense Account <span class="text-red-500">*</span></label>
                <select name="expense_account_id" required class="w-full border border-gray-200 rounded px-3 py-2 text-sm">
                    @foreach($expenseAccounts as $acc)
                    <option value="{{ $acc->id }}"
                        {{ (old('expense_account_id', 726) == $acc->id) ? 'selected' : '' }}>
                        {{ $acc->code }} – {{ $acc->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Bank Account -->
            <div class="mb-4">
                <label class="block text-xs text-gray-500 mb-1">Pay From (Bank Account) <span class="text-red-500">*</span></label>
                <select name="bank_account_id" required class="w-full border border-gray-200 rounded px-3 py-2 text-sm">
                    @foreach($bankAccounts as $acc)
                    <option value="{{ $acc->id }}"
                        {{ (old('bank_account_id', 647) == $acc->id) ? 'selected' : '' }}>
                        {{ $acc->code }} – {{ $acc->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <label class="block text-xs text-gray-500 mb-1">Notes</label>
                <input type="text" name="notes" value="{{ old('notes') }}"
                    placeholder="e.g. October 2026 remuneration"
                    class="w-full border border-gray-200 rounded px-3 py-2 text-sm">
            </div>

            <div class="flex gap-3">
                <button type="submit"
                    class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
                    💾 Save as Draft
                </button>
                <a href="/payroll?tab=directors" class="border border-gray-200 text-gray-600 text-sm px-4 py-2 rounded hover:bg-gray-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Last remuneration amounts per director (PHP → JS)
const lastAmounts = @json($lastAmounts);

function suggestAmount() {
    const director = document.getElementById('directorSelect').value;
    const type     = document.querySelector('input[name="payment_type"]:checked')?.value;
    const hint     = document.getElementById('amountHint');
    const field    = document.getElementById('amountField');

    if (type === 'remuneration' && director && lastAmounts[director]) {
        const last = parseFloat(lastAmounts[director]).toFixed(2);
        hint.textContent = 'Last remuneration: AED ' + Number(last).toLocaleString('en-AE', {minimumFractionDigits: 2});
        hint.classList.remove('hidden');
        if (!field.value) field.value = last;
    } else {
        hint.classList.add('hidden');
    }
}
</script>
@endpush

@endsection
