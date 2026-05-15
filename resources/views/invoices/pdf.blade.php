<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a2e; padding: 30px 30px 140px 30px; }

        /* Header */
        .header-table { width: 100%; margin-bottom: 16px; }
        .company-info { font-size: 10px; color: #333; line-height: 1.7; text-align: right; }
        .company-info strong { font-size: 13px; color: #1a1a2e; display: block; margin-bottom: 2px; }

        /* Title bar */
        .title-table { width: 100%; border-bottom: 2px solid #1a1a2e; padding-bottom: 8px; margin-bottom: 14px; }
        .invoice-title { font-size: 22px; font-weight: bold; color: #1a1a2e; }
        .invoice-number { font-size: 22px; font-weight: bold; color: #1a1a2e; text-align: right; }

        /* Client / meta */
        .meta-table { width: 100%; margin-bottom: 20px; }
        .meta-table td { vertical-align: top; font-size: 10px; color: #333; line-height: 1.8; }

        /* Items table */
        .section-title { font-size: 14px; font-weight: bold; color: #1a1a2e; margin-bottom: 8px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        table.items thead th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 7px 6px; font-size: 9px; text-transform: uppercase; color: #6b7280; text-align: left; }
        table.items thead th.right { text-align: right; }
        table.items tbody td { border: 1px solid #d1d5db; padding: 7px 6px; font-size: 11px; vertical-align: top; }
        table.items tbody td.right { text-align: right; }
        table.items tfoot td { border: 1px solid #d1d5db; padding: 7px 6px; font-size: 11px; }
        table.items tfoot td.right { text-align: right; }
        table.items tfoot tr.grand-total td { font-weight: bold; font-size: 12px; background: #f0fdf4; color: #15803d; }
        table.items tfoot tr.words td { font-size: 10px; font-style: italic; background: #f9fafb; }

        /* Bank details - fixed to bottom */
        .bank-section {
            position: fixed;
            bottom: 36px;
            left: 30px;
            right: 30px;
            border-top: 1px solid #d1d5db;
            padding-top: 8px;
        }
        .bank-section h4 { font-size: 10px; font-weight: bold; color: #1a1a2e; margin-bottom: 5px; }
        .bank-table { border-collapse: collapse; }
        .bank-table td { padding: 2px 0; font-size: 9px; }
        .bank-table td:first-child { font-weight: bold; width: 120px; color: #374151; }
        .bank-table td:last-child { color: #374151; }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 30px;
            right: 30px;
            border-top: 1px solid #e5e7eb;
            padding: 5px 0;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>

@php
    $logoB64 = null;
    if (!empty($company->logo_path) && file_exists(public_path($company->logo_path))) {
        $logoB64 = base64_encode(file_get_contents(public_path($company->logo_path)));
        $logoMime = mime_content_type(public_path($company->logo_path));
    } elseif (file_exists(public_path('images/logo_b64.txt'))) {
        $logoB64 = file_get_contents(public_path('images/logo_b64.txt'));
        $logoMime = null; // already a raw base64 string with no prefix needed
    }

    function amountInWords($amount) {
        $amount = round($amount, 2);
        $parts  = explode('.', number_format($amount, 2, '.', ''));
        $whole  = (int) str_replace(',', '', $parts[0]);
        $dec    = (int) $parts[1];
        $ones   = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
                   'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
                   'Seventeen','Eighteen','Nineteen'];
        $tens   = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        function cvt($n, $ones, $tens) {
            $s = '';
            if ($n >= 100) { $s .= $ones[(int)($n/100)].' Hundred '; $n %= 100; }
            if ($n >= 20)  { $s .= $tens[(int)($n/10)].' '; $n %= 10; }
            if ($n > 0)    { $s .= $ones[$n].' '; }
            return $s;
        }
        $w = '';
        if ($whole >= 1000000) { $w .= cvt((int)($whole/1000000),$ones,$tens).'Million '; $whole %= 1000000; }
        if ($whole >= 1000)    { $w .= cvt((int)($whole/1000),$ones,$tens).'Thousand '; $whole %= 1000; }
        if ($whole > 0)        { $w .= cvt($whole,$ones,$tens); }
        $result = trim($w).' Dirhams';
        if ($dec > 0) $result .= ' and '.$dec.' Fils';
        return $result;
    }
@endphp

<!-- HEADER -->
<table class="header-table">
    <tr>
        <td style="width:150px; vertical-align:top;">
            @if($logoB64)
                @if($logoMime)
                    <img src="data:{{ $logoMime }};base64,{{ $logoB64 }}" alt="Logo" style="width:90px;">
                @else
                    <img src="data:image/png;base64,{{ $logoB64 }}" alt="Logo" style="width:90px;">
                @endif
            @endif
        </td>
        <td style="vertical-align:top;">
            <div class="company-info">
                <strong>{{ $company->name ?? '' }}</strong>
                @if($company->address_line1 ?? null){{ $company->address_line1 }}@if($company->address_line2 ?? null), {{ $company->address_line2 }}@endif<br>@endif
                @if(($company->emirate ?? null) || ($company->country ?? null)){{ implode(', ', array_filter([$company->city ?? null, $company->emirate ?? null, $company->country ?? 'UAE'])) }}<br>@endif
                @if($company->email ?? null)Email: {{ $company->email }}<br>@endif
                @if($company->phone ?? null)Phone: {{ $company->phone }}<br>@endif
                @if($company->trn ?? null)TRN: {{ $company->trn }}@endif
            </div>
        </td>
    </tr>
</table>

<!-- TITLE BAR -->
<table class="title-table">
    <tr>
        <td><span class="invoice-title">Tax Invoice</span></td>
        <td style="text-align:right;"><span class="invoice-number">{{ $invoice->invoice_number }}</span></td>
    </tr>
</table>

<!-- CLIENT / META -->
<table class="meta-table">
    <tr>
        <td style="width:55%;">
            <strong>Client Name:</strong> {{ $invoice->customer_name }}<br>
            @if($invoice->customer_address)<strong>Address:</strong> {{ $invoice->customer_address }}<br>@endif
            @if($invoice->customer_trn)<strong>TRN Number:</strong> {{ $invoice->customer_trn }}<br>@endif
            @if($invoice->contact_person)<strong>Attn:</strong> {{ $invoice->contact_person }}<br>@endif
            @if($invoice->customer_phone)<strong>Phone:</strong> {{ $invoice->customer_phone }}<br>@endif
            @if($invoice->customer_mobile && $invoice->customer_mobile != $invoice->customer_phone)<strong>Mobile:</strong> {{ $invoice->customer_mobile }}<br>@endif
            @if($invoice->customer_email)<strong>Email:</strong> {{ $invoice->customer_email }}<br>@endif
        </td>
        <td style="width:45%; text-align:right;">
            @if($invoice->payment_terms)<strong>Payment Terms:</strong> {{ $invoice->payment_terms }}<br>@endif
            <strong>Invoice Date:</strong> {{ $invoice->invoice_date }}<br>
            <strong>Due Date:</strong> {{ $invoice->due_date ?? 'Upon Receipt' }}<br>
            @if($invoice->po_number)<strong>PO #:</strong> {{ $invoice->po_number }}<br>@endif
        </td>
    </tr>
</table>

<!-- PARTICULARS -->
<div class="section-title">Particulars</div>

<table class="items">
    <thead>
        <tr>
            <th style="width:30px;">#</th>
            <th>Description</th>
            <th class="right" style="width:55px;">Qty</th>
            <th class="right" style="width:90px;">Unit Price</th>
            <th class="right" style="width:55px;">VAT %</th>
            <th class="right" style="width:80px;">VAT Amt</th>
            <th class="right" style="width:90px;">Total (AED)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($lines as $line)
        <tr>
            <td>{{ $line->line_number }}</td>
            <td>{{ $line->description }}</td>
            <td class="right">{{ number_format($line->quantity, 2) }}</td>
            <td class="right">{{ number_format($line->unit_price, 2) }}</td>
            <td class="right">{{ number_format($line->vat_rate, 0) }}%</td>
            <td class="right">{{ number_format($line->vat_amount, 2) }}</td>
            <td class="right">{{ number_format($line->line_amount + $line->vat_amount, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" class="right">Subtotal</td>
            <td class="right">{{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td colspan="6" class="right">VAT</td>
            <td class="right">{{ number_format($invoice->total_vat_amount, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td colspan="6" class="right">TOTAL DUE</td>
            <td class="right">AED {{ number_format($invoice->total_amount, 2) }}</td>
        </tr>
        <tr class="words">
            <td colspan="7"><strong>Total in Words:</strong> {{ amountInWords($invoice->total_amount) }}</td>
        </tr>
    </tfoot>
</table>

@if($invoice->notes)
<div style="margin-top:12px; padding:8px 10px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:3px; font-size:10px; color:#374151;">
    <strong>Notes:</strong> {{ $invoice->notes }}
</div>
@endif

<!-- BANK DETAILS — fixed to bottom above footer -->
@if($company->bank_name || $company->bank_account_number || $company->bank_iban)
<div class="bank-section">
    <h4>Bank Details</h4>
    <table class="bank-table">
        @if($company->bank_name)<tr><td>Bank Name:</td><td>{{ $company->bank_name }}</td></tr>@endif
        @if($company->bank_account_title)<tr><td>Account Title:</td><td>{{ $company->bank_account_title }}</td></tr>@endif
        @if($company->bank_account_number)<tr><td>Account Number:</td><td>{{ $company->bank_account_number }}</td></tr>@endif
        @if($company->bank_iban)<tr><td>IBAN Number:</td><td>{{ $company->bank_iban }}</td></tr>@endif
        @if($company->bank_swift)<tr><td>SWIFT Code:</td><td>{{ $company->bank_swift }}</td></tr>@endif
    </table>
</div>
@endif

<!-- FOOTER -->
<div class="footer">
    This is an electronically generated invoice and does not require a signature or stamp. &nbsp;|&nbsp;
    {{ $company->name ?? '' }}
    @if($company->address_line1 ?? null) &nbsp;|&nbsp; {{ $company->address_line1 }}, {{ $company->emirate ?? 'UAE' }} @endif
</div>

</body>
</html>