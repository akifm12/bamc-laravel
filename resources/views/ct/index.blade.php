@extends('layouts.app')
@section('title', 'Corporate Tax')
@section('content')

{{-- Tab nav --}}
<div class="flex gap-1 mb-5 border-b border-gray-200">
    <a href="/ct"
       class="px-4 py-2 text-sm font-medium border-b-2 {{ request()->is('ct') || request()->is('ct/') ? 'border-green-700 text-green-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        CT Returns
    </a>
    <a href="/ct/sbr"
       class="px-4 py-2 text-sm font-medium border-b-2 {{ request()->is('ct/sbr') ? 'border-green-700 text-green-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        Small Business Relief
    </a>
</div>

{{-- Current year estimate banner --}}
<div class="mb-5 rounded-lg border border-blue-100 bg-blue-50 overflow-hidden">
    <div class="px-5 py-3 bg-blue-100 border-b border-blue-200 flex items-center justify-between">
        <span class="font-semibold text-sm text-blue-800">Current Year Estimate — {{ $currentYear }}</span>
        <span class="text-xs text-blue-600">Filing due: 30 Sep {{ $currentYear + 1 }}</span>
    </div>
    <div class="px-5 py-4 grid grid-cols-4 gap-6">
        <div>
            <p class="text-xs text-gray-500 mb-1">Revenue (YTD)</p>
            <p class="text-xl font-bold text-blue-800">AED {{ number_format($currentRevenue, 2) }}</p>
            @if($currentRevenue <= 3000000 && $currentRevenue > 0)
                <p class="text-xs text-green-600 mt-0.5">✓ SBR eligible (≤ AED 3M)</p>
            @endif
        </div>
        <div>
            <p class="text-xs text-gray-500 mb-1">Net Profit (YTD)</p>
            <p class="text-xl font-bold {{ $currentProfit >= 0 ? 'text-gray-800' : 'text-red-600' }}">
                AED {{ number_format(abs($currentProfit), 2) }}
                @if($currentProfit < 0)<span class="text-sm font-normal">(loss)</span>@endif
            </p>
        </div>
        <div>
            <p class="text-xs text-gray-500 mb-1">Estimated CT @ 9%</p>
            <p class="text-xl font-bold {{ $estimatedCT['tax'] > 0 ? 'text-red-700' : 'text-green-700' }}">
                AED {{ number_format($estimatedCT['tax'], 2) }}
            </p>
            @if($estimatedCT['nine'] > 0)
                <p class="text-xs text-gray-400 mt-0.5">On AED {{ number_format($estimatedCT['nine'], 2) }} above threshold</p>
            @else
                <p class="text-xs text-green-600 mt-0.5">Below AED 375K threshold</p>
            @endif
        </div>
        <div class="flex flex-col justify-center">
            <a href="/ct/create?year={{ $currentYear }}"
               class="text-sm text-center bg-green-700 text-white px-4 py-2 rounded hover:bg-green-800">
                ➕ Prepare CT Return
            </a>
        </div>
    </div>
</div>

{{-- History table --}}
<div class="mb-3 flex items-center justify-between">
    <p class="text-sm text-gray-500">All fiscal years</p>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-200 bg-gray-50">
                <th class="px-4 py-2 text-left">Year</th>
                <th class="px-4 py-2 text-right">Revenue</th>
                <th class="px-4 py-2 text-right">Net Profit</th>
                <th class="px-4 py-2 text-right">Taxable Income</th>
                <th class="px-4 py-2 text-right">CT Payable</th>
                <th class="px-4 py-2 text-left">Filing Due</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($history as $h)
            @php
                $r = $h['return'];
                $statusColor = !$r ? 'bg-gray-100 text-gray-500'
                    : match($r->status) {
                        'PAID'  => 'bg-green-100 text-green-700',
                        'FILED' => 'bg-blue-100 text-blue-700',
                        default => 'bg-yellow-100 text-yellow-700',
                    };
                $statusLabel = $r ? $r->status : 'NOT FILED';
            @endphp
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="px-4 py-3 font-semibold text-gray-700">{{ $h['year'] }}</td>
                <td class="px-4 py-3 text-right text-xs text-gray-600">
                    {{ $h['revenue'] > 0 ? 'AED ' . number_format($h['revenue'], 2) : '—' }}
                    @if($h['sbrEligible'])
                        <br><span class="text-green-600 text-xs">SBR eligible</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right text-xs {{ $h['netProfit'] < 0 ? 'text-red-600' : 'text-gray-700' }}">
                    {{ $h['netProfit'] != 0 ? 'AED ' . number_format($h['netProfit'], 2) : '—' }}
                </td>
                <td class="px-4 py-3 text-right text-xs text-gray-700">
                    @if($r)
                        @if($r->sbr_elected)
                            <span class="text-green-600">SBR elected (nil)</span>
                        @else
                            AED {{ number_format($r->taxable_income, 2) }}
                        @endif
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right font-semibold text-xs {{ ($r?->ct_payable ?? 0) > 0 ? 'text-red-600' : 'text-gray-400' }}">
                    {{ $r ? 'AED ' . number_format($r->ct_payable, 2) : '—' }}
                </td>
                <td class="px-4 py-3 text-xs text-gray-500">{{ $h['due'] }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $statusColor }}">{{ $statusLabel }}</span>
                </td>
                <td class="px-4 py-3 text-right">
                    @if($r)
                        <a href="/ct/{{ $r->id }}" class="text-xs text-blue-500 hover:text-blue-700">View →</a>
                    @else
                        <a href="/ct/create?year={{ $h['year'] }}"
                           class="text-xs bg-green-700 text-white px-2 py-0.5 rounded hover:bg-green-800">
                            Prepare
                        </a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
