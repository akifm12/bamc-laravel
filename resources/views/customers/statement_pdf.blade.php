<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 2cm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 0; }

    /* ── Clearfix ── */
    .cf:after { content: ""; display: table; clear: both; }

    /* ── Header ── */
    .header { border-bottom: 2px solid #15803d; padding-bottom: 14px; margin-bottom: 20px; }
    .header-left  { float: left; width: 55%; }
    .header-right { float: right; width: 40%; text-align: right; }
    .logo         { max-height: 48px; max-width: 160px; margin-bottom: 4px; }
    .company-name { font-size: 17px; font-weight: bold; color: #1d4ed8; }
    .company-sub  { font-size: 9px; color: #6b7280; margin-top: 2px; }
    .doc-title    { font-size: 15px; font-weight: bold; color: #1f2937; }
    .doc-sub      { font-size: 9px; color: #6b7280; margin-top: 2px; }

    /* ── Info boxes ── */
    .info-grid  { margin-bottom: 18px; }
    .info-left  { float: left;  width: 47%; }
    .info-right { float: right; width: 47%; }
    .info-box   { background: #f9fafb; border: 1px solid #e5e7eb; padding: 9px 12px; }
    .info-label { font-size: 8px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
    .info-value { font-size: 11px; font-weight: bold; color: #1f2937; }
    .info-small { font-size: 10px; color: #6b7280; margin-top: 2px; }

    /* ── Table ── */
    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; table-layout: fixed; }
    thead th { background: #1f2937; color: #fff; font-size: 8px; text-transform: uppercase; letter-spacing: 0.4px; padding: 6px 7px; text-align: left; }
    thead th.num { text-align: right; }
    tbody tr { border-bottom: 1px solid #f3f4f6; }
    tbody tr.opening { background: #eff6ff; }
    tbody tr.closing { background: #f3f4f6; border-top: 2px solid #374151; }
    tbody tr.alt     { background: #fafafa; }
    td { padding: 5px 7px; font-size: 10px; word-wrap: break-word; }
    td.num { text-align: right; }
    td.ref { font-size: 9px; }
    .type-inv { background: #dbeafe; color: #1d4ed8; padding: 1px 4px; font-size: 8px; }
    .type-pay { background: #dcfce7; color: #15803d; padding: 1px 4px; font-size: 8px; }
    .bal-dr  { color: #1f2937; font-weight: bold; }
    .bal-cr  { color: #15803d; font-weight: bold; }
    .bal-nil { color: #9ca3af; }

    /* ── Footer ── */
    .footer      { margin-top: 28px; border-top: 1px solid #e5e7eb; padding-top: 8px; font-size: 8px; color: #9ca3af; }
    .footer-left  { float: left; }
    .footer-right { float: right; }
</style>
</head>
<body>

    <!-- Header -->
    <div class="header cf">
        <div class="header-left">
            @if($logoBase64)<img src="{{ $logoBase64 }}" class="logo" alt="Logo"><br>@endif
            <div class="company-name">{{ $company->name ?? 'Company' }}</div>
            @if(!empty($company->trn))<div class="company-sub">TRN: {{ $company->trn }}</div>@endif
            @if(!empty($company->address))<div class="company-sub">{{ $company->address }}</div>@endif
        </div>
        <div class="header-right">
            <div class="doc-title">Statement of Account</div>
            <div class="doc-sub">Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</div>
            <div class="doc-sub">Generated: {{ date('d M Y') }}</div>
        </div>
    </div>

    <!-- Customer & Summary info -->
    <div class="info-grid cf">
        <div class="info-left">
            <div class="info-box">
                <div class="info-label">Bill To</div>
                <div class="info-value">{{ $customer->name }}</div>
                @if(!empty($customer->trn))<div class="info-small">TRN: {{ $customer->trn }}</div>@endif
                @if(!empty($customer->email))<div class="info-small">{{ $customer->email }}</div>@endif
                @if(!empty($customer->phone))<div class="info-small">{{ $customer->phone }}</div>@endif
            </div>
        </div>
        <div class="info-right">
            <div class="info-box">
                <div class="info-label">Account Summary</div>
                <div class="info-small" style="margin-top:4px">Opening Balance: <strong>AED {{ number_format(abs($openingBalance), 2) }}{{ $openingBalance < 0 ? ' Cr' : ($openingBalance > 0 ? ' Dr' : '') }}</strong></div>
                <div class="info-small">Total Invoiced: <strong>AED {{ number_format($transactions->sum('debit'), 2) }}</strong></div>
                <div class="info-small">Total Payments: <strong>AED {{ number_format($transactions->sum('credit'), 2) }}</strong></div>
                <div class="info-small" style="margin-top:5px; font-size:11px">
                    Balance Due: <strong style="color:{{ $closingBalance > 0 ? '#dc2626' : '#15803d' }}">AED {{ number_format(abs($closingBalance), 2) }}{{ $closingBalance < 0 ? ' Cr' : ($closingBalance > 0 ? ' Dr' : '') }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions table -->
    <table>
        <thead>
            <tr>
                <th style="width:68px">Date</th>
                <th style="width:50px">Type</th>
                <th style="width:88px">Reference</th>
                <th>Description</th>
                <th class="num" style="width:82px">Debit (AED)</th>
                <th class="num" style="width:82px">Credit (AED)</th>
                <th class="num" style="width:88px">Balance (AED)</th>
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
                    {{ number_format(abs($openingBalance), 2) }}{{ $openingBalance < 0 ? ' Cr' : ($openingBalance > 0 ? ' Dr' : '') }}
                </td>
            </tr>
            @forelse($transactions as $i => $txn)
            <tr{{ $i % 2 === 1 ? ' class="alt"' : '' }}>
                <td>{{ \Carbon\Carbon::parse($txn->date)->format('d M Y') }}</td>
                <td><span class="{{ $txn->type === 'Invoice' ? 'type-inv' : 'type-pay' }}">{{ $txn->type }}</span></td>
                <td class="ref">{{ $txn->reference }}</td>
                <td>{{ $txn->description }}</td>
                <td class="num">{{ $txn->debit > 0 ? number_format($txn->debit, 2) : '—' }}</td>
                <td class="num {{ $txn->credit > 0 ? 'bal-cr' : '' }}">{{ $txn->credit > 0 ? number_format($txn->credit, 2) : '—' }}</td>
                <td class="num {{ $txn->balance > 0 ? 'bal-dr' : ($txn->balance < 0 ? 'bal-cr' : 'bal-nil') }}">
                    {{ number_format(abs($txn->balance), 2) }}{{ $txn->balance < 0 ? ' Cr' : ($txn->balance > 0 ? ' Dr' : '') }}
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center; color:#9ca3af; padding:14px">No transactions in this period.</td></tr>
            @endforelse
            <tr class="closing">
                <td colspan="4"><strong>Closing Balance</strong></td>
                <td class="num"><strong>{{ number_format($transactions->sum('debit'), 2) }}</strong></td>
                <td class="num bal-cr"><strong>{{ number_format($transactions->sum('credit'), 2) }}</strong></td>
                <td class="num {{ $closingBalance > 0 ? 'bal-dr' : ($closingBalance < 0 ? 'bal-cr' : 'bal-nil') }}">
                    <strong>AED {{ number_format(abs($closingBalance), 2) }}{{ $closingBalance < 0 ? ' Cr' : ($closingBalance > 0 ? ' Dr' : '') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="footer cf">
        <div class="footer-left">{{ $company->name ?? '' }} · Statement of Account · {{ $customer->name }}</div>
        <div class="footer-right">Generated {{ date('d M Y H:i') }}</div>
    </div>

</body>
</html>
