@extends('layouts.app')

@section('title', 'Payroll Run - ' . $run->run_number)

@section('content')

<div class="max-w-5xl">

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $run->run_number }}</h2>
                <p class="text-gray-500 text-sm mt-1">Period: {{ $run->pay_period_start }} - {{ $run->pay_period_end }}</p>
                <p class="text-gray-500 text-sm">Pay Date: {{ $run->pay_date }}</p>
            </div>
            <div class="text-right">
                @php
                    $statusColor = match(strtolower($run->status)) {
                        'posted' => 'bg-green-100 text-green-700',
                        'draft'  => 'bg-yellow-100 text-yellow-700',
                        default  => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <span class="text-xs px-3 py-1 rounded-full {{ $statusColor }}">{{ strtoupper($run->status) }}</span>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-4 gap-4 mb-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Employees</p>
            <p class="text-xl font-bold text-gray-800">{{ $run->employee_count }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Total Basic</p>
            <p class="text-xl font-bold text-gray-800">AED {{ number_format($run->total_basic_salary, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Total Allowances</p>
            <p class="text-xl font-bold text-gray-800">AED {{ number_format($run->total_allowances, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Total Net Pay</p>
            <p class="text-xl font-bold text-green-700">AED {{ number_format($run->total_net_pay, 2) }}</p>
        </div>
    </div>

    <!-- Payroll Lines -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <span class="font-semibold text-gray-700 text-sm">Payroll Detail</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                    <th class="px-4 py-2 text-left">Employee</th>
                    <th class="px-4 py-2 text-left">Bank / IBAN</th>
                    <th class="px-4 py-2 text-right">Basic</th>
                    <th class="px-4 py-2 text-right">Housing</th>
                    <th class="px-4 py-2 text-right">Transport</th>
                    <th class="px-4 py-2 text-right">Other</th>
                    <th class="px-4 py-2 text-right">Gross</th>
                    <th class="px-4 py-2 text-right">Deductions</th>
                    <th class="px-4 py-2 text-right">Net Pay</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $line)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2">
                        <p class="font-medium text-gray-800">{{ $line->full_name }}</p>
                        <p class="text-xs text-gray-400">{{ $line->emp_code }} - {{ $line->designation ?? '' }}</p>
                    </td>
                    <td class="px-4 py-2">
                        <p class="text-xs text-gray-600">{{ $line->bank_name ?? '-' }}</p>
                        <p class="text-xs text-gray-400">{{ $line->iban ?? '-' }}</p>
                    </td>
                    <td class="px-4 py-2 text-right">{{ number_format($line->basic_salary, 2) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($line->housing_allowance, 2) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($line->transport_allowance, 2) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($line->other_allowances, 2) }}</td>
                    <td class="px-4 py-2 text-right font-medium">{{ number_format($line->gross_pay, 2) }}</td>
                    <td class="px-4 py-2 text-right text-red-500">{{ number_format($line->total_deductions, 2) }}</td>
                    <td class="px-4 py-2 text-right font-bold text-green-700">{{ number_format($line->net_pay, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t border-gray-200 font-semibold">
                    <td colspan="6" class="px-4 py-2 text-right text-xs text-gray-500">TOTALS</td>
                    <td class="px-4 py-2 text-right">AED {{ number_format($lines->sum('gross_pay'), 2) }}</td>
                    <td class="px-4 py-2 text-right text-red-500">AED {{ number_format($lines->sum('total_deductions'), 2) }}</td>
                    <td class="px-4 py-2 text-right text-green-700">AED {{ number_format($lines->sum('net_pay'), 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <a href="/payroll" class="text-sm text-gray-500 hover:text-gray-700">← Back to Payroll</a>

</div>

@endsection