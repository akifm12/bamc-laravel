@extends('layouts.app')

@section('title', 'Run Payroll')

@section('content')

<div class="max-w-2xl">

<form method="POST" action="/payroll/run" class="space-y-4">
    @csrf

    <!-- Period -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Payroll Period</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Pay Period Start *</label>
                <input type="date" name="pay_period_start"
                    value="{{ date('Y-m-01') }}" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Pay Period End *</label>
                <input type="date" name="pay_period_end"
                    value="{{ date('Y-m-t') }}" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Pay Date *</label>
                <input type="date" name="pay_date"
                    value="{{ date('Y-m-t') }}" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <!-- Journal Accounts -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-1">Journal Entry Accounts</h3>
        <p class="text-xs text-gray-400 mb-4">Optional - if provided, a journal entry will be auto-created.</p>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Salary Expense Account</label>
                <select name="salary_expense_account" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">- Select -</option>
                    @foreach($accounts->get('EXPENSE', collect()) as $a)
                        <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Salary Payable Account</label>
                <select name="salary_payable_account" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">- Select -</option>
                    @foreach($accounts->get('LIABILITY', collect()) as $a)
                        <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Employee Preview -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between">
            <span class="font-semibold text-gray-700 text-sm">Employees to be paid ({{ count($employees) }})</span>
            <span class="font-semibold text-green-700 text-sm">
                Total: AED {{ number_format($employees->sum('gross_salary'), 2) }}
            </span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                    <th class="px-4 py-2 text-left">Employee</th>
                    <th class="px-4 py-2 text-left">Designation</th>
                    <th class="px-4 py-2 text-right">Basic</th>
                    <th class="px-4 py-2 text-right">Allowances</th>
                    <th class="px-4 py-2 text-right">Gross</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                <tr class="border-b border-gray-50">
                    <td class="px-4 py-2">
                        <p class="font-medium text-gray-800">{{ $emp->full_name }}</p>
                        <p class="text-xs text-gray-400">{{ $emp->employee_id }}</p>
                    </td>
                    <td class="px-4 py-2 text-gray-600">{{ $emp->designation ?? '-' }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($emp->basic_salary, 2) }}</td>
                    <td class="px-4 py-2 text-right text-gray-500">
                        {{ number_format($emp->housing_allowance + $emp->transport_allowance + $emp->other_allowances, 2) }}
                    </td>
                    <td class="px-4 py-2 text-right font-semibold">{{ number_format($emp->gross_salary, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                        No active employees. <a href="/payroll/employees/create" class="text-green-600">Add employees first →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($employees->count() > 0)
    <div class="flex gap-3">
        <button type="submit"
            onclick="return confirm('Process payroll for {{ $employees->count() }} employees?')"
            class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
            ▶ Process Payroll
        </button>
        <a href="/payroll" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
    </div>
    @endif

</form>
</div>

@endsection