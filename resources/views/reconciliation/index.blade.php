@extends('layouts.app')
@section('title', 'Bank Reconciliation')
@section('content')

<div class="flex items-center justify-between mb-5">
    <div>
        <p class="text-xs text-gray-400 mt-0.5">Match your bank statement to journal entries, period by period.</p>
    </div>
    <a href="/reconciliation/create" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">
        ➕ New Reconciliation
    </a>
</div>

@if($reconciliations->isEmpty())
<div class="bg-white rounded-lg border border-gray-200 px-6 py-12 text-center">
    <div class="text-4xl mb-3">🏦</div>
    <p class="text-gray-500 text-sm mb-4">No reconciliations yet. Start by uploading your bank statement CSV.</p>
    <a href="/reconciliation/create" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">
        Start First Reconciliation
    </a>
</div>
@else
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-200 bg-gray-50">
                <th class="px-4 py-2 text-left">Account</th>
                <th class="px-4 py-2 text-left">Period</th>
                <th class="px-4 py-2 text-right">Statement Closing</th>
                <th class="px-4 py-2 text-center">Lines</th>
                <th class="px-4 py-2 text-center">Matched</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($reconciliations as $r)
            @php
                $pct = $r->total_lines > 0 ? round($r->matched_lines / $r->total_lines * 100) : 0;
                $statusColor = $r->status === 'LOCKED'
                    ? 'bg-green-100 text-green-700'
                    : ($pct == 100 ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700');
            @endphp
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-700">{{ $r->account_name }}</td>
                <td class="px-4 py-3 text-xs text-gray-500">
                    {{ \Carbon\Carbon::parse($r->period_from)->format('d M Y') }}
                    – {{ \Carbon\Carbon::parse($r->period_to)->format('d M Y') }}
                </td>
                <td class="px-4 py-3 text-right font-medium text-gray-700">
                    AED {{ number_format($r->closing_balance_statement, 2) }}
                </td>
                <td class="px-4 py-3 text-center text-gray-500">{{ $r->total_lines }}</td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center gap-2 justify-center">
                        <div class="w-20 bg-gray-200 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full {{ $pct == 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                                style="width:{{ $pct }}%"></div>
                        </div>
                        <span class="text-xs text-gray-500">{{ $r->matched_lines }}/{{ $r->total_lines }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $statusColor }}">
                        {{ $r->status === 'LOCKED' ? '🔒 Locked' : ($pct == 100 ? 'Ready to lock' : 'In Progress') }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="/reconciliation/{{ $r->id }}" class="text-xs text-blue-500 hover:text-blue-700">
                        {{ $r->status === 'LOCKED' ? 'View →' : 'Reconcile →' }}
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
