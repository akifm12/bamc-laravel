<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{
    public function index()
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $companyId = session('company_id');
        $journalCount  = DB::table('journal_entries')->where('company_id', $companyId)->count();
        $accountCount  = DB::table('accounts')->where('company_id', $companyId)->count();
        $customerCount = DB::table('customers')->where('company_id', $companyId)->count();
        $vendorCount   = DB::table('vendors')->where('company_id', $companyId)->count();
        return view('data.index', compact('journalCount', 'accountCount', 'customerCount', 'vendorCount'));
    }

    public function exportJournals(Request $request)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $companyId = session('company_id');
        $format    = $request->get('format', 'csv');

        $journals = DB::table('journal_entries')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.is_deleted', false)
            ->orderBy('journal_entries.entry_date')
            ->get();

        $rows = [];
        foreach ($journals as $je) {
            $lines = DB::table('journal_lines')
                ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
                ->where('journal_lines.journal_entry_id', $je->id)
                ->select('journal_lines.*', 'accounts.code', 'accounts.name as account_name')
                ->orderByRaw('CASE WHEN journal_lines.debit_amount > 0 THEN 0 ELSE 1 END')
                ->orderBy('journal_lines.line_number')
                ->get();

            foreach ($lines as $line) {
                $rows[] = [
                    'entry_number'   => $je->entry_number,
                    'entry_date'     => $je->entry_date,
                    'journal_type'   => $je->journal_type,
                    'status'         => $je->status,
                    'description'    => $je->description,
                    'reference'      => $je->reference,
                    'account_code'   => $line->code,
                    'account_name'   => $line->account_name,
                    'debit_amount'   => $line->debit_amount,
                    'credit_amount'  => $line->credit_amount,
                    'line_description' => $line->description,
                ];
            }
        }

        $filename = 'journal_entries_' . date('Y-m-d');

        if ($format === 'xml') return $this->toXML($rows, 'journal_entries', 'entry', $filename);
        return $this->toCSV($rows, $filename);
    }

    public function exportAccounts(Request $request)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $companyId = session('company_id');
        $format    = $request->get('format', 'csv');

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->orderBy('code')
            ->get([
                'code', 'name', 'account_type', 'account_sub_type',
                'parent_id', 'currency_code', 'description', 'is_active'
            ]);

        $rows = $accounts->map(fn($a) => (array)$a)->toArray();
        $filename = 'chart_of_accounts_' . date('Y-m-d');

        if ($format === 'xml') return $this->toXML($rows, 'accounts', 'account', $filename);
        return $this->toCSV($rows, $filename);
    }

    public function exportCustomers(Request $request)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $companyId = session('company_id');
        $format    = $request->get('format', 'csv');

        $customers = DB::table('customers')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get([
                'code', 'name', 'name_arabic', 'trn', 'email', 'phone',
                'mobile', 'contact_person', 'address_line1', 'city',
                'emirate', 'country', 'payment_terms_days', 'credit_limit'
            ]);

        $rows = $customers->map(fn($c) => (array)$c)->toArray();
        $filename = 'customers_' . date('Y-m-d');

        if ($format === 'xml') return $this->toXML($rows, 'customers', 'customer', $filename);
        return $this->toCSV($rows, $filename);
    }

    public function exportVendors(Request $request)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $companyId = session('company_id');
        $format    = $request->get('format', 'csv');

        $vendors = DB::table('vendors')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get([
                'code', 'name', 'name_arabic', 'trn', 'email', 'phone',
                'contact_person', 'address_line1', 'city', 'emirate',
                'country', 'payment_terms_days', 'bank_name', 'iban', 'swift_code'
            ]);

        $rows = $vendors->map(fn($v) => (array)$v)->toArray();
        $filename = 'vendors_' . date('Y-m-d');

        if ($format === 'xml') return $this->toXML($rows, 'vendors', 'vendor', $filename);
        return $this->toCSV($rows, $filename);
    }

    public function dbDump()
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $companyId = session('company_id');
        $filename  = 'bamc_export_company' . $companyId . '_' . date('Y-m-d_His') . '.sql';
        $tempFile  = storage_path('app/' . $filename);

        $dbName = config('database.connections.pgsql.database');
        $dbUser = config('database.connections.pgsql.username');
        $dbPass = config('database.connections.pgsql.password');
        $dbHost = config('database.connections.pgsql.host');

        $tables = [
            'companies', 'accounts', 'fiscal_years', 'accounting_periods',
            'customers', 'vendors', 'invoices', 'invoice_lines',
            'bills', 'bill_lines', 'journal_entries', 'journal_lines',
            'bank_accounts', 'employees', 'payroll_runs', 'payroll_lines',
            'fixed_assets', 'items', 'stock_movements', 'warehouse_stock',
            'vat_returns'
        ];

        $tableStr = implode(' ', array_map(fn($t) => "-t {$t}", $tables));

        putenv("PGPASSWORD={$dbPass}");
        $cmd    = "pg_dump -h {$dbHost} -U {$dbUser} -d {$dbName} {$tableStr} --data-only --column-inserts 2>/dev/null";
        $output = shell_exec($cmd);

        if (!$output) {
            return back()->with('error', 'Database dump failed. Check server pg_dump access.');
        }

        file_put_contents($tempFile, $output);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/octet-stream',
        ])->deleteFileAfterSend(true);
    }

    public function downloadTemplate($type)
    {
        if (!auth()->user()->is_super_admin) abort(403);

        $templates = [
            'accounts' => [
                'headers' => ['code', 'name', 'account_type', 'account_sub_type', 'description', 'is_active'],
                'sample'  => [
                    ['1001', 'Cash on Hand', 'ASSET', 'CURRENT_ASSET', 'Petty cash account', 'true'],
                    ['1002', 'Bank Account', 'ASSET', 'CURRENT_ASSET', 'Main bank account', 'true'],
                    ['2001', 'Accounts Payable', 'LIABILITY', 'CURRENT_LIABILITY', 'Vendor payables', 'true'],
                    ['4001', 'Sales Revenue', 'REVENUE', '', 'Revenue from sales', 'true'],
                    ['5001', 'Rent Expense', 'EXPENSE', '', 'Office rent', 'true'],
                ],
                'notes' => [
                    ['# IMPORT TEMPLATE: Chart of Accounts'],
                    ['# account_type values: ASSET, LIABILITY, EQUITY, REVENUE, EXPENSE'],
                    ['# account_subtype values: CURRENT_ASSET, FIXED_ASSET, CURRENT_LIABILITY, LONG_TERM_LIABILITY (optional)'],
                    ['# is_active: true or false'],
                    ['# Do not change column headers'],
                    [''],
                ],
            ],
            'journals' => [
                'headers' => ['entry_date', 'entry_number', 'journal_type', 'description', 'reference', 'account_code', 'debit_amount', 'credit_amount', 'line_description'],
                'sample'  => [
                    ['2026-01-01', 'JE-2026-00001', 'GENERAL', 'Office rent payment', 'REF001', '5001', '5000.00', '0.00', 'Rent expense'],
                    ['2026-01-01', 'JE-2026-00001', 'GENERAL', 'Office rent payment', 'REF001', '1002', '0.00', '5000.00', 'Bank payment'],
                ],
                'notes' => [
                    ['# IMPORT TEMPLATE: Journal Entries'],
                    ['# Each journal entry can have multiple lines (same entry_number)'],
                    ['# journal_type values: GENERAL, CASH_PAYMENT, CASH_RECEIPT, BANK_PAYMENT, BANK_RECEIPT'],
                    ['# Debits and credits must balance per entry_number'],
                    ['# account_code must match existing accounts in the system'],
                    ['# Do not change column headers'],
                    [''],
                ],
            ],
        ];

        if (!isset($templates[$type])) abort(404);

        $tpl      = $templates[$type];
        $filename = "import_template_{$type}.csv";

        $output = fopen('php://output', 'w');
        ob_start();

        foreach ($tpl['notes'] as $note) {
            fputcsv($output, $note);
        }
        fputcsv($output, $tpl['headers']);
        foreach ($tpl['sample'] as $row) {
            fputcsv($output, $row);
        }

        $content = ob_get_clean();
        fclose($output);

        return response($content, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function importAccounts(Request $request)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $companyId = session('company_id');

        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $file     = $request->file('file');
        $handle   = fopen($file->getPathname(), 'r');
        $headers  = null;
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        while (($row = fgetcsv($handle)) !== false) {
            // Skip comment lines
            if (empty($row[0]) || str_starts_with(trim($row[0]), '#')) continue;

            if (!$headers) {
                $headers = array_map('trim', $row);
                continue;
            }

            $data = array_combine($headers, array_map('trim', $row));

            if (empty($data['code']) || empty($data['name']) || empty($data['account_type'])) {
                $skipped++;
                continue;
            }

            $validTypes = ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE'];
            if (!in_array(strtoupper($data['account_type']), $validTypes)) {
                $errors[] = "Row skipped - invalid account_type: {$data['account_type']}";
                $skipped++;
                continue;
            }

            $exists = DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', $data['code'])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            DB::table('accounts')->insert([
    			'company_id'     => $companyId,
    			'code'           => $data['code'],
    			'name'           => $data['name'],
    			'account_type'   => strtoupper($data['account_type']),
    			'normal_balance' => in_array(strtoupper($data['account_type']), ['ASSET', 'EXPENSE']) ? 'DEBIT' : 'CREDIT',
    			'description'    => $data['description'] ?? null,
                'is_active' 	 => in_array(strtolower($data['is_active'] ?? 'true'), ['true', '1', 'yes']),
    			'currency_code'  => 'AED',
    			'created_at'     => now(),
    			'updated_at'     => now(),
			]);

            $imported++;
        }

        fclose($handle);

        $msg = "Import complete: {$imported} accounts imported, {$skipped} skipped.";
        if ($errors) $msg .= ' Errors: ' . implode('; ', $errors);

        return back()->with('success', $msg);
    }

    public function importJournals(Request $request)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $companyId = session('company_id');

        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $file    = $request->file('file');
        $handle  = fopen($file->getPathname(), 'r');
        $headers = null;
        $entries = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0]) || str_starts_with(trim($row[0]), '#')) continue;
            if (!$headers) { $headers = array_map('trim', $row); continue; }

            $data = array_combine($headers, array_map('trim', $row));
            if (empty($data['entry_number'])) continue;

            $entries[$data['entry_number']][] = $data;
        }
        fclose($handle);

        $imported = 0;
        $skipped  = 0;

        foreach ($entries as $entryNumber => $lines) {
            $totalDebit  = array_sum(array_column($lines, 'debit_amount'));
            $totalCredit = array_sum(array_column($lines, 'credit_amount'));

            if (abs($totalDebit - $totalCredit) > 0.01) {
                $skipped++;
                continue;
            }

            $firstLine = $lines[0];
            $entryDate = $firstLine['entry_date'];

            $period = DB::table('accounting_periods')
                ->where('company_id', $companyId)
                ->where('start_date', '<=', $entryDate)
                ->where('end_date', '>=', $entryDate)
                ->first();

            DB::transaction(function () use ($companyId, $entryNumber, $firstLine, $lines, $totalDebit, $totalCredit, $period) {
                $jeId = DB::table('journal_entries')->insertGetId([
                    'company_id'    => $companyId,
                    'period_id'     => $period?->id,
                    'entry_number'  => $entryNumber,
                    'entry_date'    => $firstLine['entry_date'],
                    'journal_type'  => strtoupper($firstLine['journal_type'] ?? 'GENERAL'),
                    'status'        => 'POSTED',
                    'description'   => $firstLine['description'] ?? '',
                    'reference'     => $firstLine['reference'] ?? null,
                    'total_debit'   => $totalDebit,
                    'total_credit'  => $totalCredit,
                    'currency_code' => 'AED',
                    'exchange_rate' => 1.0,
                    'is_reversal'   => false,
                    'is_recurring'  => false,
                    'is_deleted'    => false,
                    'created_by_id' => auth()->user()->id,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                foreach ($lines as $i => $line) {
                    $account = DB::table('accounts')
                        ->where('company_id', $companyId)
                        ->where('code', $line['account_code'])
                        ->first();

                    if (!$account) continue;

                    DB::table('journal_lines')->insert([
                        'journal_entry_id' => $jeId,
                        'company_id'       => $companyId,
                        'account_id'       => $account->id,
                        'line_number'      => $i + 1,
                        'description'      => $line['line_description'] ?? $line['description'] ?? '',
                        'debit_amount'     => floatval($line['debit_amount']),
                        'credit_amount'    => floatval($line['credit_amount']),
                        'currency_code'    => 'AED',
                        'exchange_rate'    => 1.0,
                        'is_reconciled'    => false,
                    ]);
                }
            });

            $imported++;
        }

        return back()->with('success', "Import complete: {$imported} journal entries imported, {$skipped} skipped (unbalanced).");
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function toCSV(array $rows, string $filename)
    {
        if (empty($rows)) return back()->with('error', 'No data to export.');

        $output = fopen('php://output', 'w');
        ob_start();
        fputcsv($output, array_keys($rows[0]));
        foreach ($rows as $row) fputcsv($output, $row);
        $content = ob_get_clean();
        fclose($output);

        return response($content, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}.csv",
        ]);
    }

    private function toXML(array $rows, string $root, string $item, string $filename)
    {
        if (empty($rows)) return back()->with('error', 'No data to export.');

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<{$root} generated=\"" . now()->toIso8601String() . "\">\n";
        foreach ($rows as $row) {
            $xml .= "  <{$item}>\n";
            foreach ($row as $key => $value) {
                $key   = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
                $value = htmlspecialchars((string)$value, ENT_XML1);
                $xml  .= "    <{$key}>{$value}</{$key}>\n";
            }
            $xml .= "  </{$item}>\n";
        }
        $xml .= "</{$root}>";

        return response($xml, 200, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => "attachment; filename={$filename}.xml",
        ]);
    }
