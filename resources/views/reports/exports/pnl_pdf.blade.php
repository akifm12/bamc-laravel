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
        .net-profit { font-weight: bold; font-size: 13px; background: #f0fdf4; color: #15803d; }
        .net-loss { font-weight: bold; font-size: 13px; background: #fef2f2; color: #dc2626; }
        .text-right { text-align: right; }
        .text-mono { font-family: monospace; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <h1>{{ $companyName }}</h1>
    <h2>Profit & Loss Statement — {{ $dateFrom }} to {{ $dateTo }}</h2>

    <table>
        <thead>
            <tr>
                <th style="width:80px">Code</th>
                <th>Account</th>
                <th class="text-right" style="width:120px">Amount (AED)</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="3" class="section-header">REVENUE</td></tr>
            @foreach($revenue as $r)
            <tr>
                <td class="text-mono">{{ $r->code }}</td>
                <td>{{ $r->name }}</td>
                <td class="text-right">{{ number_format($r->balance, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="text-right">Total Revenue</td>
                <td class="text-right">{{ number_format($totalRevenue, 2) }}</td>
            </tr>

            <tr><td colspan="3" style="padding:8px"></td></tr>

            <tr><td colspan="3" class="section-header">EXPENSES</td></tr>
            @foreach($expenses as $r)
            <tr>
                <td class="text-mono">{{ $r->code }}</td>
                <td>{{ $r->name }}</td>
                <td class="text-right">{{ number_format(abs($r->balance), 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="text-right">Total Expenses</td>
                <td class="text-right">{{ number_format($totalExpenses, 2) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="{{ $netProfit >= 0 ? 'net-profit' : 'net-loss' }}">
                <td colspan="2" class="text-right">{{ $netProfit >= 0 ? 'NET PROFIT' : 'NET LOSS' }}</td>
                <td class="text-right">AED {{ number_format(abs($netProfit), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <p style="font-size:10px;color:#9ca3af;margin-top:24px;">
        Generated on {{ now()->format('d M Y H:i') }}
    </p>
</body>
</html>