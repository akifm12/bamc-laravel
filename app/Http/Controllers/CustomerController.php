<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index()
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $customers = DB::table('customers')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get();

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        $companyId = session('company_id');
        $accounts  = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('account_type', 'ASSET')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $emirates = ['Abu Dhabi','Dubai','Sharjah','Ajman','Umm Al Quwain','Ras Al Khaimah','Fujairah'];
        return view('customers.create', compact('accounts', 'emirates'));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');
        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string',
        ]);

        // Generate code if not provided
        $code = $request->code ?: 'CUST-' . strtoupper(substr($request->name, 0, 4)) . '-' . rand(100, 999);

        DB::table('customers')->insert([
            'company_id'        => $companyId,
            'code'              => $code,
            'name'              => $request->name,
            'name_arabic'       => $request->name_arabic,
            'trn'                  => $request->trn,
            'is_vat_registered'    => $request->has('is_vat_registered'),
            'buyer_legal_reg_id'   => $request->buyer_legal_reg_id ?: null,
            'buyer_legal_reg_type' => $request->buyer_legal_reg_type ?: null,
            'email'                => $request->email,
            'phone'                => $request->phone,
            'mobile'               => $request->mobile,
            'contact_person'       => $request->contact_person,
            'address_line1'        => $request->address_line1,
            'address_line2'        => $request->address_line2,
            'city'                 => $request->city,
            'emirate'              => $request->emirate,
            'country'              => $request->country ?? 'UAE',
            'payment_terms_days'=> $request->payment_terms_days ?? 30,
            'credit_limit'      => $request->credit_limit ?? 0,
            'currency_code'     => 'AED',
            'ar_account_id'     => $request->ar_account_id,
            'is_active'         => true,
            'is_deleted'        => false,
            'blacklisted'       => false,
            'notes'             => $request->notes,
            'created_by_id'     => auth()->user()->id,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return redirect('/customers')->with('success', 'Customer created successfully.');
    }

    public function show($id)
    {
        $companyId = session('company_id');
        $customer  = DB::table('customers')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if (!$customer) abort(404);

        $invoices = DB::table('invoices')
            ->where('customer_id', $id)
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->orderBy('invoice_date', 'desc')
            ->get();

        $totalBilled = $invoices->sum('total_amount');
        $totalDue    = $invoices->sum('amount_due');

        return view('customers.show', compact('customer', 'invoices', 'totalBilled', 'totalDue'));
    }

    public function edit($id)
    {
        $companyId = session('company_id');
        $customer  = DB::table('customers')->where('id', $id)->where('company_id', $companyId)->first();
        if (!$customer) abort(404);

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('account_type', 'ASSET')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $emirates = ['Abu Dhabi','Dubai','Sharjah','Ajman','Umm Al Quwain','Ras Al Khaimah','Fujairah'];
        return view('customers.edit', compact('customer', 'accounts', 'emirates'));
    }

    public function update(Request $request, $id)
    {
        $companyId = session('company_id');
        DB::table('customers')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->update([
                'name'              => $request->name,
                'name_arabic'       => $request->name_arabic,
                'trn'                  => $request->trn,
                'is_vat_registered'    => $request->has('is_vat_registered'),
                'buyer_legal_reg_id'   => $request->buyer_legal_reg_id ?: null,
                'buyer_legal_reg_type' => $request->buyer_legal_reg_type ?: null,
                'email'                => $request->email,
                'phone'                => $request->phone,
                'mobile'               => $request->mobile,
                'contact_person'       => $request->contact_person,
                'address_line1'        => $request->address_line1,
                'address_line2'        => $request->address_line2,
                'city'                 => $request->city,
                'emirate'              => $request->emirate,
                'country'              => $request->country ?? 'UAE',
                'payment_terms_days'=> $request->payment_terms_days ?? 30,
                'credit_limit'      => $request->credit_limit ?? 0,
                'ar_account_id'     => $request->ar_account_id,
                'notes'             => $request->notes,
                'updated_by_id'     => auth()->user()->id,
                'updated_at'        => now(),
            ]);

        return redirect("/customers/{$id}")->with('success', 'Customer updated.');
    }
public function statement(Request $request, $id)
{
    $companyId = session('company_id');
    $customer  = DB::table('customers')->where('id', $id)->where('company_id', $companyId)->first();
    if (!$customer) abort(404);

    $dateFrom = $request->get('date_from', date('Y-01-01'));
    $dateTo   = $request->get('date_to',   date('Y-m-d'));

    [$transactions, $openingBalance, $closingBalance] = $this->buildStatement($id, $companyId, $dateFrom, $dateTo);

    return view('customers.statement', compact(
        'customer', 'transactions', 'openingBalance', 'closingBalance', 'dateFrom', 'dateTo'
    ));
}

public function statementPdf(Request $request, $id)
{
    $companyId = session('company_id');
    $customer  = DB::table('customers')->where('id', $id)->where('company_id', $companyId)->first();
    if (!$customer) abort(404);

    $dateFrom = $request->get('date_from', date('Y-01-01'));
    $dateTo   = $request->get('date_to',   date('Y-m-d'));
    $company  = DB::table('companies')->find($companyId);

    // Encode logo as base64 for Dompdf (file path embedding)
    $logoBase64 = null;
    if (!empty($company->logo_path)) {
        $logoFile = public_path($company->logo_path);
        if (file_exists($logoFile)) {
            $mime = mime_content_type($logoFile);
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoFile));
        }
    }

    [$transactions, $openingBalance, $closingBalance] = $this->buildStatement($id, $companyId, $dateFrom, $dateTo);

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('customers.statement_pdf', compact(
        'customer', 'company', 'transactions', 'openingBalance', 'closingBalance', 'dateFrom', 'dateTo', 'logoBase64'
    ))->setPaper('a4', 'portrait');

    $filename = 'Statement_' . preg_replace('/[^A-Za-z0-9]/', '_', $customer->name) . '_' . $dateFrom . '_' . $dateTo . '.pdf';
    return $pdf->download($filename);
}

