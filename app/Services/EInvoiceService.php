<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Provider-agnostic e-invoicing service.
 * Adding a new ASP: implement a driver method (e.g. submitViaNewProvider())
 * and register it in the driver dispatch below. InvoiceController doesn't change.
 */
class EInvoiceService
{
    private ?string $lastContactError = null;

    public function submitInvoice(int $invoiceId, int $companyId): void
    {
        $company = DB::table('companies')->find($companyId);

        if (!$company || !$company->einvoicing_enabled) {
            return;
        }

        $provider = $company->einvoicing_provider ?? 'wafeq';
        $apiKey   = $this->decryptKey($company->einvoicing_api_key);

        if (!$apiKey) {
            $this->markFailed($invoiceId, 'E-invoicing API key not configured.');
            return;
        }

        match ($provider) {
            'wafeq'  => $this->submitViaWafeq($invoiceId, $companyId, $company, $apiKey),
            default  => $this->markFailed($invoiceId, "Unknown provider: {$provider}"),
        };
    }

    // ─── Wafeq Driver ────────────────────────────────────────────────────────

    private function submitViaWafeq(int $invoiceId, int $companyId, object $company, string $apiKey): void
    {
        $invoice = DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.id', $invoiceId)
            ->select('invoices.*', 'customers.name as customer_name', 'customers.email as customer_email',
                     'customers.phone as customer_phone',
                     DB::raw("CONCAT_WS(', ', customers.address_line1, customers.address_line2, customers.city) as customer_address"),
                     'customers.trn as customer_trn')
            ->first();

        if (!$invoice) {
            $this->markFailed($invoiceId, 'Invoice not found.');
            return;
        }

        $lines = DB::table('invoice_lines')
            ->leftJoin('accounts', 'accounts.id', '=', 'invoice_lines.account_id')
            ->where('invoice_lines.invoice_id', $invoiceId)
            ->select('invoice_lines.*', 'accounts.name as account_name', 'accounts.code as account_code')
            ->get();

        // Ensure contact exists in Wafeq
        $contactId = $this->ensureWafeqContact($invoice, $apiKey);
        if (!$contactId) {
            $this->markFailed($invoiceId, $this->lastContactError ?? 'Failed to create/find contact in Wafeq.');
            return;
        }

        $wafeqAccountId = $company->einvoicing_revenue_account_id ?? null;
        $wafeqVatId     = $this->getWafeqVatId($apiKey);

        // Build line items
        $lineItems = [];
        foreach ($lines as $line) {
            $item = [
                'description' => $line->description,
                'quantity'    => (float) $line->quantity,
                'unit_amount' => (float) $line->unit_price,
            ];
            if ($wafeqAccountId) {
                $item['account'] = $wafeqAccountId;
            }
            if ($wafeqVatId && (float) $line->vat_amount > 0) {
                $item['tax_rate'] = $wafeqVatId;
            }
            $lineItems[] = $item;
        }

        $payload = [
            'contact'          => $contactId,
            'currency'         => 'AED',
            'invoice_date'     => $invoice->invoice_date,
            'invoice_due_date' => $invoice->due_date ?? $invoice->invoice_date,
            'invoice_number'   => $invoice->invoice_number,
            'status'           => 'SENT',
            'line_items'       => $lineItems,
            'external_id'      => (string) $invoiceId,
            'notes'            => $invoice->notes ?? '',
        ];

        $idempotencyKey = Str::uuid()->toString();

        $response = Http::withHeaders([
            'Authorization'         => 'Api-Key ' . $apiKey,
            'Content-Type'          => 'application/json',
            'X-Wafeq-Idempotency-Key' => $idempotencyKey,
        ])->post('https://api.wafeq.com/v1/invoices/', $payload);

        if ($response->successful()) {
            $data    = $response->json();
            $uuid    = $data['id'] ?? null;
            $qrCode  = $data['qr_code'] ?? $data['qr'] ?? null;

            DB::table('invoices')->where('id', $invoiceId)->update([
                'einvoice_uuid'         => $uuid,
                'einvoice_qr_code'      => $qrCode,
                'einvoice_status'       => 'SUBMITTED',
                'einvoice_submitted_at' => now(),
                'einvoice_error'        => null,
                'updated_at'            => now(),
            ]);
        } else {
            $body = $response->body();
            $this->markFailed($invoiceId, "Wafeq API error ({$response->status()}): {$body}");
        }
    }

    private function getWafeqVatId(string $apiKey): ?string
    {
        $res = Http::withHeaders(['Authorization' => 'Api-Key ' . $apiKey])
            ->get('https://api.wafeq.com/v1/tax-rates/', ['limit' => 50]);

        if (!$res->successful()) {
            // Try alternate endpoint name
            $res = Http::withHeaders(['Authorization' => 'Api-Key ' . $apiKey])
                ->get('https://api.wafeq.com/v1/taxes/', ['limit' => 50]);
        }

        if ($res->successful()) {
            $results = $res->json('results') ?? [];
            // Find UAE VAT 5%
            foreach ($results as $tax) {
                $rate = $tax['rate'] ?? $tax['tax_rate'] ?? $tax['percentage'] ?? null;
                if ($rate == 5) return $tax['id'];
            }
            // Fallback: return first non-zero tax
            foreach ($results as $tax) {
                $rate = $tax['rate'] ?? $tax['tax_rate'] ?? $tax['percentage'] ?? 0;
                if ($rate > 0) return $tax['id'];
            }
        }
        return null;
    }

    private function ensureWafeqContact(object $invoice, string $apiKey): ?string
    {
        // Try to look up by external_id (customer id stored as external_id when we create)
        $searchRes = Http::withHeaders([
            'Authorization' => 'Api-Key ' . $apiKey,
        ])->get('https://api.wafeq.com/v1/contacts/', [
            'external_id' => 'cust-' . $invoice->customer_id,
        ]);

        if ($searchRes->successful()) {
            $results = $searchRes->json('results') ?? [];
            if (!empty($results)) {
                return $results[0]['id'];
            }
        }

        // Create new contact
        $payload = [
            'name'        => $invoice->customer_name,
            'external_id' => 'cust-' . $invoice->customer_id,
        ];
        if ($invoice->customer_email) $payload['email']      = $invoice->customer_email;
        if ($invoice->customer_phone) $payload['phone']      = $invoice->customer_phone;
        if ($invoice->customer_address) $payload['address']  = $invoice->customer_address;
        if ($invoice->customer_trn)   $payload['tax_number'] = $invoice->customer_trn;

        $createRes = Http::withHeaders([
            'Authorization' => 'Api-Key ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.wafeq.com/v1/contacts/', $payload);

        if ($createRes->successful()) {
            return $createRes->json('id');
        }

        // Store exact Wafeq error so we can debug
        $this->lastContactError = "Contact create failed ({$createRes->status()}): " . $createRes->body();
        return null;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function testConnection(string $apiKey, string $provider = 'wafeq'): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Api-Key ' . $apiKey,
            ])->get('https://api.wafeq.com/v1/contacts/', ['limit' => 1]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Connection successful.'];
            }
            return ['success' => false, 'message' => 'API returned ' . $response->status() . ': ' . $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function markFailed(int $invoiceId, string $error): void
    {
        DB::table('invoices')->where('id', $invoiceId)->update([
            'einvoice_status' => 'FAILED',
            'einvoice_error'  => $error,
            'updated_at'      => now(),
        ]);
    }

    public function encryptKey(string $key): string
    {
        return encrypt($key);
    }

    private function decryptKey(?string $encrypted): ?string
    {
        if (!$encrypted) return null;
        try {
            return decrypt($encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }
}
