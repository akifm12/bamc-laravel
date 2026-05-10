@extends('layouts.app')

@section('title', 'VAT Returns')

@section('content')

<div class="mb-4 flex items-center justify-between">
    <p class="text-sm text-gray-500">{{ count($returns) }} VAT returns</p>
    <a href="/vat/create" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">➕ New VAT Return</a>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-200 bg-gray-50">
                <th class="px-4 py-2 text-left">Return No.</th>
                <th class="px-4 py-2 text-left">Period</th>
                <th class="px-4 py-2 text-left">Due Date</th>
                <th class="px-4 py-2 text-right">Output VAT</th>
                <th class="px-4 py-2 text-right">Input VAT</th>
                <th class="px-4 py-2 text-right">Net Payable</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($returns as $r)
            @php
                $statusColor = match(strtolower($r->status)) {
                    'submitted' => 'bg-green-100 text-green-700',
                    'draft'     => 'bg-yellow-100 text-yellow-700',
                    'paid'      => 'bg-blue-100 text-blue-700',
                    default     => 'bg-gray-100 text-gray-500',
                };
            @endphp
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 font-mono text-xs">{{ $r->return_number }}</td>
                <td class="px-4 py-2 text-gray-600">{{ $r->period_from }} — {{ $r->period_to }}</td>
                <td class="px-4 py-2 text-gray-600">{{ $r->due_date ?? '—' }}</td>
                <td class="px-4 py-2 text-right">{{ number_format($r->box6_total_output_tax, 2) }}</td>
                <td class="px-4 py-2 text-right">{{ number_format($r->box9_total_input_tax, 2) }}</td>
                <td class="px-4 py-2 text-right font-semibold {{ $r->box13_net_payable > 0 ? 'text-red-600' : 'text-green-600' }}">
                    AED {{ number_format($r->box13_net_payable, 2) }}
                </td>
                <td class="px-4 py-2 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $statusColor }}">{{ strtoupper($r->status) }}</span>
                </td>
                <td class="px-4 py-2 text-right">
                    <a href="/vat/{{ $r->id }}" class="text-xs text-blue-500 hover:text-blue-700">View →</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                    No VAT returns yet. <a href="/vat/create" class="text-green-600">Create one →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection