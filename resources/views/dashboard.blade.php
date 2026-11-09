@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

@if(!($hasCompany ?? true))
<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
    <p class="text-sm text-yellow-700 font-medium">No company assigned to your account yet.</p>
    <a href="/settings/companies" class="mt-3 inline-block text-xs bg-green-700 text-white px-4 py-2 rounded hover:bg-green-800">Set up a Company</a>
</div>
@else

{{-- ── Row 1: KPI tiles ──────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">

    {{-- Revenue --}}
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Revenue YTD</p>
            <span class="text-green-100 bg-green-600 rounded-full w-7 h-7 flex items-center justify-center text-xs">↑</span>
        </div>
        <p class="text-2xl font-bold text-gray-800">AED {{ number_format($revenue, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ date('Y') }} posted journals</p>
    </div>

    {{-- Expenses --}}
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Expenses YTD</p>
            <span class="text-red-100 bg-red-500 rounded-full w-7 h-7 flex items-center justify-center text-xs">↓</span>
        </div>
        <p class="text-2xl font-bold text-gray-800">AED {{ number_format($expenses, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ date('Y') }} posted journals</p>
    </div>

    {{-- Net Profit --}}
    <div class="bg-white rounded-lg border border-gray-200 p-4 {{ $netProfit >= 0 ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-red-500' }}">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Net Profit YTD</p>
            <span class="text-xs {{ $netProfit >= 0 ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50' }} px-2 py-0.5 rounded-full font-semibold">
                {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}
            </span>
        </div>
        <p class="text-2xl font-bold {{ $netProfit >= 0 ? 'text-green-700' : 'text-red-600' }}">
            AED {{ number_format(abs($netProfit), 0) }}
        </p>
        @if($revenue > 0)
        <p class="text-xs text-gray-400 mt-1">{{ number_format(($netProfit / $revenue) * 100, 1) }}% margin</p>
        @endif
    </div>

    {{-- Cash & Bank --}}
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Cash & Bank</p>
            <span class="text-blue-100 bg-blue-500 rounded-full w-7 h-7 flex items-center justify-center text-xs">🏦</span>
        </div>
        <p class="text-2xl font-bold {{ $cashBalance >= 0 ? 'text-blue-700' : 'text-red-600' }}">
            AED {{ number_format(abs($cashBalance), 0) }}
        </p>
        <p class="text-xs text-gray-400 mt-1">All accounts combined</p>
    </div>

</div>

{{-- ── Row 2: Receivables / Payables / Tax ──────────────────────────────── --}}
<div class="grid grid-cols-3 gap-4 mb-5">

    {{-- Receivables --}}
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Receivables</p>
            <a href="/invoices" class="text-xs text-blue-500 hover:text-blue-700">View all →</a>
        </div>
        <p class="text-xl font-bold text-gray-800">AED {{ number_format($receivables->total ?? 0, 0) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">{{ $receivables->count ?? 0 }} unpaid invoice(s)</p>
        @if(($receivables->overdue ?? 0) > 0)
        <div class="mt-2 flex items-center gap-1.5 text-xs text-red-600 bg-red-50 px-2 py-1 rounded">
            <span>⚠</span>
            <span>AED {{ number_format($receivables->overdue, 0) }} overdue ({{ $receivables->overdue_count }})</span>
        </div>
        @else
        <div class="mt-2 text-xs text-green-600">✓ No overdue invoices</div>
        @endif
    </div>

    {{-- Payables --}}
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Payables</p>
            <a href="/bills" class="text-xs text-blue-500 hover:text-blue-700">View all →</a>
        </div>
        <p class="text-xl font-bold text-gray-800">AED {{ number_format($payables->total ?? 0, 0) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">{{ $payables->count ?? 0 }} unpaid bill(s)</p>
        @if(($payables->overdue ?? 0) > 0)
        <div class="mt-2 flex items-center gap-1.5 text-xs text-red-600 bg-red-50 px-2 py-1 rounded">
            <span>⚠</span>
            <span>AED {{ number_format($payables->overdue, 0) }} overdue</span>
        </div>
        @else
        <div class="mt-2 text-xs text-green-600">✓ No overdue bills</div>
        @endif
    </div>

    {{-- Tax snapshot --}}
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tax Snapshot</p>
            <a href="/vat" class="text-xs text-blue-500 hover:text-blue-700">VAT →</a>
        </div>
        <div class="space-y-2">
            <div class="flex justify-between items-center text-xs">
                <span class="text-gray-500">VAT Collected</span>
                <span class="font-medium text-gray-700">AED {{ number_format($vatCollected, 0) }}</span>
            </div>
            <div class="flex justify-between items-center text-xs">
                <span class="text-gray-500">VAT Reclaimable</span>
                <span class="font-medium text-gray-700">AED {{ number_format($vatReclaimable, 0) }}</span>
            </div>
            <div class="flex justify-between items-center text-xs border-t border-gray-100 pt-1.5">
                <span class="font-semibold text-gray-600">VAT Net Due</span>
                <span class="font-bold {{ $vatNetDue > 0 ? 'text-red-600' : 'text-green-700' }}">
                    AED {{ number_format(abs($vatNetDue), 0) }}
                    @if($vatNetDue < 0)<span class="font-normal text-xs">(refund)</span>@endif
                </span>
            </div>
            @if($estimatedCT > 0)
            <div class="flex justify-between items-center text-xs border-t border-gray-100 pt-1.5">
                <span class="text-gray-500">Est. Corp Tax {{ $year }}</span>
                <a href="/ct" class="font-semibold text-orange-600 hover:text-orange-700">AED {{ number_format($estimatedCT, 0) }}</a>
            </div>
            @endif
        </div>
    </div>

</div>

{{-- ── Row 3: Chart + Recent Invoices ──────────────────────────────────────── --}}
<div class="grid grid-cols-5 gap-4 mb-5">

    {{-- Monthly chart --}}
    <div class="col-span-2 bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Revenue vs Expenses — Last 6 Months</p>
        @php
            $maxVal = 1;
            foreach ($monthlyData as $m) {
                $maxVal = max($maxVal, (float)$m->revenue, (float)$m->expenses);
            }
        @endphp
        @if(count($monthlyData) > 0)
        <div class="flex items-end gap-2 h-28">
            @foreach($monthlyData as $m)
            @php
                $rH = max(2, round(((float)$m->revenue  / $maxVal) * 96));
                $eH = max(2, round(((float)$m->expenses / $maxVal) * 96));
            @endphp
            <div class="flex-1 flex flex-col items-center gap-0.5">
                <div class="w-full flex gap-0.5 items-end" style="height:96px">
                    <div class="flex-1 bg-green-500 rounded-t opacity-80" style="height:{{ $rH }}px" title="Rev: AED {{ number_format($m->revenue,0) }}"></div>
                    <div class="flex-1 bg-red-400 rounded-t opacity-80"   style="height:{{ $eH }}px" title="Exp: AED {{ number_format($m->expenses,0) }}"></div>
                </div>
                <span class="text-xs text-gray-400">{{ $m->month_label }}</span>
            </div>
            @endforeach
        </div>
        <div class="flex gap-4 mt-3">
            <div class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-2.5 h-2.5 bg-green-500 rounded-sm inline-block"></span>Revenue</div>
            <div class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-2.5 h-2.5 bg-red-400 rounded-sm inline-block"></span>Expenses</div>
        </div>
        @else
        <div class="h-28 flex items-center justify-center text-xs text-gray-400">No posted journal data yet</div>
        @endif
    </div>

    {{-- Recent Invoices --}}
    <div class="col-span-3 bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Recent Invoices</p>
            <div class="flex gap-2">
                <a href="/invoices/create" class="text-xs bg-green-700 text-white px-2.5 py-1 rounded hover:bg-green-800">+ New</a>
                <a href="/invoices" class="text-xs text-blue-500 hover:text-blue-700">All →</a>
            </div>
        </div>
        @if($recentInvoices->count())
        <table class="w-full text-xs">
            <thead>
                <tr class="text-gray-400 uppercase border-b border-gray-50 bg-gray-50">
                    <th class="px-4 py-2 text-left">Invoice</th>
                    <th class="px-4 py-2 text-left">Customer</th>
                    <th class="px-4 py-2 text-right">Amount</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-left">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentInvoices as $inv)
                @php
                    $sc = match($inv->status) {
                        'PAID'       => 'bg-green-100 text-green-700',
                        'SENT'       => 'bg-blue-100 text-blue-700',
                        'OVERDUE'    => 'bg-red-100 text-red-700',
                        'DRAFT'      => 'bg-gray-100 text-gray-500',
                        default      => 'bg-yellow-100 text-yellow-700',
                    };
                    $isOverdue = $inv->due_date && $inv->due_date < date('Y-m-d') && !in_array($inv->status, ['PAID','CANCELLED']);
                @endphp
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2.5">
                        <a href="/invoices/{{ $inv->id }}" class="text-blue-600 hover:text-blue-800 font-medium">{{ $inv->invoice_number }}</a>
                    </td>
                    <td class="px-4 py-2.5 text-gray-600 max-w-28 truncate">{{ $inv->customer_name }}</td>
                    <td class="px-4 py-2.5 text-right font-medium text-gray-700">{{ number_format($inv->total_amount, 0) }}</td>
                    <td class="px-4 py-2.5 text-center">
                        <span class="px-1.5 py-0.5 rounded-full text-xs {{ $isOverdue ? 'bg-red-100 text-red-700' : $sc }}">
                            {{ $isOverdue ? 'OVERDUE' : $inv->status }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-gray-400">{{ date('d M', strtotime($inv->invoice_date)) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="px-4 py-8 text-center text-xs text-gray-400">No invoices yet</div>
        @endif
    </div>

</div>

{{-- ── Row 4: Recent Bills + Quick Actions ──────────────────────────────────── --}}
<div class="grid grid-cols-5 gap-4">

    {{-- Recent Bills --}}
    <div class="col-span-3 bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Recent Bills</p>
            <div class="flex gap-2">
                <a href="/bills/create" class="text-xs bg-gray-700 text-white px-2.5 py-1 rounded hover:bg-gray-800">+ New</a>
                <a href="/bills" class="text-xs text-blue-500 hover:text-blue-700">All →</a>
            </div>
        </div>
        @if($recentBills->count())
        <table class="w-full text-xs">
            <thead>
                <tr class="text-gray-400 uppercase border-b border-gray-50 bg-gray-50">
                    <th class="px-4 py-2 text-left">Bill</th>
                    <th class="px-4 py-2 text-left">Vendor</th>
                    <th class="px-4 py-2 text-right">Amount</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-left">Due</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentBills as $bill)
                @php
                    $sc = match($bill->status) {
                        'PAID'    => 'bg-green-100 text-green-700',
                        'DRAFT'   => 'bg-gray-100 text-gray-500',
                        default   => 'bg-yellow-100 text-yellow-700',
                    };
                    $isOverdue = $bill->due_date && $bill->due_date < date('Y-m-d') && !in_array($bill->status, ['PAID','CANCELLED']);
                @endphp
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2.5">
                        <a href="/bills/{{ $bill->id }}" class="text-blue-600 hover:text-blue-800 font-medium">{{ $bill->bill_number }}</a>
                    </td>
                    <td class="px-4 py-2.5 text-gray-600 max-w-28 truncate">{{ $bill->vendor_name }}</td>
                    <td class="px-4 py-2.5 text-right font-medium text-gray-700">{{ number_format($bill->total_amount, 0) }}</td>
                    <td class="px-4 py-2.5 text-center">
                        <span class="px-1.5 py-0.5 rounded-full text-xs {{ $isOverdue ? 'bg-red-100 text-red-700' : $sc }}">
                            {{ $isOverdue ? 'OVERDUE' : $bill->status }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-gray-400">
                        {{ $bill->due_date ? date('d M', strtotime($bill->due_date)) : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="px-4 py-8 text-center text-xs text-gray-400">No bills yet</div>
        @endif
    </div>

    {{-- Quick Actions + P&L mini summary --}}
    <div class="col-span-2 flex flex-col gap-4">

        {{-- P&L mini --}}
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">P&L Summary {{ $year }}</p>
                <a href="/reports" class="text-xs text-blue-500 hover:text-blue-700">Full Report →</a>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-xs text-gray-500">Revenue</span>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">AED {{ number_format($revenue, 0) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-red-400 rounded-full"></div>
                        <span class="text-xs text-gray-500">Expenses</span>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">AED {{ number_format($expenses, 0) }}</span>
                </div>
                {{-- visual ratio bar --}}
                @if($revenue > 0)
                <div class="h-1.5 w-full bg-red-100 rounded-full overflow-hidden mt-1">
                    <div class="h-full bg-green-500 rounded-full"
                         style="width: {{ min(100, round(($netProfit / $revenue) * 100 + 50)) }}%"></div>
                </div>
                @endif
                <div class="flex justify-between items-center border-t border-gray-100 pt-2">
                    <span class="text-xs font-bold text-gray-700">Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</span>
                    <span class="text-sm font-bold {{ $netProfit >= 0 ? 'text-green-700' : 'text-red-600' }}">
                        AED {{ number_format(abs($netProfit), 0) }}
                    </span>
                </div>
                @if($estimatedCT > 0)
                <div class="flex justify-between items-center text-xs text-orange-600 bg-orange-50 px-2 py-1 rounded">
                    <span>Est. Corp Tax 9%</span>
                    <span class="font-semibold">AED {{ number_format($estimatedCT, 0) }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Quick Actions</p>
            <div class="grid grid-cols-2 gap-2">
                <a href="/invoices/create"
                   class="flex items-center gap-2 text-xs bg-green-50 text-green-700 border border-green-200 px-3 py-2 rounded hover:bg-green-100 font-medium">
                    🧾 New Invoice
                </a>
                <a href="/bills/create"
                   class="flex items-center gap-2 text-xs bg-gray-50 text-gray-700 border border-gray-200 px-3 py-2 rounded hover:bg-gray-100 font-medium">
                    📋 New Bill
                </a>
                <a href="/journals/create"
                   class="flex items-center gap-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 px-3 py-2 rounded hover:bg-blue-100 font-medium">
                    📓 Journal Entry
                </a>
                <a href="/reports"
                   class="flex items-center gap-2 text-xs bg-purple-50 text-purple-700 border border-purple-200 px-3 py-2 rounded hover:bg-purple-100 font-medium">
                    📈 Reports
                </a>
                <a href="/vat"
                   class="flex items-center gap-2 text-xs bg-yellow-50 text-yellow-700 border border-yellow-200 px-3 py-2 rounded hover:bg-yellow-100 font-medium">
                    🧾 VAT Returns
                </a>
                <a href="/ct"
                   class="flex items-center gap-2 text-xs bg-orange-50 text-orange-700 border border-orange-200 px-3 py-2 rounded hover:bg-orange-100 font-medium">
                    🏢 Corp Tax
                </a>
            </div>
        </div>

    </div>

</div>

@endif
@endsection
