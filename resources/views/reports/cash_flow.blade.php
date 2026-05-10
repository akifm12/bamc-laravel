@extends('layouts.app')

@section('title', 'Cash Flow Statement')

@section('content')

<form method="GET" action="/reports/cash-flow" class="bg-white rounded-lg border border-gray-200 p-4 mb-4 flex gap-3 items-end">
    <div>
        <label class="text-xs text-gray-500 block mb-1">From</label>
        <input type="date" name="date_from" value="{{ $dateFrom }}"
            class="border border-gray-200 rounded px-3 py-1.5 text-sm">
    </div>
    <div>
        <label class="text-xs text-gray-500 block mb-1">To</label>
        <input type="date" name="date_to" value="{{ $dateTo }}"
            class="border border-gray-200 rounded px-3 py-1.5 text-sm">
    </div>
    <button type="submit" class="bg-gray-700 text-white text-sm px-4 py-1.5 rounded hover:bg-gray-800">Run Report</button>
    <a href="/reports" class="text-sm text-gray-400 px-2 py-1.5">← Reports</a>
</form>

<div class="max-w-3xl">

    <!-- Operating Activities -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">Operating Activities</span>
        </div>
        <table class="w-full text-sm">
            <tbody>
                <tr class="border-b border-gray-50">
                    <td class="px-4 py-2 text-gray-600 italic">Revenue</td>
                    <td class="px-4 py-2 text-right text-green-700 font-medium">AED {{ number_format($revenue, 2) }}</td>
                </tr>
                <tr class="border-b border-gray-50">
                    <td class="px-4 py-2 text-gray-600 italic">Expenses</td>
                    <td class="px-4 py-2 text-right text-red-600 font-medium">(AED {{ number_format($expenses, 2) }})</td>
                </tr>
                @foreach($operating as $row)
                    @if($row->account_type === 'REVENUE' || $row->account_type === 'EXPENSE')
                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="px-4 py-2 text-gray-500 pl-8 text-xs">
                            {{ $row->code }} — {{ $row->name }}
                        </td>
                        <td class="px-4 py-2 text-right text-xs text-gray-600">
                            @php $bal = $row->account_type === 'REVENUE' ? $row->cr - $row->dr : $row->dr - $row->cr; @endphp
                            {{ $bal < 0 ? '('.number_format(abs($bal),2).')' : number_format($bal, 2) }}
                        </td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t border-gray-200 font-semibold">
                    <td class="px-4 py-2">Net Operating Cash Flow</td>
                    <td class="px-4 py-2 text-right {{ $netIncome >= 0 ? 'text-green-700' : 'text-red-600' }}">
                        AED {{ number_format($netIncome, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Investing Activities -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">Investing Activities (Asset Changes)</span>
        </div>
        <table class="w-full text-sm">
            <tbody>
                @foreach($assetChanges as $row)
                @php $change = $row->dr - $row->cr; @endphp
                @if(abs($change) > 0.01)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 text-gray-600">{{ $row->code }} — {{ $row->name }}</td>
                    <td class="px-4 py-2 text-right {{ $change > 0 ? 'text-red-600' : 'text-green-700' }}">
                        {{ $change > 0 ? '('.number_format($change,2).')' : number_format(abs($change), 2) }}
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t border-gray-200 font-semibold">
                    <td class="px-4 py-2">Net Investing Cash Flow</td>
                    <td class="px-4 py-2 text-right {{ $netAssetChange > 0 ? 'text-red-600' : 'text-green-700' }}">
                        {{ $netAssetChange > 0 ? '('.number_format($netAssetChange,2).')' : 'AED '.number_format(abs($netAssetChange), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Financing Activities -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">Financing Activities (Liability & Equity Changes)</span>
        </div>
        <table class="w-full text-sm">
            <tbody>
                @foreach($liabilityChanges->merge($equityChanges) as $row)
                @php $change = $row->cr - $row->dr; @endphp
                @if(abs($change) > 0.01)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 text-gray-600">{{ $row->code }} — {{ $row->name }}</td>
                    <td class="px-4 py-2 text-right {{ $change >= 0 ? 'text-green-700' : 'text-red-600' }}">
                        {{ $change < 0 ? '('.number_format(abs($change),2).')' : number_format($change, 2) }}
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t border-gray-200 font-semibold">
                    <td class="px-4 py-2">Net Financing Cash Flow</td>
                    <td class="px-4 py-2 text-right {{ ($netLiabilityChange + $netEquityChange) >= 0 ? 'text-green-700' : 'text-red-600' }}">
                        AED {{ number_format($netLiabilityChange + $netEquityChange, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Net Cash Flow -->
    <div class="rounded-lg border p-4 {{ $netCashFlow >= 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
        <div class="flex justify-between items-center">
            <span class="font-bold text-gray-700">Net Cash Flow</span>
            <span class="font-bold text-xl {{ $netCashFlow >= 0 ? 'text-green-700' : 'text-red-700' }}">
                AED {{ number_format(abs($netCashFlow), 2) }}
                {{ $netCashFlow >= 0 ? '(Inflow)' : '(Outflow)' }}
            </span>
        </div>
        <p class="text-xs text-gray-400 mt-1">{{ $dateFrom }} to {{ $dateTo }}</p>
    </div>

</div>

@endsection