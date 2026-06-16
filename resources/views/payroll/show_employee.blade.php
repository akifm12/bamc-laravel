@extends('layouts.app')

@section('title', $employee->full_name)

@section('content')

<div class="max-w-4xl">

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $employee->full_name }}</h2>
                <p class="text-gray-500 text-sm mt-1">{{ $employee->employee_id }} - {{ $employee->designation ?? 'No designation' }}</p>
            </div>
            @php
                $statusColor = match($employee->status) {
                    'active'     => 'bg-green-100 text-green-700',
                    'inactive'   => 'bg-gray-100 text-gray-500',
                    'terminated' => 'bg-red-100 text-red-600',
                    default      => 'bg-gray-100 text-gray-500',
                };
            @endphp
            <span class="text-xs px-3 py-1 rounded-full {{ $statusColor }}">{{ strtoupper($employee->status) }}</span>
        </div>
    </div>

    <!-- Salary KPIs -->
    <div class="grid grid-cols-4 gap-4 mb-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Basic Salary</p>
            <p class="text-lg font-bold text-gray-800">AED {{ number_format($employee->basic_salary, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Housing</p>
            <p class="text-lg font-bold text-gray-800">AED {{ number_format($employee->housing_allowance, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Transport</p>
            <p class="text-lg font-bold text-gray-800">AED {{ number_format($employee->transport_allowance, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Gross Salary</p>
            <p class="text-lg font-bold text-green-700">AED {{ number_format($employee->gross_salary, 2) }}</p>
        </div>
    </div>

    <!-- Details -->
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-3">Personal Details</h3>
            <div class="space-y-2 text-sm">
                @if($employee->nationality)
                    <p><span class="text-gray-400">Nationality:</span> {{ $employee->nationality }}</p>
                @endif
                @if($employee->emirates_id)
                    <p><span class="text-gray-400">Emirates ID:</span> {{ $employee->emirates_id }}</p>
                @endif
                @if($employee->passport_no)
                    <p><span class="text-gray-400">Passport:</span> {{ $employee->passport_no }}</p>
                @endif
                @if($employee->visa_no)
                    <p><span class="text-gray-400">Visa No.:</span> {{ $employee->visa_no }}</p>
                @endif
                @if($employee->visa_expiry)
                    <p><span class="text-gray-400">Visa Expiry:</span>
                        <span class="{{ $employee->visa_expiry < date('Y-m-d') ? 'text-red-600 font-semibold' : '' }}">
                            {{ $employee->visa_expiry }}
                        </span>
                    </p>
                @endif
                <p><span class="text-gray-400">Join Date:</span> {{ $employee->join_date }}</p>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-3">Bank Details</h3>
            <div class="space-y-2 text-sm">
                @if($employee->bank_name)
                    <p><span class="text-gray-400">Bank:</span> {{ $employee->bank_name }}</p>
                @endif
                @if($employee->bank_account_no)
                    <p><span class="text-gray-400">Account:</span> {{ $employee->bank_account_no }}</p>
                @endif
                @if($employee->iban)
                    <p><span class="text-gray-400">IBAN:</span> {{ $employee->iban }}</p>
                @endif
                @if($employee->wps_routing_code)
                    <p><span class="text-gray-400">WPS Code:</span> {{ $employee->wps_routing_code }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Payroll History -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">Payroll History (Last 12)</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                    <th class="px-4 py-2 text-left">Run</th>
                    <th class="px-4 py-2 text-left">Pay Date</th>
                    <th class="px-4 py-2 text-right">Basic</th>
                    <th class="px-4 py-2 text-right">Gross</th>
                    <th class="px-4 py-2 text-right">Deductions</th>
                    <th class="px-4 py-2 text-right">Net Pay</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrollLines as $line)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $line->run_number }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $line->pay_date }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($line->basic_salary, 2) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($line->gross_pay, 2) }}</td>
                    <td class="px-4 py-2 text-right text-red-500">{{ number_format($line->total_deductions, 2) }}</td>
                    <td class="px-4 py-2 text-right font-semibold text-green-700">{{ number_format($line->net_pay, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">No payroll history yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <a href="/payroll/employees" class="text-sm text-gray-500 hover:text-gray-700">← Back to Employees</a>

</div>

@endsection