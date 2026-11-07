@extends('layouts.app')

@section('title', 'Payroll')

@section('content')

@php $activeTab = request('tab', 'runs'); @endphp

<!-- Tabs -->
<div class="mb-4 flex items-center justify-between">
    <div class="flex gap-1 bg-white border border-gray-200 rounded-lg p-1">
        <a href="/payroll?tab=runs"
            class="px-4 py-1.5 rounded text-sm font-medium transition {{ $activeTab === 'runs' ? 'bg-green-700 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
            👷 Payroll Runs
        </a>
        <a href="/payroll?tab=directors"
            class="px-4 py-1.5 rounded text-sm font-medium transition {{ $activeTab === 'directors' ? 'bg-green-700 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
            🎩 Directors' Remuneration
        </a>
    </div>

    @if($activeTab === 'runs')
    <div class="flex gap-3">
        <a href="/payroll/employees" class="border border-gray-200 text-gray-600 text-sm px-4 py-2 rounded hover:bg-gray-50">👥 Employees</a>
        <a href="/payroll/run" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">▶ Run Payroll</a>
    </div>
    @else
    <a href="/payroll/directors/create" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">➕ New Payment</a>
    @endif
</div>

@if($activeTab === 'runs')

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

<!-- Payroll Runs Table -->
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
                <td class="px-4 py-2 text-gray-600">{{ $run->pay_period_start }} – {{ $run->pay_period_end }}</td>
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

@else

<!-- Directors' Remuneration -->

@php
    $totalPosted = $directorPayments->where('status', 'posted')->sum('amount');
    $totalDraft  = $directorPayments->where('status', 'draft')->sum('amount');
    $ytd         = $directorPayments->where('status', 'posted')->where('period_year', date('Y'))->sum('amount');
@endphp

<div class="grid grid-cols-3 gap-4 mb-4">
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">Total Posted (All Time)</p>
        <p class="text-xl font-bold text-gray-800">AED {{ number_format($totalPosted, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">YTD {{ date('Y') }}</p>
        <p class="text-xl font-bold text-gray-800">AED {{ number_format($ytd, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400">Pending (Draft)</p>
        <p class="text-xl font-bold {{ $totalDraft > 0 ? 'text-yellow-600' : 'text-gray-800' }}">AED {{ number_format($totalDraft, 2) }}</p>
    </div>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
        <span class="font-semibold text-gray-700 text-sm">Director Payments</span>
        <span class="text-xs text-gray-400">Remuneration &amp; Bonus</span>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                <th class="px-4 py-2 text-left">Ref</th>
                <th class="px-4 py-2 text-left">Director</th>
                <th class="px-4 py-2 text-left">Type</th>
                <th class="px-4 py-2 text-left">Pay Date</th>
                <th class="px-4 py-2 text-right">Amount</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2 text-left">Notes</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($directorPayments as $dp)
            @php
                $sc = match($dp->status) {
                    'posted' => 'bg-green-100 text-green-700',
                    'draft'  => 'bg-yellow-100 text-yellow-700',
                    default  => 'bg-gray-100 text-gray-500',
                };
                $tc = $dp->payment_type === 'bonus'
                    ? 'bg-purple-100 text-purple-700'
                    : 'bg-blue-100 text-blue-700';
            @endphp
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $dp->run_number }}</td>
                <td class="px-4 py-2 font-medium text-gray-800">{{ $dp->director_name }}</td>
                <td class="px-4 py-2">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $tc }}">{{ ucfirst($dp->payment_type) }}</span>
                </td>
                <td class="px-4 py-2 text-gray-600">{{ $dp->pay_date }}</td>
                <td class="px-4 py-2 text-right font-semibold text-gray-800">AED {{ number_format($dp->amount, 2) }}</td>
                <td class="px-4 py-2 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $sc }}">{{ strtoupper($dp->status) }}</span>
                </td>
                <td class="px-4 py-2 text-gray-500 text-xs">{{ $dp->notes ?? '-' }}</td>
                <td class="px-4 py-2 text-right">
                    @if($dp->status === 'draft')
                    <div class="flex gap-2 justify-end">
                        <form method="POST" action="/payroll/directors/{{ $dp->id }}/post">
                            @csrf
                            <button onclick="return confirm('Post this payment to journal?')"
                                class="text-xs bg-green-700 text-white px-2 py-1 rounded hover:bg-green-800">
                                ✅ Post
                            </button>
                        </form>
                        <form method="POST" action="/payroll/directors/{{ $dp->id }}">
                            @csrf
                            @method('DELETE')
                            <button onclick="return confirm('Delete this draft?')"
                                class="text-xs text-red-500 hover:text-red-700 px-1">
                                🗑
                            </button>
                        </form>
                    </div>
                    @else
                    <a href="/journals/{{ $dp->journal_entry_id }}" class="text-xs text-blue-500 hover:text-blue-700">JE →</a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                    No director payments yet. <a href="/payroll/directors/create" class="text-green-600">Add one →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endif

@endsection