public function statementCsv(Request $request, $id)
{
    $companyId = session('company_id');
    $customer  = DB::table('customers')->where('id', $id)->where('company_id', $companyId)->first();
    if (!$customer) abort(404);

    $dateFrom = $request->get('date_from', date('Y-01-01'));
    $dateTo   = $request->get('date_to',   date('Y-m-d'));
    $company  = DB::table('companies')->find($companyId);

    [$transactions, $openingBalance, $closingBalance] = $this->buildStatement($id, $companyId, $dateFrom, $dateTo);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Statement');

    // ── Row 1: Company name (large, bold, blue) ──
    $sheet->setCellValue('A1', $company->name ?? 'Company');
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1d4ed8']],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(24);

    // ── Row 2: Statement title ──
    $sheet->setCellValue('A2', 'Statement of Account');
    $sheet->mergeCells('A2:G2');
    $sheet->getStyle('A2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '374151']],
    ]);

    // ── Row 3: Customer & period ──
    $sheet->setCellValue('A3', 'Customer: ' . $customer->name);
    $sheet->setCellValue('E3', 'Period: ' . \Carbon\Carbon::parse($dateFrom)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($dateTo)->format('d M Y'));
    $sheet->mergeCells('A3:D3');
    $sheet->mergeCells('E3:G3');
    $sheet->getStyle('A3:G3')->applyFromArray([
        'font' => ['size' => 10, 'color' => ['rgb' => '6b7280']],
    ]);

    // ── Row 4: blank ──
    $sheet->getRowDimension(4)->setRowHeight(6);

    // ── Row 5: Column headers ──
    $headers = ['Date', 'Type', 'Reference', 'Description', 'Debit (AED)', 'Credit (AED)', 'Balance (AED)'];
    foreach ($headers as $col => $label) {
        $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '5';
        $sheet->setCellValue($cell, $label);
    }
    $sheet->getStyle('A5:G5')->applyFromArray([
        'font'    => ['bold' => true, 'color' => ['rgb' => 'ffffff'], 'size' => 10],
        'fill'    => ['fillType' => 'solid', 'startColor' => ['rgb' => '1f2937']],
        'alignment' => ['horizontal' => 'left'],
    ]);
    $sheet->getStyle('E5:G5')->getAlignment()->setHorizontal('right');
    $sheet->getRowDimension(5)->setRowHeight(18);

    // ── Data rows (starting row 6) ──
    $row = 6;

    // Opening balance row
    $sheet->setCellValue('A' . $row, \Carbon\Carbon::parse($dateFrom)->format('d M Y'));
    $sheet->setCellValue('D' . $row, 'Opening Balance');
    $sheet->setCellValue('G' . $row, $openingBalance);
    $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'eff6ff']],
        'font' => ['italic' => true],
    ]);
    $row++;

    foreach ($transactions as $i => $txn) {
        $sheet->setCellValue('A' . $row, \Carbon\Carbon::parse($txn->date)->format('d M Y'));
        $sheet->setCellValue('B' . $row, $txn->type);
        $sheet->setCellValue('C' . $row, $txn->reference);
        $sheet->setCellValue('D' . $row, $txn->description);
        $sheet->setCellValue('E' . $row, $txn->debit  > 0 ? $txn->debit  : '');
        $sheet->setCellValue('F' . $row, $txn->credit > 0 ? $txn->credit : '');
        $sheet->setCellValue('G' . $row, $txn->balance);

        // Alternate row shading
        if ($i % 2 === 1) {
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'f9fafb']],
            ]);
        }
        // Credit amounts in green
        if ($txn->credit > 0) {
            $sheet->getStyle('F' . $row)->applyFromArray([
                'font' => ['color' => ['rgb' => '15803d']],
            ]);
        }
        // Negative balance (Cr) in green
        if ($txn->balance < 0) {
            $sheet->getStyle('G' . $row)->applyFromArray([
                'font' => ['color' => ['rgb' => '15803d']],
            ]);
        }
        $row++;
    }

    // Closing balance row
    $sheet->setCellValue('D' . $row, 'Closing Balance');
    $sheet->setCellValue('E' . $row, $transactions->sum('debit'));
    $sheet->setCellValue('F' . $row, $transactions->sum('credit'));
    $sheet->setCellValue('G' . $row, $closingBalance);
    $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'f3f4f6']],
        'font' => ['bold' => true],
        'borders' => ['top' => ['borderStyle' => 'medium', 'color' => ['rgb' => '374151']]],
    ]);

    // ── Number format for numeric columns ──
    $numFmt = '#,##0.00';
    $lastRow = $row;
    $sheet->getStyle('E6:G' . $lastRow)->getNumberFormat()->setFormatCode($numFmt);

    // ── Column widths ──
    $sheet->getColumnDimension('A')->setWidth(14);  // Date
    $sheet->getColumnDimension('B')->setWidth(10);  // Type
    $sheet->getColumnDimension('C')->setWidth(22);  // Reference
    $sheet->getColumnDimension('D')->setWidth(36);  // Description
    $sheet->getColumnDimension('E')->setWidth(16);  // Debit
    $sheet->getColumnDimension('F')->setWidth(16);  // Credit
    $sheet->getColumnDimension('G')->setWidth(18);  // Balance

    // Right-align numeric columns in data rows
    $sheet->getStyle('E6:G' . $lastRow)->getAlignment()->setHorizontal('right');

    // ── Thin border around the entire data table ──
    $sheet->getStyle('A5:G' . $lastRow)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'e5e7eb']]],
    ]);

    // ── Generated note ──
    $noteRow = $lastRow + 2;
    $sheet->setCellValue('A' . $noteRow, 'Generated: ' . date('d M Y H:i'));
    $sheet->getStyle('A' . $noteRow)->applyFromArray([
        'font' => ['size' => 8, 'color' => ['rgb' => '9ca3af'], 'italic' => true],
    ]);

    // ── Write to temp file and stream ──
    $tmpFile  = tempnam(sys_get_temp_dir(), 'stmt_') . '.xlsx';
    $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($tmpFile);

    $filename = 'Statement_' . preg_replace('/[^A-Za-z0-9]/', '_', $customer->name) . '_' . $dateFrom . '_' . $dateTo . '.xlsx';

    return response()->download($tmpFile, $filename, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ])->deleteFileAfterSend(true);
}

