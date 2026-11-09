@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

@if(!($hasCompany ?? true))
<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
    <p class="text-sm text-yellow-700 font-medium">No company assigned yet.</p>
    <a href="/settings/companies" class="mt-3 inline-block text-xs bg-green-700 text-white px-4 py-2 rounded">Set up a Company</a>
</div>
@else

@php
// ── Dual-line sparkline helper ───────────────────────────────────────────────
// Returns SVG data for a colored YTD line + gray flat reference at lastYearVal
function dualSparkData(array $ytdVals, float $flatVal, int $w, int $h): array {
    if (empty($ytdVals)) return ['pts' => '', 'fill' => '', 'flatY' => $h / 2, 'hasData' => false];
    $allVals = array_merge($ytdVals, [$flatVal]);
    $mn  = min(array_merge($allVals, [0]));
    $mx  = max($allVals) ?: 1;
    $rng = $mx - $mn ?: 1;
    $n   = count($ytdVals);
    $pts = [];
    foreach ($ytdVals as $i => $v) {
        $x     = $n > 1 ? round($i / ($n - 1) * $w, 2) : $w / 2;
        $y     = round($h - (($v - $mn) / $rng) * ($h - 6) - 3, 2);
        $pts[] = "$x,$y";
    }
    $polyline = implode(' ', $pts);
    $flatY    = round($h - (($flatVal - $mn) / $rng) * ($h - 6) - 3, 2);
    return [
        'pts'     => $polyline,
        'fill'    => "$polyline {$w},{$h} 0,{$h}",
        'flatY'   => $flatY,
        'hasData' => true,
    ];
}

$spW   = 120; $spH = 32;
$dRev  = dualSparkData($cumulRev,       $lyRevTotal,  $spW, $spH);
$dExp  = dualSparkData($cumulExp,       $lyExpTotal,  $spW, $spH);
$dNet  = dualSparkData($cumulNet,       $lyNetTotal,  $spW, $spH);
$dCash = dualSparkData($cashMonthlyBals, $lyDecBalance, $spW, $spH);
@endphp

