<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->is_super_admin) {
            $companies = DB::table('companies')->select('id', 'name')->get()->toArray();
        } else {
            $companies = DB::table('companies')
                ->join('user_companies', 'companies.id', '=', 'user_companies.company_id')
                ->where('user_companies.user_id', $user->id)
                ->select('companies.id', 'companies.name')->get()->toArray();
        }

        session(['companies' => array_map(fn($c) => ['id' => $c->id, 'name' => $c->name], $companies)]);

        if (!session('company_id') && count($companies) > 0) {
            session(['company_id' => $companies[0]->id, 'company_name' => $companies[0]->name]);
        }

        $companyId = session('company_id');
        $year      = (int) date('Y');
        $month     = (int) date('m');

        if (!$companyId) {
            return view('dashboard', ['hasCompany' => false]);
        }

        // ── P&L YTD ──────────────────────────────────────────────────────────────
        $plYtd = DB::selectOne("
            SELECT
                COALESCE(SUM(CASE WHEN a.account_type = 'REVENUE' THEN jl.credit_amount - jl.debit_amount ELSE 0 END), 0) AS revenue,
                COALESCE(SUM(CASE WHEN a.account_type = 'EXPENSE' THEN jl.debit_amount - jl.credit_amount ELSE 0 END), 0) AS expenses
            FROM journal_lines jl
            JOIN journal_entries je ON je.id = jl.journal_entry_id
            JOIN accounts a ON a.id = jl.account_id
            WHERE je.company_id = ? AND je.status = 'POSTED'
              AND je.journal_type != 'CLOSING_ENTRY'
              AND EXTRACT(YEAR FROM je.entry_date) = ?
        ", [$companyId, $year]);

        $revenue  = (float) ($plYtd->revenue  ?? 0);
        $expenses = (float) ($plYtd->expenses ?? 0);
        $netProfit = $revenue - $expenses;

        // ── Cash & Bank balances ──────────────────────────────────────────────────
        $cash = DB::selectOne("
            SELECT COALESCE(SUM(jl.debit_amount - jl.credit_amount), 0) AS balance
            FROM journal_lines jl
            JOIN journal_entries je ON je.id = jl.journal_entry_id
            JOIN accounts a ON a.id = jl.account_id
            WHERE je.company_id = ? AND je.status = 'POSTED'
              AND a.account_type = 'ASSET'
              AND (a.is_cash = true OR a.is_bank = true)
        ", [$companyId]);
        $cashBalance = (float) ($cash->balance ?? 0);

        // ── Receivables ───────────────────────────────────────────────────────────
        $receivables = DB::selectOne("
            SELECT
                COALESCE(SUM(total_amount), 0)                                                      AS total,
                COALESCE(SUM(CASE WHEN due_date < CURRENT_DATE THEN total_amount ELSE 0 END), 0)    AS overdue,
                COUNT(*)                                                                              AS count,
                COUNT(CASE WHEN due_date < CURRENT_DATE THEN 1 END)                                 AS overdue_count
            FROM invoices
            WHERE company_id = ? AND status NOT IN ('PAID','VOID') AND is_deleted = false
        ", [$companyId]);

        // ── Payables ──────────────────────────────────────────────────────────────
        $payables = DB::selectOne("
            SELECT
                COALESCE(SUM(total_amount), 0)                                                      AS total,
                COALESCE(SUM(CASE WHEN due_date < CURRENT_DATE THEN total_amount ELSE 0 END), 0)    AS overdue,
                COUNT(*)                                                                              AS count
            FROM bills
            WHERE company_id = ? AND status NOT IN ('PAID','VOID') AND is_deleted = false
        ", [$companyId]);

        // ── Monthly revenue vs expenses (last 6 months) ───────────────────────────
        $monthlyData = DB::select("
            SELECT
                TO_CHAR(je.entry_date, 'Mon') AS month_label,
                EXTRACT(MONTH FROM je.entry_date) AS month_num,
                EXTRACT(YEAR FROM je.entry_date)  AS year_num,
                COALESCE(SUM(CASE WHEN a.account_type = 'REVENUE' THEN jl.credit_amount - jl.debit_amount ELSE 0 END), 0) AS revenue,
                COALESCE(SUM(CASE WHEN a.account_type = 'EXPENSE' THEN jl.debit_amount - jl.credit_amount ELSE 0 END), 0) AS expenses
            FROM journal_lines jl
            JOIN journal_entries je ON je.id = jl.journal_entry_id
            JOIN accounts a ON a.id = jl.account_id
            WHERE je.company_id = ? AND je.status = 'POSTED'
              AND je.journal_type != 'CLOSING_ENTRY'
              AND je.entry_date >= DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '5 months'
            GROUP BY month_label, month_num, year_num
            ORDER BY year_num, month_num
        ", [$companyId]);

        // ── Recent invoices ────────────────────────────────────────────────────────
        $recentInvoices = DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.company_id', $companyId)
            ->where('invoices.is_deleted', false)
            ->select('invoices.id', 'invoices.invoice_number', 'invoices.total_amount',
                     'invoices.status', 'invoices.invoice_date', 'invoices.due_date',
                     'customers.name as customer_name')
            ->orderByDesc('invoices.invoice_date')
            ->limit(6)->get();

        // ── Recent bills ───────────────────────────────────────────────────────────
        $recentBills = DB::table('bills')
            ->join('vendors', 'vendors.id', '=', 'bills.vendor_id')
            ->where('bills.company_id', $companyId)
            ->where('bills.is_deleted', false)
            ->select('bills.id', 'bills.bill_number', 'bills.total_amount',
                     'bills.status', 'bills.bill_date', 'bills.due_date',
                     'vendors.name as vendor_name')
            ->orderByDesc('bills.bill_date')
            ->limit(5)->get();

        // ── VAT position (current quarter / period) ───────────────────────────────
        $vatPeriod = DB::table('vat_returns')
            ->where('company_id', $companyId)
            ->where('status', '!=', 'PAID')
            ->orderByDesc('period_to')->first();

        $vatCollected   = 0;
        $vatReclaimable = 0;
        $vatNetDue      = 0;
        if ($vatPeriod) {
            $vatCollected   = (float) ($vatPeriod->box6_total_output_tax ?? 0);
            $vatReclaimable = (float) ($vatPeriod->box9_total_input_tax  ?? 0);
            $vatNetDue      = $vatCollected - $vatReclaimable;
        }

        // ── CT estimate ────────────────────────────────────────────────────────────
        $estimatedCT = 0;
        if ($netProfit > 375000) {
            $estimatedCT = round(($netProfit - 375000) * 0.09, 2);
        }

        return view('dashboard', compact(
            'revenue', 'expenses', 'netProfit',
            'cashBalance',
            'receivables', 'payables',
            'monthlyData',
            'recentInvoices', 'recentBills',
            'vatCollected', 'vatReclaimable', 'vatNetDue', 'vatPeriod',
            'estimatedCT',
            'year', 'month'
        ) + ['hasCompany' => true]);
    }
}