private function buildStatement(int $customerId, int $companyId, string $dateFrom, string $dateTo): array
{
    $openingInvoices = DB::table('invoices')
        ->where('customer_id', $customerId)->where('company_id', $companyId)
        ->where('is_deleted', false)->whereNotIn('status', ['VOID', 'DRAFT'])
        ->where('invoice_date', '<', $dateFrom)
        ->sum('total_amount');

    $openingPayments = DB::table('journal_entries as je')
        ->join('invoices as i', 'i.invoice_number', '=', 'je.reference')
        ->where('i.customer_id', $customerId)->where('je.company_id', $companyId)
        ->where('je.journal_type', 'BANK_RECEIPT')->where('je.status', 'POSTED')
        ->where('je.is_deleted', false)->where('je.entry_date', '<', $dateFrom)
        ->sum('je.total_credit');

    $openingBalance = $openingInvoices - $openingPayments;

    $invoices = DB::table('invoices')
        ->where('customer_id', $customerId)->where('company_id', $companyId)
        ->where('is_deleted', false)->whereNotIn('status', ['VOID', 'DRAFT'])
        ->whereBetween('invoice_date', [$dateFrom, $dateTo])
        ->orderBy('invoice_date')->orderBy('id')
        ->get()->map(fn($r) => (object)[
            'date' => $r->invoice_date, 'type' => 'Invoice',
            'reference' => $r->invoice_number, 'description' => 'Tax Invoice',
            'debit' => $r->total_amount, 'credit' => 0,
        ]);

    $payments = DB::table('journal_entries as je')
        ->join('invoices as i', 'i.invoice_number', '=', 'je.reference')
        ->where('i.customer_id', $customerId)->where('je.company_id', $companyId)
        ->where('je.journal_type', 'BANK_RECEIPT')->where('je.status', 'POSTED')
        ->where('je.is_deleted', false)->whereBetween('je.entry_date', [$dateFrom, $dateTo])
        ->select('je.entry_date as payment_date', 'je.entry_number', 'je.description', 'je.total_credit as amount')
        ->orderBy('je.entry_date')->orderBy('je.id')
        ->get()->map(fn($r) => (object)[
            'date' => $r->payment_date, 'type' => 'Payment',
            'reference' => $r->entry_number,
            'description' => $r->description ?? 'Payment Received',
            'debit' => 0, 'credit' => $r->amount,
        ]);

    $transactions = $invoices->concat($payments)->sortBy('date')->values();

    $running = $openingBalance;
    foreach ($transactions as $txn) {
        $running += $txn->debit - $txn->credit;
        $txn->balance = $running;
    }

    return [$transactions, $openingBalance, $running];
}

public function details($id)
{
    $companyId = session('company_id');
    $customer  = DB::table('customers')
        ->where('id', $id)
        ->where('company_id', $companyId)
        ->first();

    if (!$customer) return response()->json(['error' => 'Not found'], 404);

    // Find their revenue account
    $revenueAccount = DB::table('accounts')
        ->where('company_id', $companyId)
        ->where('account_type', 'REVENUE')
        ->where('name', $customer->name)
        ->first();

    return response()->json([
        'id'              => $customer->id,
        'name'            => $customer->name,
        'client_number'   => $customer->client_number,
        'client_acronym'  => $customer->client_acronym,
        'payment_terms'   => $customer->payment_terms_days,
        'revenue_account' => $revenueAccount ? [
            'id'   => $revenueAccount->id,
            'code' => $revenueAccount->code,
            'name' => $revenueAccount->name,
        ] : null,
    ]);
}
}