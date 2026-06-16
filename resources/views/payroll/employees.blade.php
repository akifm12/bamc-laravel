@extends('layouts.app')

@section('title', 'Employees')

@section('content')

<div class="mb-4 flex items-center justify-between">
    <p class="text-sm text-gray-500">{{ count($employees) }} employees</p>
    <div class="flex gap-3">
        <a href="/payroll" class="border border-gray-200 text-gray-600 text-sm px-4 py-2 rounded hover:bg-gray-50">← Payroll</a>
        <a href="/payroll/employees/create" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">➕ New Employee</a>
    </div>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-200 bg-gray-50">
                <th class="px-4 py-2 text-left">ID</th>
                <th class="px-4 py-2 text-left">Name</th>
                <th class="px-4 py-2 text-left">Designation</th>
                <th class="px-4 py-2 text-left">Nationality</th>
                <th class="px-4 py-2 text-left">Join Date</th>
                <th class="px-4 py-2 text-right">Basic</th>
                <th class="px-4 py-2 text-right">Gross</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $emp)
            @php
                $statusColor = match($emp->status) {
                    'active'     => 'bg-green-100 text-green-700',
                    'inactive'   => 'bg-gray-100 text-gray-500',
                    'terminated' => 'bg-red-100 text-red-600',
                    default      => 'bg-gray-100 text-gray-500',
                };
            @endphp
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $emp->employee_id }}</td>
                <td class="px-4 py-2 font-medium text-gray-800">{{ $emp->full_name }}</td>
                <td class="px-4 py-2 text-gray-600">{{ $emp->designation ?? '-' }}</td>
                <td class="px-4 py-2 text-gray-600">{{ $emp->nationality ?? '-' }}</td>
                <td class="px-4 py-2 text-gray-600">{{ $emp->join_date }}</td>
                <td class="px-4 py-2 text-right">{{ number_format($emp->basic_salary, 2) }}</td>
                <td class="px-4 py-2 text-right font-semibold">{{ number_format($emp->gross_salary, 2) }}</td>
                <td class="px-4 py-2 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $statusColor }}">{{ strtoupper($emp->status) }}</span>
                </td>
                <td class="px-4 py-2 text-right">
                    <a href="/payroll/employees/{{ $emp->id }}" class="text-xs text-blue-500 hover:text-blue-700">View →</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                    No employees yet. <a href="/payroll/employees/create" class="text-green-600">Add one →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection