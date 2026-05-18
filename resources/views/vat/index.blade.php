@extends('layouts.app')

@section('title', 'VAT Returns')

@section('content')

@php
    $cycleLabel = match((int)($company->vat_quarter_start_month ?? 1)) {
        2 => 'Cycle 2 (Feb/May/Aug/Nov)',
        3 => 'Cycle 3 (Mar/Jun/Sep/Dec)',
        default => 'Cycle 1 (Jan/Apr/Jul/Oct)',
    };
@endphp

<!-- Current Period Banner -->
<div class="mb-5 rounded-lg border overflow-hidden {{ $daysUntilDue < 0 ? 'border-red-200 bg-red-50' : ($daysUntilDue <= 14 ? 'border-yellow-200 bg-yellow-50' : 'border-blue-100 bg-blue-50') }}">
    <div class="px-5 py-3 border-b {{ $daysUntilDue < 0 ? 'border-red-200 bg-red-100' : ($daysUntilDue <= 14 ? 'border-yellow-200 bg-yellow-100' : 'border-blue-100 bg-blue-100') }} flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="font-semibold text-sm {{ $daysUntilDue < 0 ? 'text-red-800' : 'text-blue-800' }}">
                Current VAT Period — {{ $currentPeriod['label'] }}
            </span>
            <span class="text-xs text-gray-500">{{ $cycleLabel }}</span>
        </div>
        <div class="flex items-center gap-3">
            @if($daysUntilDue < 0)
                <span class="text-xs font-semibold text-red-700 bg-red-200 px-2 py-0.5 rounded-full">⚠️ Due date passed {{ abs($daysUntilDue) }} days ago</span>
            @elseif($daysUntilDue <= 14)
                <span class="text-xs font-semibold text-yellow-700 bg-yellow-200 px-2 py-0.5 rounded-full">⏰ Due in {{ $daysUntilDue }} days</span>
            @else
                <span class="text-xs text-blue-600 bg-blue-200 px-2 py-0.5 rounded-full">Due in {{ $daysUntilDue }} days</span>
            @endif
            <span class="text-xs text-gray-500">Deadline: {{ \Carbon\Carbon::parse($currentPeriod['due'])->format('d M Y') }}</span>
        </div>
    </div>
    <div class="px-5 py-4 grid grid-cols-4 gap-6">
        <div>
            <p class="text-xs text-gray-500 mb-1">Output VAT (Collected)</p>
            <p class="text-xl font-bold text-blue-800">AED {{ number_format($currentVAT['outputVatAmount'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">From {{ number_format($currentVAT['standardSales'], 2) }} in sales</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 mb-1">Input VAT (Recoverable)</p>
            <p class="text-xl font-bold text-orange-700">AED {{ number_format($currentVAT['inputVatAmount'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">From {{ number_format($currentVAT['standardPurchases'], 2) }} in purchases</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 mb-1">Net VAT Due</p>
            <p class="text-xl font-bold {{ $currentVAT['netVat'] > 0 ? 'text-red-700' : 'text-green-700' }}">
                AED {{ number_format(abs($currentVAT['netVat']), 2) }}
                @if($currentVAT['netVat'] < 0) <span class="text-sm font-normal">(refund)</span>@endif
            </p>
            <p class="text-xs text-gray-400 mt-0.5">As of today — final at period close</p>
        </div>
        <div class="flex flex-col justify-center">
            @if($currentReturn)
                <a href="/vat/{{ $currentReturn->id }}" class="text-sm text-center bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800">
                    View Filed Return →
                </a>
                <p class="text-xs text-center text-gray-400 mt-1">Return {{ $currentReturn->return_number }} ({{ ucfirst(strtolower($currentReturn->status)) }})</p>
            @else
                <a href="/vat/create?date_from={{ $currentPeriod['from'] }}&date_to={{ $currentPeriod['to'] }}"
                    class="text-sm text-center bg-green-700 text-white px-4 py-2 rounded hover:bg-green-800">
                    ➕ File Return for This Period
                </a>
                <p class="text-xs text-center text-gray-400 mt-1">No return filed yet</p>
            @endif
        </div>
    </div>
</div>

<!-- Previous Period Quick Check -->
@php
    $prevReturn = $returns->where('period_from', $prevPeriod['from'])->first();
    $prevDaysAgo = now()->diffInDays(\Carbon\Carbon::parse($prevPeriod['due']), false);
@endphp
<div class="mb-5 bg-white rounded-lg border border-gray-200 px-5 py-3 flex items-center justify-between">
    <div>
        <p class="text-xs text-gray-400 mb-0.5">Previous Period</p>
        <p class="text-sm font-medium text-gray-700">{{ $prevPeriod['label'] }}</p>
        <p class="text-xs text-gray-400">Due: {{ \Carbon\Carbon::parse($prevPeriod['due'])->format('d M Y') }}</p>
    </div>
    @if($prevReturn)
        <div class="text-center">
            <p class="text-xs text-gray-400 mb-1">Net Payable</p>
            <p class="font-bold {{ $prevReturn->box13_net_payable > 0 ? 'text-red-600' : 'text-green-600' }}">
                AED {{ number_format($prevReturn->box13_net_payable, 2) }}
            </p>
        </div>
        <div class="text-center">
            <p class="text-xs text-gray-400 mb-1">Amount Paid</p>
            <p class="font-bold {{ $prevReturn->amount_paid > 0 ? 'text-green-600' : 'text-gray-400' }}">
                AED {{ number_format($prevReturn->amount_paid ?? 0, 2) }}
            </p>
        </div>
        @php $prevBalance = ($prevReturn->box13_net_payable ?? 0) - ($prevReturn->amount_paid ?? 0); @endphp
        <div class="text-center">
            <p class="text-xs text-gray-400 mb-1">Balance</p>
            <p class="font-bold {{ abs($prevBalance) < 0.01 ? 'text-green-600' : 'text-red-600' }}">
                {{ abs($prevBalance) < 0.01 ? '✅ Settled' : 'AED ' . number_format($prevBalance, 2) . ' outstanding' }}
            </p>
        </div>
        <a href="/vat/{{ $prevReturn->id }}" class="text-xs text-blue-500 hover:text-blue-700">View Return →</a>
    @else
        <span class="text-xs text-red-500 font-medium bg-red-50 px-3 py-1.5 rounded-full">⚠️ No return filed for this period</span>
        <a href="/vat/create?date_from={{ $prevPeriod['from'] }}&date_to={{ $prevPeriod['to'] }}"
            class="text-sm bg-red-700 text-white px-4 py-2 rounded hover:bg-red-800">File Now</a>
    @endif
</div>

<!-- Historical Quarters -->
<div class="mb-3 flex items-center justify-between">
    <p class="text-sm text-gray-500">All VAT quarters since registration</p>
    <a href="/vat/create" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">➕ New VAT Return</a>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-200 bg-gray-50">
                <th class="px-4 py-2 text-left">Period</th>
                <th class="px-4 py-2 text-left">Due Date</th>
                <th class="px-4 py-2 text-right">Sales</th>
                <th class="px-4 py-2 text-right">Output VAT</th>
                <th class="px-4 py-2 text-right">Purchases</th>
                <th class="px-4 py-2 text-right">Input VAT</th>
                <th class="px-4 py-2 text-right">Net Due</th>
                <th class="px-4 py-2 text-right">Paid</th>
                <th class="px-4 py-2 text-right">Balance</th>
                <th class="px-4 py-2 text-center">Return</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($history as $q)
            @php
                $hasActivity = $q['sales'] > 0 || $q['purchases'] > 0 || $q['vatReturn'];
                $netDue      = $q['netDue'];
                $balance     = $q['balance'];
                $r           = $q['vatReturn'];

                if (!$hasActivity) {
                    $rowClass = 'bg-gray-50 text-gray-400';
                } elseif (abs($balance) < 0.01) {
                    $rowClass = 'hover:bg-green-50';
                } elseif ($netDue > 0 && !$r) {
                    $rowClass = 'bg-red-50 hover:bg-red-100'; // no return filed
                } else {
                    $rowClass = 'hover:bg-gray-50';
                }

                $statusColor = !$r ? 'bg-red-100 text-red-600'
                    : match(strtolower($r->status)) {
                        'paid'      => 'bg-green-100 text-green-700',
                        'submitted' => 'bg-blue-100 text-blue-700',
                        'draft'     => 'bg-yellow-100 text-yellow-700',
                        default     => 'bg-gray-100 text-gray-500',
                    };
                $statusLabel = !$r ? 'Not Filed' : strtoupper($r->status);
            @endphp
            <tr class="border-b border-gray-100 {{ $rowClass }}">
                <td class="px-4 py-2 font-medium text-gray-700 text-xs">{{ $q['label'] }}</td>
                <td class="px-4 py-2 text-xs text-gray-500">{{ \Carbon\Carbon::parse($q['due'])->format('d M Y') }}</td>
                <td class="px-4 py-2 text-right text-xs text-gray-600">
                    {{ $q['sales'] > 0 ? number_format($q['sales'], 2) : '—' }}
                </td>
                <td class="px-4 py-2 text-right text-xs font-medium text-blue-700">
                    {{ $q['outputVAT'] > 0 ? number_format($q['outputVAT'], 2) : '—' }}
                </td>
                <td class="px-4 py-2 text-right text-xs text-gray-600">
                    {{ $q['purchases'] > 0 ? number_format($q['purchases'], 2) : '—' }}
                </td>
                <td class="px-4 py-2 text-right text-xs font-medium text-orange-600">
                    {{ $q['inputVAT'] > 0 ? number_format($q['inputVAT'], 2) : '—' }}
                </td>
                <td class="px-4 py-2 text-right font-semibold text-xs {{ $netDue > 0 ? 'text-red-600' : ($netDue < 0 ? 'text-green-600' : 'text-gray-400') }}">
                    @if(abs($netDue) < 0.01) — @elseif($netDue < 0) ({{ number_format(abs($netDue), 2) }}) @else {{ number_format($netDue, 2) }} @endif
                </td>
                <td class="px-4 py-2 text-right text-xs text-green-700">
                    @if(($q['amountPaid'] ?? 0) > 0)
                        {{ number_format($q['amountPaid'], 2) }}
                        @if($r?->payment_date)
                            <br><span class="text-gray-400">{{ \Carbon\Carbon::parse($r->payment_date)->format('d M Y') }}</span>
                        @endif
                    @else
                        —
                    @endif
                </td>
                <td class="px-4 py-2 text-right font-semibold text-xs {{ abs($balance) < 0.01 ? 'text-green-600' : 'text-red-600' }}">
                    @if(!$hasActivity) — @elseif(abs($balance) < 0.01) ✅ Nil @else {{ number_format($balance, 2) }} @endif
                </td>
                <td class="px-4 py-2 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $statusColor }}">{{ $statusLabel }}</span>
                </td>
                <td class="px-4 py-2 text-right">
                    <div class="flex items-center gap-1 justify-end">
                        @if($r)
                            <a href="/vat/{{ $r->id }}" class="text-xs text-blue-500 hover:text-blue-700">View</a>
                            @if(strtolower($r->status) !== 'paid' && ($r->box13_net_payable ?? 0) > 0)
                            <button onclick="document.getElementById('pay-modal-{{ $r->id }}').classList.remove('hidden')"
                                class="text-xs text-green-600 hover:text-green-800 border border-green-200 px-2 py-0.5 rounded">
                                Pay
                            </button>
                            @endif
                        @elseif($hasActivity)
                            <a href="/vat/create?date_from={{ $q['from'] }}&date_to={{ $q['to'] }}"
                                class="text-xs text-white bg-red-600 hover:bg-red-700 px-2 py-0.5 rounded">
                                File
                            </a>
                        @endif
                    </div>
                </td>
            </tr>

            @if($r && strtolower($r->status) !== 'paid' && ($r->box13_net_payable ?? 0) > 0)
            <tr id="pay-modal-{{ $r->id }}" class="hidden bg-green-50 border-b border-green-100">
                <td colspan="11" class="px-6 py-3">
                    <form method="POST" action="/vat/{{ $r->id }}/pay" class="flex items-end gap-4">
                        @csrf
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Amount Paid (AED)</label>
                            <input type="number" name="amount_paid" step="0.01"
                                value="{{ number_format($r->box13_net_payable, 2, '.', '') }}"
                                class="border border-gray-200 rounded px-3 py-1.5 text-sm w-40">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Payment Date</label>
                            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}"
                                class="border border-gray-200 rounded px-3 py-1.5 text-sm">
                        </div>
                        <button type="submit" class="bg-green-700 text-white text-sm px-4 py-1.5 rounded hover:bg-green-800">Confirm</button>
                        <button type="button" onclick="document.getElementById('pay-modal-{{ $r->id }}').classList.add('hidden')"
                            class="text-sm text-gray-400 px-2 py-1.5">Cancel</button>
                    </form>
                </td>
            </tr>
            @endif

            @empty
            <tr>
                <td colspan="11" class="px-4 py-8 text-center text-gray-400">
                    No historical quarters found. Set your VAT registration date in Company Settings.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
