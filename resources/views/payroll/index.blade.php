@extends('layouts.app')

@section('title', 'Payroll')

@section('content')

<div class="mb-4 flex items-center justify-between">
    <div class="flex gap-3">
        <a href="/payroll/employees" class="border border-gray-200 text-gray-600 text-sm px-4 py-2 rounded hover:bg-gray-50">👥 Employees</a>
        <a href="/payroll/run" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">▶ Run Payroll</a>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-3 gap-4 mb-4">
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">Active Employees</p>
        <p class="text-xl font-bold text-gray-800">{{ $totalEmployees }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">Payroll Runs</p>
        <p class="text-xl font-bold text-gray-800">{{ count($runs) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">Total Net Pay (All Time)</p>
        <p class="text-xl font-bold text-gray-800">AED {{ number_format($runs->sum('total_net_pay'), 2) }}</p>
    </div>
</div>

<!-- Payroll Runs -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
        <span class="font-semibold text-gray-700 text-sm">Payroll Runs</span>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                <th class="px-4 py-2 text-left">Run No.</th>
                <th class="px-4 py-2 text-left">Period</th>
                <th class="px-4 py-2 text-left">Pay Date</th>
                <th class="px-4 py-2 text-center">Employees</th>
                <th class="px-4 py-2 text-right">Total Basic</th>
                <th class="px-4 py-2 text-right">Total Net Pay</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($runs as $run)
            @php
                $statusColor = match(strtolower($run->status)) {
                    'posted' => 'bg-green-100 text-green-700',
                    'draft'  => 'bg-yellow-100 text-yellow-700',
                    default  => 'bg-gray-100 text-gray-500',
                };
            @endphp
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $run->run_number }}</td>
                <td class="px-4 py-2 text-gray-600">{{ $run->pay_period_start }} - {{ $run->pay_period_end }}</td>
                <td class="px-4 py-2 text-gray-600">{{ $run->pay_date }}</td>
                <td class="px-4 py-2 text-center text-gray-600">{{ $run->employee_count }}</td>
                <td class="px-4 py-2 text-right">{{ number_format($run->total_basic_salary, 2) }}</td>
                <td class="px-4 py-2 text-right font-semibold">AED {{ number_format($run->total_net_pay, 2) }}</td>
                <td class="px-4 py-2 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $statusColor }}">{{ strtoupper($run->status) }}</span>
                </td>
                <td class="px-4 py-2 text-right">
                    <a href="/payroll/{{ $run->id }}" class="text-xs text-blue-500 hover:text-blue-700">View →</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                    No payroll runs yet. <a href="/payroll/run" class="text-green-600">Run payroll →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection