<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 32px 36px; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; }
    .page { padding: 0; }

    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 2px solid #15803d; padding-bottom: 16px; }
    .company-name { font-size: 18px; font-weight: bold; color: #15803d; }
    .company-sub  { font-size: 10px; color: #6b7280; margin-top: 2px; }
    .doc-title    { font-size: 16px; font-weight: bold; color: #1f2937; text-align: right; }
    .doc-sub      { font-size: 10px; color: #6b7280; text-align: right; margin-top: 2px; }

    .info-grid { display: flex; justify-content: space-between; margin-bottom: 20px; }
    .info-box  { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px 14px; width: 48%; }
    .info-label { font-size: 9px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
    .info-value { font-size: 11px; font-weight: 600; color: #1f2937; }
    .info-small { font-size: 10px; color: #6b7280; margin-top: 1px; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    thead th { background: #1f2937; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; padding: 7px 8px; text-align: left; }
    thead th.num { text-align: right; }
    tbody tr { border-bottom: 1px solid #f3f4f6; }
    tbody tr.opening { background: #eff6ff; }
    tbody tr.closing { background: #f3f4f6; border-top: 2px solid #374151; }
    tbody tr:nth-child(even):not(.opening):not(.closing) { background: #fafafa; }
    td { padding: 6px 8px; font-size: 10px; }
    td.num { text-align: right; }
    td.ref { font-family: monospace; font-size: 10px; }
    .type-inv { background: #dbeafe; color: #1d4ed8; padding: 1px 5px; border-radius: 3px; font-size: 9px; }
    .type-pay { background: #dcfce7; color: #15803d; padding: 1px 5px; border-radius: 3px; font-size: 9px; }
    .bal-dr { color: #1f2937; font-weight: 600; }
    .bal-cr { color: #15803d; font-weight: 600; }
    .bal-nil { color: #9ca3af; }

    .summary { display: flex; justify-content: flex-end; margin-top: 8px; }
    .summary-box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px 16px; min-width: 220px; }
    .summary-row { display: flex; justify-content: space-between; gap: 24px; padding: 3px 0; font-size: 10px; }
    .summary-row.total { border-top: 1px solid #e5e7eb; margin-top: 4px; padding-top: 6px; font-weight: bold; font-size: 11px; }
    .summary-row .label { color: #6b7280; }
    .summary-row .val   { font-weight: 600; }
    .due { color: #dc2626; }
    .credit { color: #15803d; }

    .footer { margin-top: 32px; border-top: 1px solid #e5e7eb; padding-top: 10px; font-size: 9px; color: #9ca3af; display: flex; justify-content: space-between; }
</style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <div class="header">
        <div>
            <div class="company-name">{{ $company->name ?? 'Company' }}</div>
            @if(!empty($company->trn))<div class="company-sub">TRN: {{ $company->trn }}</div>@endif
            @if(!empty($company->address))<div class="company-sub">{{ $company->address }}</div>@endif
        </div>
        <div>
            <div class="doc-title">Statement of Account</div>
            <div class="doc-sub">Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</div>
            <div class="doc-sub">Generated: {{ date('d M Y') }}</div>
        </div>
    </div>

    <!-- Customer & Summary info -->
    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">Bill To</div>
            <div class="info-value">{{ $customer->name }}</div>
            @if(!empty($customer->trn))<div class="info-small">TRN: {{ $customer->trn }}</div>@endif
            @if(!empty($customer->email))<div class="info-small">{{ $customer->email }}</div>@endif
            @if(!empty($customer->phone))<div class="info-small">{{ $customer->phone }}</div>@endif
        </div>
        <div class="info-box">
            <div class="info-label">Account Summary</div>
            <div class="info-small" style="margin-top:4px">Opening Balance: <strong>AED {{ number_format(abs($openingBalance), 2) }} {{ $openingBalance < 0 ? 'Cr' : ($openingBalance > 0 ? 'Dr' : '') }}</strong></div>
            <div class="info-small">Total Invoiced: <strong>AED {{ number_format($transactions->sum('debit'), 2) }}</strong></div>
            <div class="info-small">Total Payments: <strong>AED {{ number_format($transactions->sum('credit'), 2) }}</strong></div>
            <div class="info-small" style="margin-top:4px; font-size:12px">
                Balance Due: <strong class="{{ $closingBalance > 0 ? 'due' : 'credit' }}">AED {{ number_format(abs($closingBalance), 2) }} {{ $closingBalance < 0 ? 'Cr' : ($closingBalance > 0 ? 'Dr' : '') }}</strong>
            </div>
        </div>
    </div>

    <!-- Transactions table -->
    <table>
        <thead>
            <tr>
                <th style="width:70px">Date</th>
                <th style="width:55px">Type</th>
                <th style="width:90px">Reference</th>
                <th>Description</th>
                <th class="num" style="width:85px">Debit (AED)</th>
                <th class="num" style="width:85px">Credit (AED)</th>
                <th class="num" style="width:90px">Balance (AED)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="opening">
                <td>{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}</td>
                <td></td>
                <td class="ref"></td>
                <td><em>Opening Balance</em></td>
                <td class="num"></td>
                <td class="num"></td>
                <td class="num {{ $openingBalance > 0 ? 'bal-dr' : ($openingBalance < 0 ? 'bal-cr' : 'bal-nil') }}">
                    {{ number_format(abs($openingBalance), 2) }}
                    {{ $openingBalance < 0 ? ' Cr' : ($openingBalance > 0 ? ' Dr' : '') }}
                </td>
            </tr>
            @forelse($transactions as $txn)
            <tr>
                <td>{{ \Carbon\Carbon::parse($txn->date)->format('d M Y') }}</td>
                <td><span class="{{ $txn->type === 'Invoice' ? 'type-inv' : 'type-pay' }}">{{ $txn->type }}</span></td>
                <td class="ref">{{ $txn->reference }}</td>
                <td>{{ $txn->description }}</td>
                <td class="num">{{ $txn->debit > 0 ? number_format($txn->debit, 2) : '—' }}</td>
                <td class="num {{ $txn->credit > 0 ? 'bal-cr' : '' }}">{{ $txn->credit > 0 ? number_format($txn->credit, 2) : '—' }}</td>
                <td class="num {{ $txn->balance > 0 ? 'bal-dr' : ($txn->balance < 0 ? 'bal-cr' : 'bal-nil') }}">
                    {{ number_format(abs($txn->balance), 2) }}
                    {{ $txn->balance < 0 ? ' Cr' : ($txn->balance > 0 ? ' Dr' : '') }}
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center; color:#9ca3af; padding:16px">No transactions in this period.</td></tr>
            @endforelse
            <tr class="closing">
                <td colspan="4"><strong>Closing Balance</strong></td>
                <td class="num"><strong>{{ number_format($transactions->sum('debit'), 2) }}</strong></td>
                <td class="num bal-cr"><strong>{{ number_format($transactions->sum('credit'), 2) }}</strong></td>
                <td class="num {{ $closingBalance > 0 ? 'bal-dr' : ($closingBalance < 0 ? 'bal-cr' : 'bal-nil') }}">
                    <strong>AED {{ number_format(abs($closingBalance), 2) }} {{ $closingBalance < 0 ? 'Cr' : ($closingBalance > 0 ? 'Dr' : '') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <span>{{ $company->name ?? '' }} · Statement of Account · {{ $customer->name }}</span>
        <span>Generated {{ date('d M Y H:i') }}</span>
    </div>

</div>
</body>
</html>
