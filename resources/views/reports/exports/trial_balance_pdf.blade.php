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
        .section-header { background: #f9fafb; font-weight: bold; padding: 6px 8px; font-size: 11px; }
        .total-row { font-weight: bold; background: #f3f4f6; }
        .grand-total { font-weight: bold; font-size: 12px; background: #f0fdf4; }
        .text-right { text-align: right; }
        .text-mono { font-family: monospace; font-size: 10px; color: #666; }
        .balanced { color: #15803d; font-weight: bold; }
        .unbalanced { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $companyName }}</h1>
    <h2>Trial Balance - {{ $dateFrom }} to {{ $dateTo }}</h2>

    @php
    $typeLabels = [
        'ASSET' => 'Assets', 'LIABILITY' => 'Liabilities',
        'EQUITY' => 'Equity', 'REVENUE' => 'Revenue', 'EXPENSE' => 'Expenses'
    ];
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width:80px">Code</th>
                <th>Account Name</th>
                <th>Type</th>
                <th class="text-right" style="width:100px">Debit</th>
                <th class="text-right" style="width:100px">Credit</th>
                <th class="text-right" style="width:100px">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grouped as $type => $rows)
            <tr>
                <td colspan="6" class="section-header">{{ $typeLabels[$type] ?? $type }}</td>
            </tr>
            @foreach($rows as $row)
            <tr>
                <td class="text-mono">{{ $row->code }}</td>
                <td>{{ $row->name }}</td>
                <td>{{ $type }}</td>
                <td class="text-right">{{ $row->total_debit > 0 ? number_format($row->total_debit, 2) : '-' }}</td>
                <td class="text-right">{{ $row->total_credit > 0 ? number_format($row->total_credit, 2) : '-' }}</td>
                <td class="text-right">{{ number_format($row->total_debit - $row->total_credit, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-right">Subtotal</td>
                <td class="text-right">{{ number_format(collect($rows)->sum('total_debit'), 2) }}</td>
                <td class="text-right">{{ number_format(collect($rows)->sum('total_credit'), 2) }}</td>
                <td class="text-right">{{ number_format(collect($rows)->sum(fn($r) => $r->total_debit - $r->total_credit), 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td colspan="3" class="text-right">GRAND TOTAL</td>
                <td class="text-right">AED {{ number_format($totalDebit, 2) }}</td>
                <td class="text-right">AED {{ number_format($totalCredit, 2) }}</td>
                <td class="text-right {{ abs($totalDebit - $totalCredit) < 0.01 ? 'balanced' : 'unbalanced' }}">
                    {{ abs($totalDebit - $totalCredit) < 0.01 ? 'BALANCED' : 'OUT OF BALANCE' }}
                </td>
            </tr>
        </tfoot>
    </table>

    <p style="font-size:10px;color:#9ca3af;margin-top:24px;">
        Generated on {{ now()->format('d M Y H:i') }}
    </p>
</body>
</html>