<style>
.kpi-card{position:relative;overflow:hidden}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:12px 12px 0 0}
.kpi-rev::before{background:#16a34a}.kpi-exp::before{background:#ef4444}
.kpi-net::before{background:#2563eb}.kpi-cash::before{background:#7c3aed}
.db-num{font-size:1.45rem;font-weight:700;letter-spacing:-.5px;line-height:1.2}
.section-label{font-size:.65rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9ca3af}
.bar-rev{background:#16a34a;border-radius:3px 3px 0 0;min-height:3px}
.bar-exp{background:#fca5a5;border-radius:3px 3px 0 0;min-height:3px}
.inv-row:hover{background:#f9fafb}
.qa-link{display:flex;align-items:center;gap:10px;font-size:.75rem;color:#4b5563;padding:7px 10px;border-radius:8px;transition:background .15s,color .15s;text-decoration:none}
.qa-link:hover{background:#f3f4f6;color:#111827}
.qa-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
</style>

{{-- ── KPI strip ──────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-4 gap-3 mb-4">

    {{-- Revenue --}}
    <div class="kpi-card kpi-rev bg-white rounded-xl border border-gray-100 shadow-sm p-4 pb-0">
        <div class="flex items-center justify-between mb-2">
            <p class="section-label">Revenue YTD</p>
            @if($yoyRevDelta !== null)
            <span class="text-xs font-semibold px-1.5 py-0.5 rounded-full {{ $yoyRevDelta >= 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500' }}">
                {{ $yoyRevDelta >= 0 ? '↑' : '↓' }} {{ abs($yoyRevDelta) }}%
            </span>
            @endif
        </div>
        <p class="db-num text-gray-800">{{ number_format($revenue, 0) }}</p>
        <p class="text-xs text-gray-400 mt-0.5 mb-2">AED · YTD vs {{ $year-1 }} total</p>
        @if($dRev['hasData'])
        <div class="relative">
            <svg viewBox="0 0 {{ $spW }} {{ $spH }}" class="w-full" style="height:32px" preserveAspectRatio="none">
                <defs><linearGradient id="gr-rev" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#16a34a" stop-opacity=".18"/>
                    <stop offset="100%" stop-color="#16a34a" stop-opacity="0"/>
                </linearGradient></defs>
                <polygon points="{{ $dRev['fill'] }}" fill="url(#gr-rev)"/>
                @if($lyRevTotal > 0)
                <line x1="0" y1="{{ $dRev['flatY'] }}" x2="{{ $spW }}" y2="{{ $dRev['flatY'] }}"
                      stroke="#d1d5db" stroke-width="1" stroke-dasharray="3,2.5"/>
                @endif
                <polyline points="{{ $dRev['pts'] }}" fill="none" stroke="#16a34a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        @endif
    </div>

    {{-- Expenses --}}
    <div class="kpi-card kpi-exp bg-white rounded-xl border border-gray-100 shadow-sm p-4 pb-0">
        <div class="flex items-center justify-between mb-2">
            <p class="section-label">Expenses YTD</p>
            @if($yoyExpDelta !== null)
            <span class="text-xs font-semibold px-1.5 py-0.5 rounded-full {{ $yoyExpDelta <= 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500' }}">
                {{ $yoyExpDelta >= 0 ? '↑' : '↓' }} {{ abs($yoyExpDelta) }}%
            </span>
            @endif
        </div>
        <p class="db-num text-gray-800">{{ number_format($expenses, 0) }}</p>
        <p class="text-xs text-gray-400 mt-0.5 mb-2">AED · YTD vs {{ $year-1 }} total</p>
        @if($dExp['hasData'])
        <div class="relative">
            <svg viewBox="0 0 {{ $spW }} {{ $spH }}" class="w-full" style="height:32px" preserveAspectRatio="none">
                <defs><linearGradient id="gr-exp" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#ef4444" stop-opacity=".15"/>
                    <stop offset="100%" stop-color="#ef4444" stop-opacity="0"/>
                </linearGradient></defs>
                <polygon points="{{ $dExp['fill'] }}" fill="url(#gr-exp)"/>
                @if($lyExpTotal > 0)
                <line x1="0" y1="{{ $dExp['flatY'] }}" x2="{{ $spW }}" y2="{{ $dExp['flatY'] }}"
                      stroke="#d1d5db" stroke-width="1" stroke-dasharray="3,2.5"/>
                @endif
                <polyline points="{{ $dExp['pts'] }}" fill="none" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        @endif
    </div>

    {{-- Net Profit --}}
    <div class="kpi-card kpi-net bg-white rounded-xl border border-gray-100 shadow-sm p-4 pb-0">
        <div class="flex items-center justify-between mb-2">
            <p class="section-label">Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</p>
            @if($yoyNetDelta !== null)
            <span class="text-xs font-semibold px-1.5 py-0.5 rounded-full {{ $yoyNetDelta >= 0 ? 'bg-blue-50 text-blue-600' : 'bg-red-50 text-red-500' }}">
                {{ $yoyNetDelta >= 0 ? '↑' : '↓' }} {{ abs($yoyNetDelta) }}%
            </span>
            @endif
        </div>
        <p class="db-num {{ $netProfit >= 0 ? 'text-blue-700' : 'text-red-600' }}">{{ number_format(abs($netProfit), 0) }}</p>
        <p class="text-xs mt-0.5 mb-2 {{ $netProfit >= 0 ? 'text-blue-400' : 'text-red-400' }}">
            @if($revenue > 0)
                {{ number_format(($netProfit / $revenue) * 100, 1) }}% margin · YTD vs {{ $year-1 }} total
            @else
                AED · YTD vs {{ $year-1 }} total
            @endif
        </p>
        @if($dNet['hasData'])
        @php $netColor = $netProfit >= 0 ? '#2563eb' : '#ef4444'; @endphp
        <div class="relative">
            <svg viewBox="0 0 {{ $spW }} {{ $spH }}" class="w-full" style="height:32px" preserveAspectRatio="none">
                <defs><linearGradient id="gr-net" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="{{ $netColor }}" stop-opacity=".15"/>
                    <stop offset="100%" stop-color="{{ $netColor }}" stop-opacity="0"/>
                </linearGradient></defs>
                <polygon points="{{ $dNet['fill'] }}" fill="url(#gr-net)"/>
                @if($lyNetTotal != 0)
                <line x1="0" y1="{{ $dNet['flatY'] }}" x2="{{ $spW }}" y2="{{ $dNet['flatY'] }}"
                      stroke="#d1d5db" stroke-width="1" stroke-dasharray="3,2.5"/>
                @endif
                <polyline points="{{ $dNet['pts'] }}" fill="none" stroke="{{ $netColor }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        @endif
    </div>

    {{-- Cash & Bank --}}
    <div class="kpi-card kpi-cash bg-white rounded-xl border border-gray-100 shadow-sm p-4 pb-0">
        <div class="flex items-center justify-between mb-2">
            <p class="section-label">Cash &amp; Bank Balance</p>
            @if($lyDecBalance != 0)
            <span class="text-xs font-semibold px-1.5 py-0.5 rounded-full {{ $cashBalance >= $lyDecBalance ? 'bg-violet-50 text-violet-600' : 'bg-red-50 text-red-500' }}">
                {{ $cashBalance >= $lyDecBalance ? '↑' : '↓' }} vs LY
            </span>
            @endif
        </div>
        <p class="db-num {{ $cashBalance >= 0 ? 'text-violet-700' : 'text-red-600' }}">{{ number_format(abs($cashBalance), 0) }}</p>
        <p class="text-xs text-gray-400 mt-0.5 mb-2">AED · vs {{ $year-1 }} close</p>
        @if($dCash['hasData'])
        <div class="relative">
            <svg viewBox="0 0 {{ $spW }} {{ $spH }}" class="w-full" style="height:32px" preserveAspectRatio="none">
                <defs><linearGradient id="gr-cash" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#7c3aed" stop-opacity=".18"/>
                    <stop offset="100%" stop-color="#7c3aed" stop-opacity="0"/>
                </linearGradient></defs>
                <polygon points="{{ $dCash['fill'] }}" fill="url(#gr-cash)"/>
                @if($lyDecBalance != 0)
                <line x1="0" y1="{{ $dCash['flatY'] }}" x2="{{ $spW }}" y2="{{ $dCash['flatY'] }}"
                      stroke="#d1d5db" stroke-width="1" stroke-dasharray="3,2.5"/>
                @endif
                <polyline points="{{ $dCash['pts'] }}" fill="none" stroke="#7c3aed" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        @endif
    </div>

</div>

{{-- ── Main 3-column body ─────────────────────────────────────────────────── --}}
<div class="grid gap-4 mb-4" style="grid-template-columns:1.9fr 1fr 1fr">

    {{-- LEFT: P&L + chart ──────────────────────────────────────────── --}}
    <div class="flex flex-col gap-3">

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
            <div class="flex items-center justify-between mb-3">
                <span class="section-label">P&amp;L Breakdown {{ $year }}</span>
                <a href="/reports" class="text-xs text-blue-500 hover:text-blue-700 flex items-center gap-1">
                    Full report
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
            </div>
            <div class="flex gap-5 items-center">
                <div class="flex-1 space-y-2">
                    <div>
                        <div class="flex justify-between text-xs text-gray-500 mb-1"><span>Revenue</span><span class="font-semibold text-gray-700">{{ number_format($revenue,0) }}</span></div>
                        <div class="h-1.5 bg-green-100 rounded-full"><div class="h-full bg-green-500 rounded-full" style="width:100%"></div></div>
                    </div>
                    <div>
                        @php $expPct = $revenue > 0 ? min(100, round(($expenses/$revenue)*100)) : 0; @endphp
                        <div class="flex justify-between text-xs text-gray-500 mb-1"><span>Expenses</span><span class="font-semibold text-gray-700">{{ number_format($expenses,0) }}</span></div>
                        <div class="h-1.5 bg-red-100 rounded-full"><div class="h-full bg-red-400 rounded-full" style="width:{{ $expPct }}%"></div></div>
                    </div>
                </div>
                <div class="text-right pl-4 border-l border-gray-100">
                    <p class="text-xs text-gray-400 mb-0.5">Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</p>
                    <p class="text-xl font-bold {{ $netProfit >= 0 ? 'text-blue-700' : 'text-red-600' }}">{{ number_format(abs($netProfit),0) }}</p>
                    @if($revenue > 0)<p class="text-xs {{ $netProfit >= 0 ? 'text-blue-400' : 'text-red-400' }}">{{ number_format(($netProfit/$revenue)*100,1) }}%</p>@endif
                </div>
            </div>
            @if($estimatedCT > 0)
            <div class="mt-3 flex items-center justify-between bg-orange-50 rounded-lg px-3 py-2">
                <div class="flex items-center gap-2 text-xs text-orange-700">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Estimated Corporate Tax (9%)
                </div>
                <a href="/ct" class="text-xs font-bold text-orange-700 hover:text-orange-800">AED {{ number_format($estimatedCT,0) }}</a>
            </div>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4 flex-1">
            <div class="flex items-center justify-between mb-3">
                <span class="section-label">Monthly Trend — 6 months</span>
                <div class="flex gap-3 text-xs text-gray-400">
                    <span class="flex items-center gap-1.5"><span class="inline-block w-2 h-2 bg-green-500 rounded-sm"></span>Revenue</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block w-2 h-2 bg-red-300 rounded-sm"></span>Expenses</span>
                </div>
            </div>
            @php $maxVal = 1; foreach ($monthlyData as $m) { $maxVal = max($maxVal,(float)$m->revenue,(float)$m->expenses); } @endphp
            @if(count($monthlyData) > 0)
            <div class="flex items-end gap-2" style="height:88px">
                @foreach($monthlyData as $m)
                @php $rH=max(4,round(((float)$m->revenue/$maxVal)*80)); $eH=max(4,round(((float)$m->expenses/$maxVal)*80)); @endphp
                <div class="flex-1 flex flex-col items-center gap-1">
                    <div class="w-full flex gap-0.5 items-end" style="height:80px">
                        <div class="flex-1 bar-rev" style="height:{{ $rH }}px" title="Rev AED {{ number_format($m->revenue,0) }}"></div>
                        <div class="flex-1 bar-exp" style="height:{{ $eH }}px" title="Exp AED {{ number_format($m->expenses,0) }}"></div>
                    </div>
                    <span class="text-xs text-gray-400">{{ $m->month_label }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="flex items-center justify-center h-20 text-xs text-gray-400">No posted data yet</div>
            @endif
        </div>

    </div>

    {{-- MIDDLE: Receivables + Payables ─────────────────────────────── --}}
    <div class="flex flex-col gap-3">

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex-1">
            <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <span class="section-label">Receivables</span>
                </div>
                <a href="/invoices" class="text-xs text-blue-500">All →</a>
            </div>
            <p class="db-num text-gray-800 mt-2">{{ number_format($receivables->total ?? 0, 0) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">AED · {{ $receivables->count ?? 0 }} unpaid</p>
            @if(($receivables->overdue ?? 0) > 0)
            <div class="mt-3 bg-red-50 rounded-lg px-3 py-2">
                <div class="flex items-center gap-1.5 mb-0.5">
                    <svg class="w-3 h-3 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <p class="text-xs font-semibold text-red-600">Overdue</p>
                </div>
                <p class="text-xs text-red-500">AED {{ number_format($receivables->overdue,0) }} · {{ $receivables->overdue_count }} inv.</p>
            </div>
            @else
            <div class="mt-3 flex items-center gap-1.5 text-xs text-green-600">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                All current
            </div>
            @endif
            <a href="/invoices/create" class="mt-3 flex items-center justify-center gap-1.5 text-xs border border-gray-200 rounded-lg py-1.5 text-gray-500 hover:bg-gray-50">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Invoice
            </a>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex-1">
            <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-orange-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    <span class="section-label">Payables</span>
                </div>
                <a href="/bills" class="text-xs text-blue-500">All →</a>
            </div>
            <p class="db-num text-gray-800 mt-2">{{ number_format($payables->total ?? 0, 0) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">AED · {{ $payables->count ?? 0 }} unpaid</p>
            @if(($payables->overdue ?? 0) > 0)
            <div class="mt-3 bg-red-50 rounded-lg px-3 py-2">
                <div class="flex items-center gap-1.5 mb-0.5">
                    <svg class="w-3 h-3 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <p class="text-xs font-semibold text-red-600">Overdue</p>
                </div>
                <p class="text-xs text-red-500">AED {{ number_format($payables->overdue,0) }}</p>
            </div>
            @else
            <div class="mt-3 flex items-center gap-1.5 text-xs text-green-600">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                All current
            </div>
            @endif
            <a href="/bills/create" class="mt-3 flex items-center justify-center gap-1.5 text-xs border border-gray-200 rounded-lg py-1.5 text-gray-500 hover:bg-gray-50">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Bill
            </a>
        </div>

    </div>

    {{-- RIGHT: Tax + Quick Actions ─────────────────────────────────── --}}
    <div class="flex flex-col gap-3">

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-yellow-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span class="section-label">Tax Snapshot</span>
                </div>
                <a href="/vat" class="text-xs text-blue-500">VAT →</a>
            </div>
            <div class="space-y-2.5">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500">VAT Collected</span>
                    <span class="text-xs font-semibold text-gray-700">AED {{ number_format($vatCollected,0) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500">VAT Reclaimable</span>
                    <span class="text-xs font-semibold text-gray-700">AED {{ number_format($vatReclaimable,0) }}</span>
                </div>
                <div class="h-px bg-gray-100"></div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-600">Net VAT Due</span>
                    <span class="text-sm font-bold {{ $vatNetDue > 0 ? 'text-red-600' : 'text-green-700' }}">
                        AED {{ number_format(abs($vatNetDue),0) }}
                        @if($vatNetDue < 0)<span class="text-xs font-normal ml-0.5">(refund)</span>@endif
                    </span>
                </div>
                @if($estimatedCT > 0)
                <div class="h-px bg-gray-100"></div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-orange-600">Est. Corp Tax</span>
                    <a href="/ct" class="text-xs font-bold text-orange-600 hover:text-orange-700">AED {{ number_format($estimatedCT,0) }}</a>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex-1">
            <p class="section-label mb-3">Quick Actions</p>
            <div class="space-y-0.5">

                <a href="/invoices/create" class="qa-link">
                    <span class="qa-icon bg-green-50">
                        <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </span>
                    New Invoice
                </a>

                <a href="/bills/create" class="qa-link">
                    <span class="qa-icon bg-gray-50">
                        <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </span>
                    New Bill
                </a>

                <a href="/journals/create" class="qa-link">
                    <span class="qa-icon bg-blue-50">
                        <svg class="w-4 h-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    </span>
                    Journal Entry
                </a>

                <a href="/reports" class="qa-link">
                    <span class="qa-icon bg-purple-50">
                        <svg class="w-4 h-4 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>
                    </span>
                    Reports
                </a>

                <a href="/vat" class="qa-link">
                    <span class="qa-icon bg-yellow-50">
                        <svg class="w-4 h-4 text-yellow-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </span>
                    VAT Returns
                </a>

                <a href="/ct" class="qa-link">
                    <span class="qa-icon bg-orange-50">
                        <svg class="w-4 h-4 text-orange-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </span>
                    Corp Tax
                </a>

            </div>
        </div>

    </div>

</div>

{{-- ── Bottom: Recent Invoices · Recent Bills · Outstanding ─────────────── --}}
<div class="grid grid-cols-3 gap-4">

    {{-- Recent Invoices --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 flex items-center justify-between border-b border-gray-50">
            <div class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <span class="section-label">Recent Invoices</span>
            </div>
            <a href="/invoices" class="text-xs text-blue-500">All →</a>
        </div>
        @if($recentInvoices->count())
        <table class="w-full">
            <tbody>
            @foreach($recentInvoices as $inv)
            @php
                $isOvd = $inv->due_date && $inv->due_date < date('Y-m-d') && !in_array($inv->status,['PAID','VOID']);
                $dot = $isOvd ? 'bg-red-500' : match($inv->status){ 'PAID'=>'bg-green-500','SENT'=>'bg-blue-400','DRAFT'=>'bg-gray-300',default=>'bg-yellow-400' };
                $lbl = $isOvd ? 'OVERDUE' : $inv->status;
                $lc  = $isOvd ? 'text-red-500' : match($inv->status){ 'PAID'=>'text-green-600','SENT'=>'text-blue-500','DRAFT'=>'text-gray-400',default=>'text-yellow-600' };
            @endphp
            <tr class="inv-row border-b border-gray-50 last:border-0">
                <td class="pl-4 pr-2 py-2.5 w-4"><span class="inline-block w-1.5 h-1.5 rounded-full {{ $dot }}"></span></td>
                <td class="py-2.5 pr-2 min-w-0">
                    <a href="/invoices/{{ $inv->id }}" class="text-xs font-semibold text-gray-700 hover:text-blue-600 block truncate" style="max-width:110px">{{ $inv->invoice_number }}</a>
                    <p class="text-xs text-gray-400 truncate" style="max-width:110px">{{ $inv->customer_name }}</p>
                </td>
                <td class="py-2.5 pr-4 text-right whitespace-nowrap">
                    <p class="text-xs font-semibold text-gray-700">{{ number_format($inv->total_amount,0) }}</p>
                    <p class="text-xs {{ $lc }}">{{ $lbl }}</p>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <div class="py-8 text-center text-xs text-gray-400">No invoices yet</div>
        @endif
    </div>

    {{-- Recent Bills --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 flex items-center justify-between border-b border-gray-50">
            <div class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                <span class="section-label">Recent Bills</span>
            </div>
            <a href="/bills" class="text-xs text-blue-500">All →</a>
        </div>
        @if($recentBills->count())
        <table class="w-full">
            <tbody>
            @foreach($recentBills as $bill)
            @php
                $isOvd = $bill->due_date && $bill->due_date < date('Y-m-d') && !in_array($bill->status,['PAID','VOID']);
                $dot = $isOvd ? 'bg-red-500' : match($bill->status){ 'PAID'=>'bg-green-500','DRAFT'=>'bg-gray-300',default=>'bg-yellow-400' };
                $lbl = $isOvd ? 'OVERDUE' : $bill->status;
                $lc  = $isOvd ? 'text-red-500' : match($bill->status){ 'PAID'=>'text-green-600','DRAFT'=>'text-gray-400',default=>'text-yellow-600' };
            @endphp
            <tr class="inv-row border-b border-gray-50 last:border-0">
                <td class="pl-4 pr-2 py-2.5 w-4"><span class="inline-block w-1.5 h-1.5 rounded-full {{ $dot }}"></span></td>
                <td class="py-2.5 pr-2 min-w-0">
                    <a href="/bills/{{ $bill->id }}" class="text-xs font-semibold text-gray-700 hover:text-blue-600 block truncate" style="max-width:110px">{{ $bill->bill_number }}</a>
                    <p class="text-xs text-gray-400 truncate" style="max-width:110px">{{ $bill->vendor_name }}</p>
                </td>
                <td class="py-2.5 pr-4 text-right whitespace-nowrap">
                    <p class="text-xs font-semibold text-gray-700">{{ number_format($bill->total_amount,0) }}</p>
                    <p class="text-xs {{ $lc }}">{{ $lbl }}</p>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <div class="py-8 text-center text-xs text-gray-400">No bills yet</div>
        @endif
    </div>

    {{-- Outstanding Invoices --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 flex items-center justify-between border-b border-gray-50">
            <div class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-orange-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span class="section-label">Outstanding</span>
            </div>
            <a href="/invoices?status=unpaid" class="text-xs text-blue-500">All →</a>
        </div>
        @if($outstandingInvoices->count())
        <table class="w-full">
            <tbody>
            @foreach($outstandingInvoices as $inv)
            @php
                $today   = date('Y-m-d');
                $isOvd   = $inv->due_date && $inv->due_date < $today;
                $dayDiff = $inv->due_date ? (int)round((strtotime($inv->due_date) - strtotime($today)) / 86400) : null;
                $dot     = $isOvd ? 'bg-red-500' : 'bg-orange-400';
                $dueLbl  = $dayDiff === null ? '—'
                         : ($isOvd ? abs($dayDiff) . 'd overdue' : 'due in ' . $dayDiff . 'd');
                $dueCls  = $isOvd ? 'text-red-500' : 'text-orange-500';
            @endphp
            <tr class="inv-row border-b border-gray-50 last:border-0">
                <td class="pl-4 pr-2 py-2.5 w-4"><span class="inline-block w-1.5 h-1.5 rounded-full {{ $dot }}"></span></td>
                <td class="py-2.5 pr-2 min-w-0">
                    <a href="/invoices/{{ $inv->id }}" class="text-xs font-semibold text-gray-700 hover:text-blue-600 block truncate" style="max-width:110px">{{ $inv->invoice_number }}</a>
                    <p class="text-xs text-gray-400 truncate" style="max-width:110px">{{ $inv->customer_name }}</p>
                </td>
                <td class="py-2.5 pr-4 text-right whitespace-nowrap">
                    <p class="text-xs font-semibold text-gray-700">{{ number_format($inv->total_amount,0) }}</p>
                    <p class="text-xs {{ $dueCls }}">{{ $dueLbl }}</p>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <div class="py-8 text-center text-xs text-gray-400">All invoices settled</div>
        @endif
    </div>

</div>

@endif
@endsection
