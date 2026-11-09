@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

@if(!($hasCompany ?? true))
<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
    <p class="text-sm text-yellow-700 font-medium">No company assigned yet.</p>
    <a href="/settings/companies" class="mt-3 inline-block text-xs bg-green-700 text-white px-4 py-2 rounded">Set up a Company</a>
</div>
@else

<style>
.kpi-card { position:relative; overflow:hidden; }
.kpi-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.kpi-rev::before  { background: #16a34a; }
.kpi-exp::before  { background: #ef4444; }
.kpi-net::before  { background: #2563eb; }
.kpi-cash::before { background: #7c3aed; }
.db-num { font-size:1.45rem; font-weight:700; letter-spacing:-0.5px; line-height:1.2; }
.section-label { font-size:0.65rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#9ca3af; }
.bar-rev { background:#16a34a; border-radius:3px 3px 0 0; min-height:3px; }
.bar-exp { background:#fca5a5; border-radius:3px 3px 0 0; min-height:3px; }
.inv-row:hover { background:#f9fafb; }
</style>

{{-- ── KPI strip ──────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-4 gap-3 mb-4">

    <div class="kpi-card kpi-rev bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="section-label mb-2">Revenue YTD</p>
        <p class="db-num text-gray-800">{{ number_format($revenue, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">AED &nbsp;·&nbsp; {{ date('Y') }}</p>
    </div>

    <div class="kpi-card kpi-exp bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="section-label mb-2">Expenses YTD</p>
        <p class="db-num text-gray-800">{{ number_format($expenses, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">AED &nbsp;·&nbsp; {{ date('Y') }}</p>
    </div>

    <div class="kpi-card kpi-net bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="section-label mb-2">Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</p>
        <p class="db-num {{ $netProfit >= 0 ? 'text-blue-700' : 'text-red-600' }}">{{ number_format(abs($netProfit), 0) }}</p>
        @if($revenue > 0)
        <p class="text-xs mt-1 {{ $netProfit >= 0 ? 'text-blue-400' : 'text-red-400' }}">
            {{ number_format(($netProfit / $revenue) * 100, 1) }}% margin
        </p>
        @endif
    </div>

    <div class="kpi-card kpi-cash bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="section-label mb-2">Cash &amp; Bank</p>
        <p class="db-num {{ $cashBalance >= 0 ? 'text-violet-700' : 'text-red-600' }}">{{ number_format(abs($cashBalance), 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">AED &nbsp;·&nbsp; all accounts</p>
    </div>

</div>

{{-- ── Main 3-column body ─────────────────────────────────────────────────── --}}
<div class="grid gap-4 mb-4" style="grid-template-columns: 1.9fr 1fr 1fr">

    {{-- LEFT: P&L + chart ──────────────────────────────────────────── --}}
    <div class="flex flex-col gap-3">

        {{-- P&L breakdown --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
            <div class="flex items-center justify-between mb-3">
                <span class="section-label">P&amp;L Breakdown {{ $year }}</span>
                <a href="/reports" class="text-xs text-blue-500 hover:text-blue-700">Full report →</a>
            </div>
            <div class="flex gap-5">
                <div class="flex-1">
                    <div class="flex justify-between text-xs text-gray-500 mb-1"><span>Revenue</span><span class="font-semibold text-gray-700">{{ number_format($revenue,0) }}</span></div>
                    <div class="h-1.5 bg-green-100 rounded-full"><div class="h-full bg-green-500 rounded-full" style="width:100%"></div></div>
                </div>
                <div class="flex-1">
                    <div class="flex justify-between text-xs text-gray-500 mb-1"><span>Expenses</span><span class="font-semibold text-gray-700">{{ number_format($expenses,0) }}</span></div>
                    @php $expPct = $revenue > 0 ? min(100, round(($expenses/$revenue)*100)) : 0; @endphp
                    <div class="h-1.5 bg-red-100 rounded-full"><div class="h-full bg-red-400 rounded-full" style="width:{{ $expPct }}%"></div></div>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-400">Net</p>
                    <p class="text-sm font-bold {{ $netProfit >= 0 ? 'text-blue-700' : 'text-red-600' }}">AED {{ number_format(abs($netProfit),0) }}</p>
                </div>
            </div>
            @if($estimatedCT > 0)
            <div class="mt-3 flex items-center justify-between bg-orange-50 rounded-lg px-3 py-2">
                <span class="text-xs text-orange-700">Estimated Corporate Tax (9%)</span>
                <a href="/ct" class="text-xs font-bold text-orange-700 hover:text-orange-800">AED {{ number_format($estimatedCT,0) }}</a>
            </div>
            @endif
        </div>

        {{-- Monthly chart --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4 flex-1">
            <div class="flex items-center justify-between mb-3">
                <span class="section-label">Revenue vs Expenses — 6 months</span>
                <div class="flex gap-3 text-xs text-gray-400">
                    <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 bg-green-500 rounded-sm"></span>Rev</span>
                    <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 bg-red-300 rounded-sm"></span>Exp</span>
                </div>
            </div>
            @php
                $maxVal = 1;
                foreach ($monthlyData as $m) { $maxVal = max($maxVal, (float)$m->revenue, (float)$m->expenses); }
            @endphp
            @if(count($monthlyData) > 0)
            <div class="flex items-end gap-2" style="height:90px">
                @foreach($monthlyData as $m)
                @php
                    $rH = max(4, round(((float)$m->revenue  / $maxVal) * 82));
                    $eH = max(4, round(((float)$m->expenses / $maxVal) * 82));
                @endphp
                <div class="flex-1 flex flex-col items-center gap-1">
                    <div class="w-full flex gap-0.5 items-end" style="height:82px">
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
                <span class="section-label">Receivables</span>
                <a href="/invoices" class="text-xs text-blue-500">All →</a>
            </div>
            <p class="db-num text-gray-800 mt-2">{{ number_format($receivables->total ?? 0, 0) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">AED &nbsp;·&nbsp; {{ $receivables->count ?? 0 }} unpaid</p>
            @if(($receivables->overdue ?? 0) > 0)
            <div class="mt-3 bg-red-50 rounded-lg px-3 py-2">
                <p class="text-xs font-semibold text-red-600">⚠ Overdue</p>
                <p class="text-xs text-red-500 mt-0.5">AED {{ number_format($receivables->overdue,0) }} · {{ $receivables->overdue_count }} inv.</p>
            </div>
            @else
            <p class="mt-3 text-xs text-green-600">✓ All current</p>
            @endif
            <a href="/invoices/create" class="mt-3 flex items-center justify-center gap-1 text-xs border border-gray-200 rounded-lg py-1.5 text-gray-500 hover:bg-gray-50">+ New Invoice</a>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex-1">
            <div class="flex items-center justify-between mb-1">
                <span class="section-label">Payables</span>
                <a href="/bills" class="text-xs text-blue-500">All →</a>
            </div>
            <p class="db-num text-gray-800 mt-2">{{ number_format($payables->total ?? 0, 0) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">AED &nbsp;·&nbsp; {{ $payables->count ?? 0 }} unpaid</p>
            @if(($payables->overdue ?? 0) > 0)
            <div class="mt-3 bg-red-50 rounded-lg px-3 py-2">
                <p class="text-xs font-semibold text-red-600">⚠ Overdue</p>
                <p class="text-xs text-red-500 mt-0.5">AED {{ number_format($payables->overdue,0) }}</p>
            </div>
            @else
            <p class="mt-3 text-xs text-green-600">✓ All current</p>
            @endif
            <a href="/bills/create" class="mt-3 flex items-center justify-center gap-1 text-xs border border-gray-200 rounded-lg py-1.5 text-gray-500 hover:bg-gray-50">+ New Bill</a>
        </div>

    </div>

    {{-- RIGHT: Tax + Quick Actions ─────────────────────────────────── --}}
    <div class="flex flex-col gap-3">

        {{-- Tax snapshot --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <span class="section-label">Tax Snapshot</span>
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
                        @if($vatNetDue < 0) <span class="text-xs font-normal">(refund)</span>@endif
                    </span>
                </div>
                @if($estimatedCT > 0)
                <div class="h-px bg-gray-100"></div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-orange-600">Est. Corp Tax</span>
                    <a href="/ct" class="text-xs font-bold text-orange-600">AED {{ number_format($estimatedCT,0) }}</a>
                </div>
                @endif
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex-1">
            <p class="section-label mb-3">Quick Actions</p>
            <div class="space-y-1.5">
                <a href="/invoices/create" class="flex items-center gap-2.5 text-xs text-gray-600 hover:text-green-700 hover:bg-green-50 px-3 py-2 rounded-lg transition-colors">
                    <span class="w-5 h-5 bg-green-100 text-green-700 rounded flex items-center justify-center text-xs font-bold">🧾</span> New Invoice
                </a>
                <a href="/bills/create" class="flex items-center gap-2.5 text-xs text-gray-600 hover:text-gray-900 hover:bg-gray-50 px-3 py-2 rounded-lg transition-colors">
                    <span class="w-5 h-5 bg-gray-100 text-gray-600 rounded flex items-center justify-center text-xs">📋</span> New Bill
                </a>
                <a href="/journals/create" class="flex items-center gap-2.5 text-xs text-gray-600 hover:text-blue-700 hover:bg-blue-50 px-3 py-2 rounded-lg transition-colors">
                    <span class="w-5 h-5 bg-blue-100 text-blue-700 rounded flex items-center justify-center text-xs">📓</span> Journal Entry
                </a>
                <a href="/reports" class="flex items-center gap-2.5 text-xs text-gray-600 hover:text-purple-700 hover:bg-purple-50 px-3 py-2 rounded-lg transition-colors">
                    <span class="w-5 h-5 bg-purple-100 text-purple-700 rounded flex items-center justify-center text-xs">📈</span> Reports
                </a>
                <a href="/vat" class="flex items-center gap-2.5 text-xs text-gray-600 hover:text-yellow-700 hover:bg-yellow-50 px-3 py-2 rounded-lg transition-colors">
                    <span class="w-5 h-5 bg-yellow-100 text-yellow-700 rounded flex items-center justify-center text-xs">🧾</span> VAT Returns
                </a>
                <a href="/ct" class="flex items-center gap-2.5 text-xs text-gray-600 hover:text-orange-700 hover:bg-orange-50 px-3 py-2 rounded-lg transition-colors">
                    <span class="w-5 h-5 bg-orange-100 text-orange-700 rounded flex items-center justify-center text-xs">🏢</span> Corp Tax
                </a>
            </div>
        </div>

    </div>

</div>

{{-- ── Bottom: Recent Invoices + Bills ──────────────────────────────────── --}}
<div class="grid grid-cols-2 gap-4">

    {{-- Recent Invoices --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 flex items-center justify-between border-b border-gray-50">
            <span class="section-label">Recent Invoices</span>
            <a href="/invoices" class="text-xs text-blue-500">View all →</a>
        </div>
        @if($recentInvoices->count())
        <table class="w-full">
            <tbody>
            @foreach($recentInvoices as $inv)
            @php
                $isOverdue = $inv->due_date && $inv->due_date < date('Y-m-d') && !in_array($inv->status, ['PAID','VOID']);
                $dot = match(true) {
                    $isOverdue             => 'bg-red-500',
                    $inv->status === 'PAID'    => 'bg-green-500',
                    $inv->status === 'SENT'    => 'bg-blue-400',
                    $inv->status === 'DRAFT'   => 'bg-gray-300',
                    default                    => 'bg-yellow-400',
                };
                $label = $isOverdue ? 'OVERDUE' : $inv->status;
                $lc = match(true) {
                    $isOverdue             => 'text-red-600',
                    $inv->status === 'PAID'    => 'text-green-600',
                    $inv->status === 'SENT'    => 'text-blue-500',
                    $inv->status === 'DRAFT'   => 'text-gray-400',
                    default                    => 'text-yellow-600',
                };
            @endphp
            <tr class="inv-row border-b border-gray-50 last:border-0">
                <td class="pl-5 pr-2 py-2.5 w-4"><span class="inline-block w-1.5 h-1.5 rounded-full {{ $dot }}"></span></td>
                <td class="py-2.5 pr-2">
                    <a href="/invoices/{{ $inv->id }}" class="text-xs font-semibold text-gray-700 hover:text-blue-600">{{ $inv->invoice_number }}</a>
                    <p class="text-xs text-gray-400 truncate max-w-28">{{ $inv->customer_name }}</p>
                </td>
                <td class="py-2.5 pr-5 text-right">
                    <p class="text-xs font-semibold text-gray-700">{{ number_format($inv->total_amount,0) }}</p>
                    <p class="text-xs {{ $lc }}">{{ $label }}</p>
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
        <div class="px-5 py-3 flex items-center justify-between border-b border-gray-50">
            <span class="section-label">Recent Bills</span>
            <a href="/bills" class="text-xs text-blue-500">View all →</a>
        </div>
        @if($recentBills->count())
        <table class="w-full">
            <tbody>
            @foreach($recentBills as $bill)
            @php
                $isOverdue = $bill->due_date && $bill->due_date < date('Y-m-d') && !in_array($bill->status, ['PAID','VOID']);
                $dot = match(true) {
                    $isOverdue              => 'bg-red-500',
                    $bill->status === 'PAID'    => 'bg-green-500',
                    $bill->status === 'DRAFT'   => 'bg-gray-300',
                    default                     => 'bg-yellow-400',
                };
                $label = $isOverdue ? 'OVERDUE' : $bill->status;
                $lc = match(true) {
                    $isOverdue              => 'text-red-600',
                    $bill->status === 'PAID'    => 'text-green-600',
                    $bill->status === 'DRAFT'   => 'text-gray-400',
                    default                     => 'text-yellow-600',
                };
            @endphp
            <tr class="inv-row border-b border-gray-50 last:border-0">
                <td class="pl-5 pr-2 py-2.5 w-4"><span class="inline-block w-1.5 h-1.5 rounded-full {{ $dot }}"></span></td>
                <td class="py-2.5 pr-2">
                    <a href="/bills/{{ $bill->id }}" class="text-xs font-semibold text-gray-700 hover:text-blue-600">{{ $bill->bill_number }}</a>
                    <p class="text-xs text-gray-400 truncate max-w-28">{{ $bill->vendor_name }}</p>
                </td>
                <td class="py-2.5 pr-5 text-right">
                    <p class="text-xs font-semibold text-gray-700">{{ number_format($bill->total_amount,0) }}</p>
                    <p class="text-xs {{ $lc }}">{{ $label }}</p>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <div class="py-8 text-center text-xs text-gray-400">No bills yet</div>
        @endif
    </div>

</div>

@endif
@endsection
