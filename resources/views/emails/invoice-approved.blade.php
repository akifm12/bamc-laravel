<x-mail::message>
# Invoice {{ $invoice->invoice_number }}

Dear {{ $invoice->customer_name }},

Please find attached your invoice from **{{ $company->name }}**.

<x-mail::table>
| | |
|:--|--:|
| **Invoice Number** | {{ $invoice->invoice_number }} |
| **Invoice Date** | {{ $invoice->invoice_date }} |
| **Due Date** | {{ $invoice->due_date ?? 'Upon Receipt' }} |
| **Amount Due** | AED {{ number_format($invoice->total_amount, 2) }} |
</x-mail::table>

Please arrange payment at your earliest convenience.

@if($invoice->payment_terms)
**Payment Terms:** {{ $invoice->payment_terms }} days
@endif

**Bank Transfer Details**

| | |
|:--|:--|
| Bank Name | National Bank of Ras Al Khaimah (P.S.C) |
| Account Title | Blue Arrow Management Consultants FZC |
| Account Number | 0293106433001 |
| IBAN | AE700400000293106433001 |
| SWIFT | NRAKAEAK |

For any queries, please contact us at {{ $company->email ?? 'accounts@bluearrow.ae' }}.

Thank you for your business.

Regards,<br>
**{{ $company->name }}**<br>
{{ $company->phone ?? '' }}<br>
{{ $company->email ?? 'accounts@bluearrow.ae' }}

*This is an electronically generated invoice and does not require a signature.*
</x-mail::message>
