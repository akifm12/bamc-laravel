<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a2e; }
        h1 { font-size: 16px; color: #006400; margin-bottom: 2px; }
        h2 { font-size: 12px; color: #444; margin-bottom: 16px; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #f3f4f6; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; border-bottom: 2px solid #e5e7eb; }
        td { padding: 5px 8px; border-bottom: 1px solid #f3f4f6; }
        .section-header { background: #f9fafb; font-weight: bold; padding: 6px 8px; }
        .total-row { font-weight: bold; background: #f3f4f6; }
        .grand-total { font-weight: bold; font-size: 12px; background: #1f2937; color: white; }
        .balanced { color: #15803d; font-weight: bold; }
        .unbalanced { color: #dc2626; font-weight: bold; }
        .text-right { text-align: right; }
        .text-mono { font-family: monospace; font-size: 10px; color: #666; }
        .italic { font-style: italic; color: #666; }
    </style>
</head>
<body>
    <h1>{{ $companyName }}</h1>
    <h2>Balance Sheet as at {{ \Carbon\Carbon::parse($asOf)->format('d M Y') }}</h2>

    <table>
        <thead>
            <tr>
                <th style="width:80px">Code</th>
                <th>Account</th>
                <th class="text-right" style="width:120px">Balance (AED)</th>
            </tr>
        </thead>
        <tbody>
            <!-- Assets -->
            <tr><td colspan="3" class="section-header">ASSETS</td></tr>
            @foreach($assets as $r)
            <tr>
                <td class="text-mono">{{ $r->code }}</td>
                <td>{{ $r->name }}</td>
                <td class="text-right">{{ $r->balance < 0 ? '('.number_format(abs($r->balance),2).')' : number_format($r->balance, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="text-right">Total Assets</td>
                <td class="text-right">{{ number_format($totalAssets, 2) }}</td>
            </tr>

            <tr><td colspan="3" style="padding:8px"></td></tr>

            <!-- Liabilities -->
            <tr><td colspan="3" class="section-header">LIABILITIES</td></tr>
            @foreach($liabilities as $r)
            <tr>
                <td class="text-mono">{{ $r->code }}</td>
                <td>{{ $r->name }}</td>
                <td class="text-right">{{ number_format($r->balance, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="text-right">Total Liabilities</td>
                <td class="text-right">{{ number_format($totalLiabilities, 2) }}</td>
            </tr>

            <tr><td colspan="3" style="padding:4px"></td></tr>

            <!-- Equity -->
            <tr><td colspan="3" class="section-header">EQUITY</td></tr>
            @foreach($equity as $r)
            <tr>
                <td class="text-mono">{{ $r->code }}</td>
                <td>{{ $r->name }}</td>
                <td class="text-right">{{ number_format($r->balance, 2) }}</td>
            </tr>
            @endforeach
            <tr>
                <td class="text-mono"></td>
                <td class="italic">YTD Net Profit / (Loss)</td>
                <td class="text-right">{{ $ytdProfit < 0 ? '('.number_format(abs($ytdProfit),2).')' : number_format($ytdProfit, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="2" class="text-right">Total Equity</td>
                <td class="text-right">{{ number_format($totalEquity, 2) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td colspan="2" class="text-right">TOTAL LIABILITIES + EQUITY</td>
                <td class="text-right">AED {{ number_format($totalLE, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-right {{ abs($totalAssets - $totalLE) < 0.01 ? 'balanced' : 'unbalanced' }}">
                    {{ abs($totalAssets - $totalLE) < 0.01 ? 'BALANCE SHEET BALANCES' : '⚠ Out of balance by AED '.number_format(abs($totalAssets - $totalLE), 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    <p style="font-size:10px;color:#9ca3af;margin-top:24px;">
        Generated on {{ now()->format('d M Y H:i') }}
    </p>
</body>
</html>