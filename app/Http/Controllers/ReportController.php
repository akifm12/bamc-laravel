<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\TrialBalanceExport;
use App\Exports\PnlExport;
use App\Exports\BalanceSheetExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function trialBalance(Request $request)
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $dateFrom = $request->get('date_from', date('Y-01-01'));
        $dateTo   = $request->get('date_to',   date('Y-m-d'));

        $rows = DB::select("
            SELECT
                a.code, a.name, a.account_type,
                COALESCE(SUM(jl.debit_amount), 0)  AS total_debit,
                COALESCE(SUM(jl.credit_amount), 0) AS total_credit
            FROM accounts a
            LEFT JOIN (
                SELECT jl.*
                FROM journal_lines jl
                JOIN journal_entries je ON je.id = jl.journal_entry_id
                WHERE je.company_id = ?
                  AND je.status = 'POSTED'
                  AND je.journal_type != 'CLOSING_ENTRY'
                  AND je.entry_date >= ?
                  AND je.entry_date <= ?
            ) jl ON jl.account_id = a.id
            WHERE a.company_id = ?
            GROUP BY a.id, a.code, a.name, a.account_type
            HAVING COALESCE(SUM(jl.debit_amount), 0) > 0
                OR COALESCE(SUM(jl.credit_amount), 0) > 0
            ORDER BY
                CASE a.account_type
                    WHEN 'ASSET' THEN 1 WHEN 'LIABILITY' THEN 2
                    WHEN 'EQUITY' THEN 3 WHEN 'REVENUE' THEN 4
                    WHEN 'EXPENSE' THEN 5 ELSE 6
                END, a.code
        ", [$companyId, $dateFrom, $dateTo, $companyId]);

        $totalDebit  = array_sum(array_column($rows, 'total_debit'));
        $totalCredit = array_sum(array_column($rows, 'total_credit'));
        $grouped     = collect($rows)->groupBy('account_type');

        return view('reports.trial_balance', compact('grouped', 'totalDebit', 'totalCredit', 'dateFrom', 'dateTo'));
    }

    public function pnl(Request $request)
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $dateFrom = $request->get('date_from', date('Y-01-01'));
        $dateTo   = $request->get('date_to',   date('Y-m-d'));

        $rows = DB::select("
            SELECT
                a.code, a.name, a.account_type,
                COALESCE(SUM(jl.credit_amount), 0) - COALESCE(SUM(jl.debit_amount), 0) AS balance
            FROM accounts a
            JOIN (
                SELECT jl.*
                FROM journal_lines jl
                JOIN journal_entries je ON je.id = jl.journal_entry_id
                WHERE je.company_id = ?
                  AND je.status = 'POSTED'
                  AND je.journal_type != 'CLOSING_ENTRY'
                  AND je.entry_date >= ?
                  AND je.entry_date <= ?
            ) jl ON jl.account_id = a.id
            WHERE a.company_id = ?
              AND a.account_type IN ('REVENUE', 'EXPENSE')
            GROUP BY a.id, a.code, a.name, a.account_type
            HAVING ABS(COALESCE(SUM(jl.credit_amount), 0) - COALESCE(SUM(jl.debit_amount), 0)) > 0
            ORDER BY a.account_type, a.code
        ", [$companyId, $dateFrom, $dateTo, $companyId]);

        $revenue  = collect($rows)->where('account_type', 'REVENUE');
        $expenses = collect($rows)->where('account_type', 'EXPENSE');

        $totalRevenue  = $revenue->sum('balance');
        $totalExpenses = $expenses->sum(fn($r) => -$r->balance);
        $netProfit     = $totalRevenue - $totalExpenses;

        return view('reports.pnl', compact('revenue', 'expenses', 'totalRevenue', 'totalExpenses', 'netProfit', 'dateFrom', 'dateTo'));
    }

    public function balanceSheet(Request $request)
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $asOf = $request->get('as_of', date('Y-m-d'));
        $data = $this->getBalanceSheetData($companyId, $asOf);

        extract($data);
        return view('reports.balance_sheet', compact(
            'assets', 'liabilities', 'equity',
            'totalAssets', 'totalLiabilities', 'totalEquity', 'totalLE',
            'ytdProfit', 'asOf'
        ));
    }

    public function exportTrialBalance(Request $request)
    {
        $companyId = session('company_id');
        $dateFrom  = $request->get('date_from', date('Y-01-01'));
        $dateTo    = $request->get('date_to',   date('Y-m-d'));
        $format    = $request->get('format', 'csv');

        $rows = DB::select("
            SELECT
                a.code, a.name, a.account_type,
                COALESCE(SUM(jl.debit_amount), 0)  AS total_debit,
                COALESCE(SUM(jl.credit_amount), 0) AS total_credit
            FROM accounts a
            LEFT JOIN (
                SELECT jl.*
                FROM journal_lines jl
                JOIN journal_entries je ON je.id = jl.journal_entry_id
                WHERE je.company_id = ?
                  AND je.status = 'POSTED'
                  AND je.journal_type != 'CLOSING_ENTRY'
                  AND je.entry_date >= ?
                  AND je.entry_date <= ?
            ) jl ON jl.account_id = a.id
            WHERE a.company_id = ?
            GROUP BY a.id, a.code, a.name, a.account_type
            HAVING COALESCE(SUM(jl.debit_amount), 0) > 0
                OR COALESCE(SUM(jl.credit_amount), 0) > 0
            ORDER BY
                CASE a.account_type
                    WHEN 'ASSET' THEN 1 WHEN 'LIABILITY' THEN 2
                    WHEN 'EQUITY' THEN 3 WHEN 'REVENUE' THEN 4
                    WHEN 'EXPENSE' THEN 5 ELSE 6
                END, a.code
        ", [$companyId, $dateFrom, $dateTo, $companyId]);

        $companyName = session('company_name', 'Company');

        if ($format === 'excel') {
            return Excel::download(
                new TrialBalanceExport($rows, $companyName, $dateFrom, $dateTo),
                "trial_balance_{$dateTo}.xlsx"
            );
        }
        if ($format === 'pdf') {
            $grouped     = collect($rows)->groupBy('account_type');
            $totalDebit  = array_sum(array_column($rows, 'total_debit'));
            $totalCredit = array_sum(array_column($rows, 'total_credit'));
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.exports.trial_balance_pdf',
                compact('grouped', 'totalDebit', 'totalCredit', 'dateFrom', 'dateTo', 'companyName'));
            return $pdf->download("trial_balance_{$dateTo}.pdf");
        }

        $filename = "trial_balance_{$dateTo}.csv";
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];
        $callback = function() use ($rows, $companyName, $dateFrom, $dateTo) {
            $f = fopen('php://output', 'w');
            fputcsv($f, [$companyName]);
            fputcsv($f, ["Trial Balance: {$dateFrom} to {$dateTo}"]);
            fputcsv($f, []);
            fputcsv($f, ['Code', 'Account Name', 'Type', 'Debit', 'Credit', 'Balance']);
            foreach ($rows as $r) {
                fputcsv($f, [$r->code, $r->name, $r->account_type,
                    number_format($r->total_debit, 2), number_format($r->total_credit, 2),
                    number_format($r->total_debit - $r->total_credit, 2)]);
            }
            fputcsv($f, []);
            fputcsv($f, ['', '', 'TOTAL',
                number_format(array_sum(array_column((array)$rows, 'total_debit')), 2),
                number_format(array_sum(array_column((array)$rows, 'total_credit')), 2), '']);
            fclose($f);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportPnl(Request $request)
    {
        $companyId = session('company_id');
        $dateFrom  = $request->get('date_from', date('Y-01-01'));
        $dateTo    = $request->get('date_to',   date('Y-m-d'));
        $format    = $request->get('format', 'csv');

        $rows = DB::select("
            SELECT a.code, a.name, a.account_type,
                COALESCE(SUM(jl.credit_amount), 0) - COALESCE(SUM(jl.debit_amount), 0) AS balance
            FROM accounts a
            JOIN (
                SELECT jl.*
                FROM journal_lines jl
                JOIN journal_entries je ON je.id = jl.journal_entry_id
                WHERE je.company_id = ?
                  AND je.status = 'POSTED'
                  AND je.journal_type != 'CLOSING_ENTRY'
                  AND je.entry_date >= ?
                  AND je.entry_date <= ?
            ) jl ON jl.account_id = a.id
            WHERE a.company_id = ?
              AND a.account_type IN ('REVENUE', 'EXPENSE')
            GROUP BY a.id, a.code, a.name, a.account_type
            ORDER BY a.account_type, a.code
        ", [$companyId, $dateFrom, $dateTo, $companyId]);

        $companyName   = session('company_name', 'Company');
        $revenue       = collect($rows)->where('account_type', 'REVENUE');
        $expenses      = collect($rows)->where('account_type', 'EXPENSE');
        $totalRevenue  = $revenue->sum('balance');
        $totalExpenses = $expenses->sum(fn($r) => -$r->balance);
        $netProfit     = $totalRevenue - $totalExpenses;

        if ($format === 'excel') {
            return Excel::download(
                new PnlExport($revenue, $expenses, $totalRevenue, $totalExpenses, $netProfit, $companyName, $dateFrom, $dateTo),
                "pnl_{$dateFrom}_to_{$dateTo}.xlsx"
            );
        }
        if ($format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.exports.pnl_pdf',
                compact('revenue', 'expenses', 'totalRevenue', 'totalExpenses', 'netProfit', 'dateFrom', 'dateTo', 'companyName'));
            return $pdf->download("pnl_{$dateFrom}_to_{$dateTo}.pdf");
        }

        $filename = "pnl_{$dateFrom}_to_{$dateTo}.csv";
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];
        $callback = function() use ($revenue, $expenses, $totalRevenue, $totalExpenses, $netProfit, $companyName, $dateFrom, $dateTo) {
            $f = fopen('php://output', 'w');
            fputcsv($f, [$companyName]);
            fputcsv($f, ["Profit & Loss: {$dateFrom} to {$dateTo}"]);
            fputcsv($f, []);
            fputcsv($f, ['REVENUE']);
            fputcsv($f, ['Code', 'Account', 'Amount']);
            foreach ($revenue as $r) {
                fputcsv($f, [$r->code, $r->name, number_format($r->balance, 2)]);
            }
            fputcsv($f, ['', 'Total Revenue', number_format($totalRevenue, 2)]);
            fputcsv($f, []);
            fputcsv($f, ['EXPENSES']);
            foreach ($expenses as $r) {
                fputcsv($f, [$r->code, $r->name, number_format(abs($r->balance), 2)]);
            }
            fputcsv($f, ['', 'Total Expenses', number_format($totalExpenses, 2)]);
            fputcsv($f, []);
            fputcsv($f, ['', 'NET PROFIT / (LOSS)', number_format($netProfit, 2)]);
            fclose($f);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportBalanceSheet(Request $request)
    {
        $companyId = session('company_id');
        $asOf      = $request->get('as_of', date('Y-m-d'));
        $format    = $request->get('format', 'csv');

        $data        = $this->getBalanceSheetData($companyId, $asOf);
        $companyName = session('company_name', 'Company');

        if ($format === 'excel') {
            return Excel::download(
                new BalanceSheetExport($data, $companyName, $asOf),
                "balance_sheet_{$asOf}.xlsx"
            );
        }
        if ($format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.exports.balance_sheet_pdf',
                array_merge($data, compact('companyName', 'asOf')));
            return $pdf->download("balance_sheet_{$asOf}.pdf");
        }

        $filename = "balance_sheet_{$asOf}.csv";
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];
        $callback = function() use ($data, $companyName, $asOf) {
            $f = fopen('php://output', 'w');
            fputcsv($f, [$companyName]);
            fputcsv($f, ["Balance Sheet as at {$asOf}"]);
            fputcsv($f, []);
            fputcsv($f, ['ASSETS']);
            fputcsv($f, ['Code', 'Account', 'Balance']);
            foreach ($data['assets'] as $r) {
                fputcsv($f, [$r->code, $r->name, number_format($r->balance, 2)]);
            }
            fputcsv($f, ['', 'Total Assets', number_format($data['totalAssets'], 2)]);
            fputcsv($f, []);
            fputcsv($f, ['LIABILITIES']);
            foreach ($data['liabilities'] as $r) {
                fputcsv($f, [$r->code, $r->name, number_format($r->balance, 2)]);
            }
            fputcsv($f, ['', 'Total Liabilities', number_format($data['totalLiabilities'], 2)]);
            fputcsv($f, []);
            fputcsv($f, ['EQUITY']);
            foreach ($data['equity'] as $r) {
                fputcsv($f, [$r->code, $r->name, number_format($r->balance, 2)]);
            }
            fputcsv($f, ['', 'YTD Net Profit/(Loss)', number_format($data['ytdProfit'], 2)]);
            fputcsv($f, ['', 'Total Equity', number_format($data['totalEquity'], 2)]);
            fputcsv($f, []);
            fputcsv($f, ['', 'Total Liabilities + Equity', number_format($data['totalLE'], 2)]);
            fclose($f);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function getBalanceSheetData($companyId, $asOf)
    {
        $lastClose = DB::table('journal_entries')
            ->where('company_id', $companyId)
            ->where('journal_type', 'CLOSING_ENTRY')
            ->where('status', 'POSTED')
            ->where('entry_date', '<=', $asOf)
            ->max('entry_date');

        $ytdFrom = $lastClose
            ? \Carbon\Carbon::parse($lastClose)->addDay()->toDateString()
            : null;

        // Balance sheet uses cumulative balances up to asOf date
        $rows = DB::select("
            SELECT a.code, a.name, a.account_type,
                COALESCE(SUM(jl.debit_amount), 0) AS dr,
                COALESCE(SUM(jl.credit_amount), 0) AS cr
            FROM accounts a
            LEFT JOIN (
                SELECT jl.*
                FROM journal_lines jl
                JOIN journal_entries je ON je.id = jl.journal_entry_id
                WHERE je.company_id = ?
                  AND je.status = 'POSTED'
                  AND je.entry_date <= ?
            ) jl ON jl.account_id = a.id
            WHERE a.company_id = ?
            GROUP BY a.id, a.code, a.name, a.account_type
        ", [$companyId, $asOf, $companyId]);

        $accounts = collect($rows)->map(function($r) {
            $atype = strtolower($r->account_type);
            $bal   = in_array($atype, ['asset', 'expense'])
                ? $r->dr - $r->cr
                : $r->cr - $r->dr;
            return (object) array_merge((array)$r, ['balance' => $bal]);
        })->filter(fn($r) => abs($r->balance) > 0.001);

        // YTD profit
        if ($ytdFrom) {
            $ytdParams     = [$companyId, $ytdFrom, $asOf, $companyId];
            $ytdDateFilter = "AND je.entry_date >= ? AND je.entry_date <= ?";
        } else {
            $ytdParams     = [$companyId, $asOf, $companyId];
            $ytdDateFilter = "AND je.entry_date <= ?";
        }

        $ytdRows = DB::select("
            SELECT a.account_type,
                COALESCE(SUM(jl.debit_amount), 0) AS dr,
                COALESCE(SUM(jl.credit_amount), 0) AS cr
            FROM accounts a
            JOIN (
                SELECT jl.*
                FROM journal_lines jl
                JOIN journal_entries je ON je.id = jl.journal_entry_id
                WHERE je.company_id = ?
                  AND je.status = 'POSTED'
                  AND je.journal_type != 'CLOSING_ENTRY'
                  {$ytdDateFilter}
            ) jl ON jl.account_id = a.id
            WHERE a.company_id = ?
              AND a.account_type IN ('REVENUE', 'EXPENSE')
            GROUP BY a.account_type
        ", $ytdParams);

        $ytdRevenue  = collect($ytdRows)->where('account_type', 'REVENUE')->sum(fn($r) => $r->cr - $r->dr);
        $ytdExpenses = collect($ytdRows)->where('account_type', 'EXPENSE')->sum(fn($r) => $r->dr - $r->cr);
        $ytdProfit   = $ytdRevenue - $ytdExpenses;

        $assets      = $accounts->where('account_type', 'ASSET');
        $liabilities = $accounts->where('account_type', 'LIABILITY');
        $equity      = $accounts->where('account_type', 'EQUITY');

        $totalAssets      = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity      = $equity->sum('balance') + $ytdProfit;
        $totalLE          = $totalLiabilities + $totalEquity;

        return compact('assets', 'liabilities', 'equity',
            'totalAssets', 'totalLiabilities', 'totalEquity', 'totalLE', 'ytdProfit');
    }

    public function agedAR(Request $request)
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $asOf = $request->get('as_of', date('Y-m-d'));

        $invoices = DB::select("
            SELECT
                c.name as customer_name,
                c.code as customer_code,
                i.invoice_number,
                i.invoice_date,
                i.due_date,
                i.total_amount,
                i.amount_paid,
                i.amount_due,
                CASE
                    WHEN i.due_date >= :asof1 THEN 'current'
                    WHEN :asof2 - i.due_date <= 30 THEN '1_30'
                    WHEN :asof3 - i.due_date <= 60 THEN '31_60'
                    WHEN :asof4 - i.due_date <= 90 THEN '61_90'
                    ELSE 'over_90'
                END as bucket
            FROM invoices i
            JOIN customers c ON c.id = i.customer_id
            WHERE i.company_id = :company_id
              AND i.is_deleted = false
              AND i.amount_due > 0
              AND i.status NOT IN ('VOID','DRAFT')
              AND i.invoice_date <= :asof5
            ORDER BY c.name, i.due_date
        ", [
            'asof1' => $asOf, 'asof2' => $asOf, 'asof3' => $asOf,
            'asof4' => $asOf, 'asof5' => $asOf, 'company_id' => $companyId,
        ]);

        $invoices = collect($invoices);
        $buckets  = [
            'current' => 'Current', '1_30' => '1-30 days',
            '31_60'   => '31-60 days', '61_90' => '61-90 days', 'over_90' => 'Over 90 days',
        ];
        $summary = [];
        foreach ($buckets as $key => $label) {
            $summary[$key] = $invoices->where('bucket', $key)->sum('amount_due');
        }
        $totalDue   = $invoices->sum('amount_due');
        $byCustomer = $invoices->groupBy('customer_name');

        return view('reports.aged_ar', compact('invoices', 'byCustomer', 'summary', 'buckets', 'totalDue', 'asOf'));
    }

    public function agedAP(Request $request)
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $asOf = $request->get('as_of', date('Y-m-d'));

        $bills = DB::select("
            SELECT
                v.name as vendor_name,
                v.code as vendor_code,
                b.bill_number,
                b.bill_date,
                b.due_date,
                b.total_amount,
                b.amount_paid,
                b.amount_due,
                CASE
                    WHEN b.due_date >= :asof1 THEN 'current'
                    WHEN :asof2 - b.due_date <= 30 THEN '1_30'
                    WHEN :asof3 - b.due_date <= 60 THEN '31_60'
                    WHEN :asof4 - b.due_date <= 90 THEN '61_90'
                    ELSE 'over_90'
                END as bucket
            FROM bills b
            JOIN vendors v ON v.id = b.vendor_id
            WHERE b.company_id = :company_id
              AND b.is_deleted = false
              AND b.amount_due > 0
              AND b.status NOT IN ('VOID','DRAFT')
              AND b.bill_date <= :asof5
            ORDER BY v.name, b.due_date
        ", [
            'asof1' => $asOf, 'asof2' => $asOf, 'asof3' => $asOf,
            'asof4' => $asOf, 'asof5' => $asOf, 'company_id' => $companyId,
        ]);

        $bills    = collect($bills);
        $buckets  = [
            'current' => 'Current', '1_30' => '1-30 days',
            '31_60'   => '31-60 days', '61_90' => '61-90 days', 'over_90' => 'Over 90 days',
        ];
        $summary = [];
        foreach ($buckets as $key => $label) {
            $summary[$key] = $bills->where('bucket', $key)->sum('amount_due');
        }
        $totalDue = $bills->sum('amount_due');
        $byVendor = $bills->groupBy('vendor_name');

        return view('reports.aged_ap', compact('bills', 'byVendor', 'summary', 'buckets', 'totalDue', 'asOf'));
    }

    public function cashFlow(Request $request)
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $dateFrom = $request->get('date_from', date('Y-01-01'));
        $dateTo   = $request->get('date_to',   date('Y-m-d'));

        $operating = DB::select("
            SELECT a.account_type, a.name, a.code,
                COALESCE(SUM(jl.debit_amount), 0)  as dr,
                COALESCE(SUM(jl.credit_amount), 0) as cr
            FROM accounts a
            JOIN (
                SELECT jl.*
                FROM journal_lines jl
                JOIN journal_entries je ON je.id = jl.journal_entry_id
                WHERE je.company_id = ?
                  AND je.status = 'POSTED'
                  AND je.journal_type != 'CLOSING_ENTRY'
                  AND je.entry_date >= ?
                  AND je.entry_date <= ?
            ) jl ON jl.account_id = a.id
            WHERE a.company_id = ?
              AND a.account_type IN ('REVENUE', 'EXPENSE')
            GROUP BY a.id, a.account_type, a.name, a.code
            ORDER BY a.account_type, a.code
        ", [$companyId, $dateFrom, $dateTo, $companyId]);

        $balanceChanges = DB::select("
            SELECT a.account_type, a.name, a.code,
                COALESCE(SUM(jl.debit_amount), 0)  as dr,
                COALESCE(SUM(jl.credit_amount), 0) as cr
            FROM accounts a
            JOIN (
                SELECT jl.*
                FROM journal_lines jl
                JOIN journal_entries je ON je.id = jl.journal_entry_id
                WHERE je.company_id = ?
                  AND je.status = 'POSTED'
                  AND je.entry_date >= ?
                  AND je.entry_date <= ?
            ) jl ON jl.account_id = a.id
            WHERE a.company_id = ?
              AND a.account_type IN ('ASSET', 'LIABILITY', 'EQUITY')
            GROUP BY a.id, a.account_type, a.name, a.code
            ORDER BY a.account_type, a.code
        ", [$companyId, $dateFrom, $dateTo, $companyId]);

        $operating      = collect($operating);
        $balanceChanges = collect($balanceChanges);

        $revenue   = $operating->where('account_type', 'REVENUE')->sum(fn($r) => $r->cr - $r->dr);
        $expenses  = $operating->where('account_type', 'EXPENSE')->sum(fn($r) => $r->dr - $r->cr);
        $netIncome = $revenue - $expenses;

        $assetChanges     = $balanceChanges->where('account_type', 'ASSET');
        $liabilityChanges = $balanceChanges->where('account_type', 'LIABILITY');
        $equityChanges    = $balanceChanges->where('account_type', 'EQUITY');

        $netAssetChange     = $assetChanges->sum(fn($r) => $r->dr - $r->cr);
        $netLiabilityChange = $liabilityChanges->sum(fn($r) => $r->cr - $r->dr);
        $netEquityChange    = $equityChanges->sum(fn($r) => $r->cr - $r->dr);
        $netCashFlow        = $netIncome - $netAssetChange + $netLiabilityChange + $netEquityChange;

        return view('reports.cash_flow', compact(
            'operating', 'assetChanges', 'liabilityChanges', 'equityChanges',
            'revenue', 'expenses', 'netIncome',
            'netAssetChange', 'netLiabilityChange', 'netEquityChange',
            'netCashFlow', 'dateFrom', 'dateTo'
        ));
    }
public function arLedger(Request $request)
{
    $companyId = session('company_id');
    if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

    $dateFrom = $request->get('date_from', date('Y-01-01'));
    $dateTo   = $request->get('date_to',   date('Y-m-d'));
    $asOf     = $request->get('as_of',     date('Y-m-d'));
    $mode     = $request->get('mode', 'balance'); // balance or ledger

    // Get all ASSET accounts that look like AR (individual client accounts)
    // Exclude generic accounts like AR1, CA-RB1, PC1 etc.
    $arAccounts = DB::select("
        SELECT
            a.id,
            a.code,
            a.name,
            COALESCE(SUM(CASE WHEN je.entry_date <= ? THEN jl.debit_amount ELSE 0 END), 0) AS total_dr,
            COALESCE(SUM(CASE WHEN je.entry_date <= ? THEN jl.credit_amount ELSE 0 END), 0) AS total_cr
        FROM accounts a
        LEFT JOIN (
            SELECT jl.*
            FROM journal_lines jl
            JOIN journal_entries je ON je.id = jl.journal_entry_id
            WHERE je.company_id = ?
              AND je.status = 'POSTED'
              AND je.entry_date <= ?
        ) jl ON jl.account_id = a.id
        JOIN journal_entries je ON je.id = jl.journal_entry_id
        WHERE a.company_id = ?
          AND a.account_type = 'ASSET'
          AND a.code NOT IN ('AR1','CA-RB1','PC1','CA1','A1','PLAL1','PS-K1','PEI-A1')
        GROUP BY a.id, a.code, a.name
        HAVING (
            COALESCE(SUM(CASE WHEN je.entry_date <= ? THEN jl.debit_amount ELSE 0 END), 0) > 0
            OR COALESCE(SUM(CASE WHEN je.entry_date <= ? THEN jl.credit_amount ELSE 0 END), 0) > 0
        )
        ORDER BY a.name
    ", [$asOf, $asOf, $companyId, $asOf, $companyId, $asOf, $asOf]);

    $arAccounts = collect($arAccounts)->map(function($a) {
        $a->balance = $a->total_dr - $a->total_cr;
        return $a;
    });

    $totalBalance = $arAccounts->sum('balance');
    $totalDr      = $arAccounts->sum('total_dr');
    $totalCr      = $arAccounts->sum('total_cr');

    // Get transaction lines per account if ledger mode
    $ledgerLines = [];
    if ($mode === 'ledger') {
        foreach ($arAccounts as $acc) {
            $lines = DB::select("
                SELECT
                    je.entry_date as date,
                    je.entry_number,
                    je.description,
                    je.reference,
                    jl.debit_amount,
                    jl.credit_amount,
                    jl.description as line_desc
                FROM journal_lines jl
                JOIN journal_entries je ON je.id = jl.journal_entry_id
                WHERE jl.account_id = ?
                  AND je.company_id = ?
                  AND je.status = 'POSTED'
                  AND je.entry_date >= ?
                  AND je.entry_date <= ?
                ORDER BY je.entry_date, je.id
            ", [$acc->id, $companyId, $dateFrom, $dateTo]);

            $running = 0;
            foreach ($lines as $line) {
                $running += $line->debit_amount - $line->credit_amount;
                $line->running_balance = $running;
            }

            if (!empty($lines)) {
                $ledgerLines[$acc->code] = $lines;
            }
        }
    }

    return view('reports.ar_ledger', compact(
        'arAccounts', 'totalBalance', 'totalDr', 'totalCr',
        'asOf', 'dateFrom', 'dateTo', 'mode', 'ledgerLines'
    ));
}
}