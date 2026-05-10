@extends('layouts.app')

@section('title', 'Balance Sheet')

@section('content')

<!-- Filter -->
<form method="GET" action="/reports/balance-sheet" class="bg-white rounded-lg border border-gray-200 p-4 mb-4 flex gap-3 items-end">
    <div>
        <label class="text-xs text-gray-500 block mb-1">As of Date</label>
        <input type="date" name="as_of" value="{{ $asOf }}"
            class="border border-gray-200 rounded px-3 py-1.5 text-sm">
    </div>
    <button type="submit" class="bg-gray-700 text-white text-sm px-4 py-1.5 rounded hover:bg-gray-800">Run Report</button>
    <a href="/reports" class="text-sm text-gray-400 px-2 py-1.5">← Reports</a>
</form>
<!-- Export buttons -->
<div class="flex gap-2 mb-4">
    <a href="/reports/balance-sheet/export?as_of={{ $asOf }}&format=csv"
        class="text-xs bg-white border border-gray-200 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50">
        📄 CSV
    </a>
    <a href="/reports/balance-sheet/export?as_of={{ $asOf }}&format=excel"
        class="text-xs bg-white border border-gray-200 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50">
        📊 Excel
    </a>
    <a href="/reports/balance-sheet/export?as_of={{ $asOf }}&format=pdf"
        class="text-xs bg-white border border-gray-200 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50">
        📑 PDF
    </a>
</div>
<p class="text-xs text-gray-400 mb-4">As at {{ \Carbon\Carbon::parse($asOf)->format('d M Y') }}</p>

<div class="grid grid-cols-2 gap-4">

    <!-- ASSETS -->
    <div>
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="font-semibold text-gray-700 text-sm">🟦 Assets</span>
            </div>
            <table class="w-full text-sm">
                <tbody>
                    @foreach($assets as $row)
                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-xs text-gray-400 w-24">{{ $row->code }}</td>
                        <td class="px-4 py-2 text-gray-800">{{ $row->name }}</td>
                        <td class="px-4 py-2 text-right w-36 {{ $row->balance < 0 ? 'text-red-600' : 'text-gray-800' }}">
                            {{ $row->balance < 0 ? '(' . number_format(abs($row->balance), 2) . ')' : number_format($row->balance, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 border-t-2 border-gray-300 font-bold">
                        <td colspan="2" class="px-4 py-2 text-sm">TOTAL ASSETS</td>
                        <td class="px-4 py-2 text-right text-blue-700">AED {{ number_format($totalAssets, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- LIABILITIES & EQUITY -->
    <div>
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="font-semibold text-gray-700 text-sm">🟥 Liabilities</span>
            </div>
            <table class="w-full text-sm">
                <tbody>
                    @foreach($liabilities as $row)
                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-xs text-gray-400 w-24">{{ $row->code }}</td>
                        <td class="px-4 py-2 text-gray-800">{{ $row->name }}</td>
                        <td class="px-4 py-2 text-right w-36 {{ $row->balance < 0 ? 'text-red-600' : 'text-gray-800' }}">
                            {{ $row->balance < 0 ? '(' . number_format(abs($row->balance), 2) . ')' : number_format($row->balance, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 border-t border-gray-100 font-semibold">
                        <td colspan="2" class="px-4 py-2 text-xs text-gray-500 text-right">TOTAL LIABILITIES</td>
                        <td class="px-4 py-2 text-right text-red-700">AED {{ number_format($totalLiabilities, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="font-semibold text-gray-700 text-sm">🟩 Equity</span>
            </div>
            <table class="w-full text-sm">
                <tbody>
                    @foreach($equity as $row)
                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-xs text-gray-400 w-24">{{ $row->code }}</td>
                        <td class="px-4 py-2 text-gray-800">{{ $row->name }}</td>
                        <td class="px-4 py-2 text-right w-36">{{ number_format($row->balance, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-xs text-gray-400"></td>
                        <td class="px-4 py-2 text-gray-600 italic">YTD Net Profit / (Loss)</td>
                        <td class="px-4 py-2 text-right {{ $ytdProfit < 0 ? 'text-red-600' : 'text-gray-800' }}">
                            {{ $ytdProfit < 0 ? '(' . number_format(abs($ytdProfit), 2) . ')' : number_format($ytdProfit, 2) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 border-t border-gray-100 font-semibold">
                        <td colspan="2" class="px-4 py-2 text-xs text-gray-500 text-right">TOTAL EQUITY</td>
                        <td class="px-4 py-2 text-right text-green-700">AED {{ number_format($totalEquity, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Total L+E -->
        <div class="bg-gray-800 rounded-lg p-4 text-white flex justify-between items-center">
            <span class="font-bold">TOTAL LIABILITIES + EQUITY</span>
            <span class="font-bold text-lg">AED {{ number_format($totalLE, 2) }}</span>
        </div>

        <!-- Balance check -->
        <div class="mt-2 text-center text-xs">
            @if(abs($totalAssets - $totalLE) < 0.01)
                <span class="text-green-600 font-semibold">✅ Balance Sheet balances</span>
            @else
                <span class="text-red-600 font-semibold">⚠️ Out of balance by AED {{ number_format(abs($totalAssets - $totalLE), 2) }}</span>
            @endif
        </div>
    </div>

</div>

@endsection