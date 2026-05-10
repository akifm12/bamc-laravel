<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\InvoiceApproved;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $dateFrom = $request->get('date_from', date('Y-01-01'));
        $dateTo   = $request->get('date_to',   date('Y-m-d'));
        $status   = $request->get('status', 'all');

        $query = DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.company_id', $companyId)
            ->where('invoices.is_deleted', false)
            ->whereBetween('invoices.invoice_date', [$dateFrom, $dateTo])
            ->select('invoices.*', 'customers.name as customer_name')
            ->orderBy('invoices.invoice_date', 'desc');

        if ($status !== 'all') {
            $query->where('invoices.status', strtoupper($status));
        }

        $invoices    = $query->get();
        $totalAmount = $invoices->sum('total_amount');
        $totalDue    = $invoices->sum('amount_due');

        return view('invoices.index', compact('invoices', 'dateFrom', 'dateTo', 'status', 'totalAmount', 'totalDue'));
    }

    public function create(Request $request)
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $customers = DB::table('customers')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get();

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByRaw("CASE account_type WHEN 'ASSET' THEN 1 WHEN 'LIABILITY' THEN 2 WHEN 'EQUITY' THEN 3 WHEN 'REVENUE' THEN 4 WHEN 'EXPENSE' THEN 5 ELSE 6 END")
            ->orderBy('code')
            ->get()
            ->groupBy('account_type');

        $taxCodes = DB::table('tax_codes')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        $selectedCustomer = null;
        if ($request->get('customer_id')) {
            $selectedCustomer = DB::table('customers')->find($request->get('customer_id'));
        }

        $company = DB::table('companies')->find($companyId);

        $presetServices = [
            'Compliance and Regulatory Services',
            'Employee Training Program',
            'AML Screening Solution',
            'KYC & Onboarding Solution',
            'AML Audit Services',
            'IT & Software Solutions',
            'Software Services - Accounting',
            'Financial Advisory Services',
        ];

        return view('invoices.create', compact('customers', 'accounts', 'taxCodes', 'selectedCustomer', 'company', 'presetServices'));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');
        $request->validate([
            'customer_id'  => 'required|integer',
            'invoice_date' => 'required|date',
        ]);

        $customer = DB::table('customers')->find($request->customer_id);
        $company  = DB::table('companies')->find($companyId);

        $subtotal = 0;
        $totalVat = 0;
        $lines    = [];

        foreach ($request->accounts ?? [] as $i => $accountId) {
            if (!$accountId) continue;
            $qty        = floatval($request->quantities[$i] ?? 1);
            $price      = floatval($request->unit_prices[$i] ?? 0);
            $vatRate    = floatval($request->vat_rates[$i] ?? 0);
            $desc       = $request->descriptions[$i] ?? '';
            $lineAmount = $qty * $price;
            $vatAmount  = $lineAmount * ($vatRate / 100);
            $subtotal  += $lineAmount;
            $totalVat  += $vatAmount;
            $lines[]    = [
                'account_id'   => $accountId,
                'description'  => $desc,
                'quantity'     => $qty,
                'unit_price'   => $price,
                'line_amount'  => $lineAmount,
                'vat_rate'     => $vatRate,
                'vat_amount'   => $vatAmount,
                'total_amount' => $lineAmount + $vatAmount,
            ];
        }

        $totalAmount = $subtotal + $totalVat;
        $dueDate     = $request->due_date ?? date('Y-m-d', strtotime('+' . ($customer->payment_terms_days ?? 30) . ' days', strtotime($request->invoice_date)));

        $currentYear = date('Y', strtotime($request->invoice_date));

        $seq = DB::table('number_sequences')
            ->where('company_id', $companyId)
            ->where('document_type', 'invoice')
            ->first();

        if (!$seq) {
            DB::table('number_sequences')->insert([
                'company_id'     => $companyId,
                'document_type'  => 'invoice',
                'prefix'         => '',
                'suffix'         => '',
                'current_number' => 0,
                'padding_length' => 3,
                'reset_annually' => true,
                'current_year'   => $currentYear,
            ]);
            $seq = DB::table('number_sequences')
                ->where('company_id', $companyId)
                ->where('document_type', 'invoice')
                ->first();
        }

        if ($seq->current_year != $currentYear) {
            DB::table('number_sequences')
                ->where('id', $seq->id)
                ->update(['current_number' => 0, 'current_year' => $currentYear]);
            $nextNum = 1;
        } else {
            $nextNum = $seq->current_number + 1;
        }

        DB::table('number_sequences')
            ->where('id', $seq->id)
            ->update(['current_number' => $nextNum]);

        $clientNum     = str_pad($customer->client_number ?? '00', 2, '0', STR_PAD_LEFT);
        $acronym       = $customer->client_acronym ?? 'INV';
        $seqNum        = str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        $invoiceNumber = "{$acronym} - Invoice No. {$clientNum}_{$currentYear}_{$seqNum}";

        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $request->invoice_date)
            ->where('end_date', '>=', $request->invoice_date)
            ->first();

        DB::transaction(function () use (
            $companyId, $request, $customer, $company, $lines, $subtotal, $totalVat,
            $totalAmount, $dueDate, $invoiceNumber, $period
        ) {
            $invoiceId = DB::table('invoices')->insertGetId([
                'company_id'          => $companyId,
                'customer_id'         => $request->customer_id,
                'period_id'           => $period?->id,
                'invoice_number'      => $invoiceNumber,
                'invoice_type'        => 'TAX_INVOICE',
                'invoice_date'        => $request->invoice_date,
                'due_date'            => $dueDate,
                'status'              => 'DRAFT',
                'supplier_name'       => $company->name ?? session('company_name'),
                'supplier_trn'        => $company->trn ?? null,
                'supplier_address'    => $company->address ?? null,
                'customer_name'       => $customer->name,
                'customer_trn'        => $customer->trn,
                'customer_address'    => implode(', ', array_filter([
                    $customer->address_line1,
                    $customer->city,
                    $customer->emirate,
                    $customer->country,
                ])),
                'currency_code'       => 'AED',
                'exchange_rate'       => 1.0,
                'subtotal'            => $subtotal,
                'taxable_amount'      => $subtotal,
                'vat_amount_standard' => $totalVat,
                'total_vat_amount'    => $totalVat,
                'total_amount'        => $totalAmount,
                'amount_paid'         => 0,
                'amount_due'          => $totalAmount,
                'payment_terms'       => ($customer->payment_terms_days ?? 30) . ' days',
                'notes'               => $request->notes,
                'po_number'           => $request->po_number,
                'discount_amount'     => 0,
                'is_reverse_charge'   => false,
                'is_deleted'          => false,
                'created_by_id'       => auth()->user()->id,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            foreach ($lines as $i => $line) {
                DB::table('invoice_lines')->insert([
                    'invoice_id'   => $invoiceId,
                    'company_id'   => $companyId,
                    'account_id'   => $line['account_id'],
                    'line_number'  => $i + 1,
                    'description'  => $line['description'],
                    'quantity'     => $line['quantity'],
                    'unit_price'   => $line['unit_price'],
                    'line_amount'  => $line['line_amount'],
                    'vat_rate'     => $line['vat_rate'],
                    'vat_amount'   => $line['vat_amount'],
                    'total_amount' => $line['total_amount'],
                ]);
            }
        });

        return redirect('/invoices')->with('success', "Invoice {$invoiceNumber} created successfully.");
    }

    public function show($id)
    {
        $companyId = session('company_id');
        $invoice   = DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.id', $id)
            ->where('invoices.company_id', $companyId)
            ->select('invoices.*', 'customers.name as customer_name_full', 'customers.email as customer_email', 'customers.phone as customer_phone')
            ->first();

        if (!$invoice) abort(404);

        $lines = DB::table('invoice_lines')
            ->leftJoin('accounts', 'accounts.id', '=', 'invoice_lines.account_id')
            ->where('invoice_lines.invoice_id', $id)
            ->select('invoice_lines.*', 'accounts.code', 'accounts.name as account_name')
            ->orderBy('invoice_lines.line_number')
            ->get();

        $company = DB::table('companies')->find($companyId);

        return view('invoices.show', compact('invoice', 'lines', 'company'));
    }

    public function pdf($id)
    {
        $companyId = session('company_id');
        $invoice   = DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.id', $id)
            ->where('invoices.company_id', $companyId)
            ->select(
                'invoices.*',
                'customers.name as customer_name_full',
                'customers.trn as customer_trn_full',
                'customers.contact_person',
                'customers.email as customer_email',
                'customers.phone as customer_phone',
                'customers.mobile as customer_mobile'
            )
            ->first();

        if (!$invoice) abort(404);

        $lines = DB::table('invoice_lines')
            ->leftJoin('accounts', 'accounts.id', '=', 'invoice_lines.account_id')
            ->where('invoice_lines.invoice_id', $id)
            ->select('invoice_lines.*', 'accounts.name as account_name')
            ->orderBy('invoice_lines.line_number')
            ->get();

        $company = DB::table('companies')->find($companyId);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', compact('invoice', 'lines', 'company'))
            ->setPaper('a4');

        return $pdf->download("invoice_{$invoice->invoice_number}.pdf");
    }

    public function approve($id)
    {
        $companyId = session('company_id');

        $invoice = DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.id', $id)
            ->where('invoices.company_id', $companyId)
            ->select('invoices.*', 'customers.ar_account_id')
            ->first();

        if (!$invoice) abort(404);

        $lines = DB::table('invoice_lines')->where('invoice_id', $id)->get();

        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $invoice->invoice_date)
            ->where('end_date', '>=', $invoice->invoice_date)
            ->first();

        DB::transaction(function () use ($companyId, $invoice, $lines, $period, $id) {
            $count    = DB::table('journal_entries')->where('company_id', $companyId)->count() + 1;
            $jeNumber = 'JE-' . date('Y') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

            $jeId = DB::table('journal_entries')->insertGetId([
                'company_id'    => $companyId,
                'period_id'     => $period?->id,
                'entry_number'  => $jeNumber,
                'entry_date'    => $invoice->invoice_date,
                'journal_type'  => 'GENERAL',
                'status'        => 'POSTED',
                'description'   => "Invoice {$invoice->invoice_number} — {$invoice->customer_name}",
                'reference'     => $invoice->invoice_number,
                'total_debit'   => $invoice->total_amount,
                'total_credit'  => $invoice->total_amount,
                'currency_code' => 'AED',
                'exchange_rate' => 1.0,
                'is_reversal'   => false,
                'is_recurring'  => false,
                'is_deleted'    => false,
                'created_by_id' => auth()->user()->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $lineNum = 1;

            if ($invoice->ar_account_id) {
                DB::table('journal_lines')->insert([
                    'journal_entry_id' => $jeId,
                    'company_id'       => $companyId,
                    'account_id'       => $invoice->ar_account_id,
                    'line_number'      => $lineNum++,
                    'description'      => "AR — {$invoice->customer_name}",
                    'debit_amount'     => $invoice->total_amount,
                    'credit_amount'    => 0,
                    'currency_code'    => 'AED',
                    'exchange_rate'    => 1.0,
                    'is_reconciled'    => false,
                ]);
            }

            foreach ($lines as $line) {
                if (!$line->account_id) continue;
                DB::table('journal_lines')->insert([
                    'journal_entry_id' => $jeId,
                    'company_id'       => $companyId,
                    'account_id'       => $line->account_id,
                    'line_number'      => $lineNum++,
                    'description'      => $line->description,
                    'debit_amount'     => 0,
                    'credit_amount'    => $line->line_amount,
                    'currency_code'    => 'AED',
                    'exchange_rate'    => 1.0,
                    'is_reconciled'    => false,
                ]);
            }

            if ($invoice->total_vat_amount > 0) {
                $vatAccount = DB::table('accounts')
                    ->where('company_id', $companyId)
                    ->where('account_type', 'LIABILITY')
                    ->where(function($q) {
                        $q->where('name', 'like', '%VAT%')
                          ->orWhere('name', 'like', '%Tax%');
                    })
                    ->first();

                if ($vatAccount) {
                    DB::table('journal_lines')->insert([
                        'journal_entry_id' => $jeId,
                        'company_id'       => $companyId,
                        'account_id'       => $vatAccount->id,
                        'line_number'      => $lineNum++,
                        'description'      => 'Output VAT',
                        'debit_amount'     => 0,
                        'credit_amount'    => $invoice->total_vat_amount,
                        'currency_code'    => 'AED',
                        'exchange_rate'    => 1.0,
                        'is_reconciled'    => false,
                    ]);
                }
            }

            DB::table('invoices')->where('id', $id)->update([
                'status'           => 'APPROVED',
                'journal_entry_id' => $jeId,
                'updated_at'       => now(),
            ]);
        });

        return redirect("/invoices/{$id}")->with('success', 'Invoice approved and journal entry created. Use the Send Email button to email the invoice to the customer.');
    }

    public function sendEmail($id)
    {
        $companyId = session('company_id');
        $startTime = microtime(true);

        \Log::info("INVOICE EMAIL START", ['invoice_id' => $id, 'user' => auth()->user()->email]);

        $invoice = DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.id', $id)
            ->where('invoices.company_id', $companyId)
            ->select(
                'invoices.*',
                'customers.name as customer_name_full',
                'customers.trn as customer_trn_full',
                'customers.contact_person',
                'customers.email as customer_email',
                'customers.phone as customer_phone',
                'customers.mobile as customer_mobile'
            )
            ->first();

        if (!$invoice) abort(404);

        $customer = DB::table('customers')->find($invoice->customer_id);
        $company  = DB::table('companies')->find($companyId);

        if (!$customer->email) {
            return redirect("/invoices/{$id}")->with('error', 'No email address on file for this customer. Please update their profile first.');
        }

        \Log::info("INVOICE EMAIL - Customer found", [
            'customer'   => $customer->name,
            'email'      => $customer->email,
            'elapsed_ms' => round((microtime(true) - $startTime) * 1000),
        ]);

        $tmpPath = null;

        try {
            \Log::info("INVOICE EMAIL - Generating PDF", ['elapsed_ms' => round((microtime(true) - $startTime) * 1000)]);

            $lines = DB::table('invoice_lines')
                ->leftJoin('accounts', 'accounts.id', '=', 'invoice_lines.account_id')
                ->where('invoice_lines.invoice_id', $id)
                ->select('invoice_lines.*', 'accounts.name as account_name')
                ->orderBy('invoice_lines.line_number')
                ->get();

            $pdf     = Pdf::loadView('invoices.pdf', ['invoice' => $invoice, 'lines' => $lines, 'company' => $company])->setPaper('a4');
            $tmpPath = storage_path('app/invoice_' . $id . '_' . time() . '.pdf');
            file_put_contents($tmpPath, $pdf->output());

            \Log::info("INVOICE EMAIL - PDF generated", [
                'size_kb'    => round(filesize($tmpPath) / 1024, 1),
                'elapsed_ms' => round((microtime(true) - $startTime) * 1000),
            ]);

            // Purge any stale SMTP connection from PHP-FPM worker reuse
            app()->make('mail.manager')->purge('smtp');

            \Log::info("INVOICE EMAIL - Connecting to SMTP (fresh connection)", [
                'host'       => config('mail.mailers.smtp.host'),
                'port'       => config('mail.mailers.smtp.port'),
                'elapsed_ms' => round((microtime(true) - $startTime) * 1000),
            ]);

            // Auto-retry once on connection timeout (Contabo NAT drops idle routes)
            $attempt = 0;
            while (true) {
                try {
                    $attempt++;
                    Mail::mailer('smtp')->to($customer->email)->send(new InvoiceApproved($invoice, $company, $tmpPath));
                    break; // success
                } catch (\Symfony\Component\Mailer\Exception\TransportException $te) {
                    app()->make('mail.manager')->purge('smtp');
                    if ($attempt >= 2) throw $te; // give up after 2 tries
                    \Log::warning("INVOICE EMAIL - SMTP connection failed, retrying", [
                        'attempt'    => $attempt,
                        'error'      => $te->getMessage(),
                        'elapsed_ms' => round((microtime(true) - $startTime) * 1000),
                    ]);
                    sleep(1);
                }
            }

            // Close connection cleanly after send — no stale socket left behind
            app()->make('mail.manager')->purge('smtp');

            @unlink($tmpPath);

            $elapsed = round((microtime(true) - $startTime) * 1000);

            \Log::info("INVOICE EMAIL SUCCESS", [
                'to'         => $customer->email,
                'elapsed_ms' => $elapsed,
            ]);

            return redirect("/invoices/{$id}")->with('success', "Invoice emailed to {$customer->email} ({$elapsed}ms)");

        } catch (\Exception $e) {
            @unlink($tmpPath ?? '');

            \Log::error("INVOICE EMAIL FAILED", [
                'error'      => $e->getMessage(),
                'elapsed_ms' => round((microtime(true) - $startTime) * 1000),
            ]);

            return redirect("/invoices/{$id}")->with('error', 'Email failed: ' . $e->getMessage());
        }
    }

    public function void($id)
    {
        if (!auth()->user()->is_super_admin) abort(403, 'Only super admins can void invoices.');
        $companyId = session('company_id');
        DB::table('invoices')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->update(['status' => 'VOID', 'is_deleted' => true, 'updated_at' => now()]);

        return redirect('/invoices')->with('success', 'Invoice voided.');
    }

    public function paymentForm($id)
    {
        $companyId = session('company_id');
        $invoice   = DB::table('invoices')->where('id', $id)->where('company_id', $companyId)->first();
        if (!$invoice) abort(404);

        $bankAccounts = DB::table('bank_accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->get();

        $cashAccounts = DB::table('accounts')
            ->where('company_id', (int)$companyId)
            ->whereRaw("name ILIKE '%cash%'")
            ->get();

        return view('invoices.payment', compact('invoice', 'bankAccounts', 'cashAccounts'));
    }

    public function recordPayment(Request $request, $id)
    {
        $companyId = session('company_id');
        $request->validate([
            'payment_date'  => 'required|date',
            'amount'        => 'required|numeric|min:0.01',
            'gl_account_id' => 'required|integer',
        ]);

        $invoice = DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.id', $id)
            ->where('invoices.company_id', $companyId)
            ->select('invoices.*', 'customers.ar_account_id')
            ->first();

        if (!$invoice) abort(404);

        $amount = floatval($request->amount);

        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $request->payment_date)
            ->where('end_date', '>=', $request->payment_date)
            ->first();

        DB::transaction(function () use ($companyId, $invoice, $amount, $request, $period, $id) {
            $count    = DB::table('journal_entries')->where('company_id', $companyId)->count() + 1;
            $jeNumber = 'JE-' . date('Y') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

            $jeId = DB::table('journal_entries')->insertGetId([
                'company_id'    => $companyId,
                'period_id'     => $period?->id,
                'entry_number'  => $jeNumber,
                'entry_date'    => $request->payment_date,
                'journal_type'  => 'BANK_RECEIPT',
                'status'        => 'POSTED',
                'description'   => "Payment received — {$invoice->invoice_number}",
                'reference'     => $invoice->invoice_number,
                'total_debit'   => $amount,
                'total_credit'  => $amount,
                'currency_code' => 'AED',
                'exchange_rate' => 1.0,
                'is_reversal'   => false,
                'is_recurring'  => false,
                'is_deleted'    => false,
                'created_by_id' => auth()->user()->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            DB::table('journal_lines')->insert([
                'journal_entry_id' => $jeId,
                'company_id'       => $companyId,
                'account_id'       => $request->gl_account_id,
                'line_number'      => 1,
                'description'      => "Payment received — {$invoice->invoice_number}",
                'debit_amount'     => $amount,
                'credit_amount'    => 0,
                'currency_code'    => 'AED',
                'exchange_rate'    => 1.0,
                'is_reconciled'    => false,
            ]);

            DB::table('journal_lines')->insert([
                'journal_entry_id' => $jeId,
                'company_id'       => $companyId,
                'account_id'       => $invoice->ar_account_id,
                'line_number'      => 2,
                'description'      => "AR cleared — {$invoice->customer_name}",
                'debit_amount'     => 0,
                'credit_amount'    => $amount,
                'currency_code'    => 'AED',
                'exchange_rate'    => 1.0,
                'is_reconciled'    => false,
            ]);

            $newPaid = $invoice->amount_paid + $amount;
            $newDue  = $invoice->total_amount - $newPaid;
            $status  = $newDue <= 0 ? 'PAID' : 'PARTIAL';

            DB::table('invoices')->where('id', $id)->update([
                'amount_paid' => $newPaid,
                'amount_due'  => max(0, $newDue),
                'status'      => $status,
                'updated_at'  => now(),
            ]);
        });

        return redirect("/invoices/{$id}")->with('success', 'Payment recorded successfully.');
    }

    public function edit($id)
    {
        $companyId = session('company_id');
        $invoice   = DB::table('invoices')->where('id', $id)->where('company_id', $companyId)->first();
        if (!$invoice) abort(404);
        if ($invoice->status !== 'DRAFT') return redirect("/invoices/{$id}")->with('error', 'Only draft invoices can be edited.');

        $customers = DB::table('customers')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get();

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByRaw("CASE account_type WHEN 'ASSET' THEN 1 WHEN 'LIABILITY' THEN 2 WHEN 'EQUITY' THEN 3 WHEN 'REVENUE' THEN 4 WHEN 'EXPENSE' THEN 5 ELSE 6 END")
            ->orderBy('code')
            ->get()
            ->groupBy('account_type');

        $lines = DB::table('invoice_lines')
            ->where('invoice_id', $id)
            ->orderBy('line_number')
            ->get();

        $company = DB::table('companies')->find($companyId);

        $presetServices = [
            'Compliance and Regulatory Services',
            'Employee Training Program',
            'AML Screening Solution',
            'KYC & Onboarding Solution',
            'AML Audit Services',
            'IT & Software Solutions',
            'Software Services - Accounting',
            'Financial Advisory Services',
        ];

        return view('invoices.edit', compact('invoice', 'customers', 'accounts', 'lines', 'company', 'presetServices'));
    }

    public function update(Request $request, $id)
    {
        $companyId = session('company_id');
        $invoice   = DB::table('invoices')->where('id', $id)->where('company_id', $companyId)->first();
        if (!$invoice) abort(404);
        if ($invoice->status !== 'DRAFT') return redirect("/invoices/{$id}")->with('error', 'Only draft invoices can be edited.');

        $customer = DB::table('customers')->find($request->customer_id);
        $company  = DB::table('companies')->find($companyId);

        $subtotal = 0;
        $totalVat = 0;
        $lines    = [];

        foreach ($request->accounts ?? [] as $i => $accountId) {
            if (!$accountId) continue;
            $qty        = floatval($request->quantities[$i] ?? 1);
            $price      = floatval($request->unit_prices[$i] ?? 0);
            $vatRate    = floatval($request->vat_rates[$i] ?? 0);
            $desc       = $request->descriptions[$i] ?? '';
            $lineAmount = $qty * $price;
            $vatAmount  = $lineAmount * ($vatRate / 100);
            $subtotal  += $lineAmount;
            $totalVat  += $vatAmount;
            $lines[]    = [
                'account_id'   => $accountId,
                'description'  => $desc,
                'quantity'     => $qty,
                'unit_price'   => $price,
                'line_amount'  => $lineAmount,
                'vat_rate'     => $vatRate,
                'vat_amount'   => $vatAmount,
                'total_amount' => $lineAmount + $vatAmount,
            ];
        }

        $totalAmount = $subtotal + $totalVat;
        $dueDate     = $request->due_date ?? date('Y-m-d', strtotime('+' . ($customer->payment_terms_days ?? 30) . ' days', strtotime($request->invoice_date)));

        DB::transaction(function () use ($companyId, $id, $request, $customer, $company, $lines, $subtotal, $totalVat, $totalAmount, $dueDate) {
            DB::table('invoices')->where('id', $id)->update([
                'customer_id'         => $request->customer_id,
                'invoice_date'        => $request->invoice_date,
                'due_date'            => $dueDate,
                'supplier_name'       => $company->name,
                'supplier_trn'        => $company->trn,
                'customer_name'       => $customer->name,
                'customer_trn'        => $customer->trn,
                'customer_address'    => implode(', ', array_filter([
                    $customer->address_line1,
                    $customer->city,
                    $customer->emirate,
                    $customer->country,
                ])),
                'subtotal'            => $subtotal,
                'taxable_amount'      => $subtotal,
                'vat_amount_standard' => $totalVat,
                'total_vat_amount'    => $totalVat,
                'total_amount'        => $totalAmount,
                'amount_due'          => $totalAmount,
                'discount_amount'     => 0,
                'notes'               => $request->notes,
                'po_number'           => $request->po_number,
                'updated_at'          => now(),
            ]);

            DB::table('invoice_lines')->where('invoice_id', $id)->delete();

            foreach ($lines as $i => $line) {
                DB::table('invoice_lines')->insert([
                    'invoice_id'   => $id,
                    'company_id'   => $companyId,
                    'account_id'   => $line['account_id'],
                    'line_number'  => $i + 1,
                    'description'  => $line['description'],
                    'quantity'     => $line['quantity'],
                    'unit_price'   => $line['unit_price'],
                    'line_amount'  => $line['line_amount'],
                    'vat_rate'     => $line['vat_rate'],
                    'vat_amount'   => $line['vat_amount'],
                    'total_amount' => $line['total_amount'],
                ]);
            }
        });

        return redirect("/invoices/{$id}")->with('success', 'Invoice updated.');
    }
}