public function importCustomers(Request $request)
{
    if (!auth()->user()->is_super_admin) abort(403);
    $companyId = session('company_id');

    $request->validate(['file' => 'required|file|mimes:csv,txt']);

    $file     = $request->file('file');
    $handle   = fopen($file->getPathname(), 'r');
    $headers  = null;
    $imported = 0;
    $skipped  = 0;

    // Get default AR account
    $defaultAR = DB::table('accounts')
        ->where('company_id', $companyId)
        ->where('code', 'AR1')
        ->value('id');

    while (($row = fgetcsv($handle)) !== false) {
        if (empty($row[0]) || str_starts_with(trim($row[0]), '#')) continue;
        if (!$headers) { $headers = array_map('trim', $row); continue; }

        $data = array_combine($headers, array_map('trim', $row));
        if (empty($data['name'])) { $skipped++; continue; }

        // Check duplicate code
        if (DB::table('customers')->where('company_id', $companyId)->where('code', $data['code'])->exists()) {
            $skipped++;
            continue;
        }

        // Try to find matching AR account by name
        $arAccount = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('account_type', 'ASSET')
            ->where('name', $data['name'])
            ->value('id');

        DB::table('customers')->insert([
            'company_id'         => $companyId,
            'code'               => $data['code'],
            'name'               => $data['name'],
            'name_arabic'        => $data['name_arabic'] ?? null,
            'trn'                => $data['trn'] ?? null,
            'is_vat_registered'  => !empty($data['trn']),
            'email'              => $data['email'] ?? null,
            'phone'              => $data['phone'] ?? null,
            'mobile'             => $data['mobile'] ?? null,
            'contact_person'     => $data['contact_person'] ?? null,
            'address_line1'      => $data['address_line1'] ?? null,
            'city'               => $data['city'] ?? null,
            'emirate'            => $data['emirate'] ?? null,
            'country'            => $data['country'] ?? 'UAE',
            'payment_terms_days' => intval($data['payment_terms_days'] ?? 30),
            'credit_limit'       => floatval($data['credit_limit'] ?? 0),
            'currency_code'      => 'AED',
            'ar_account_id'      => $arAccount ?? $defaultAR,
            'is_active'          => true,
            'is_deleted'         => false,
            'blacklisted'        => false,
            'created_by_id'      => auth()->user()->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $imported++;
    }

    fclose($handle);
    return back()->with('success', "Import complete: {$imported} customers imported, {$skipped} skipped.");
}
}