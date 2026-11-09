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

        $revenue   = (float) ($plYtd->revenue  ?? 0);
        $expenses  = (float) ($plYtd->expenses ?? 0);
        $netProfit = $revenue - $expenses;

        // ── YTD last year (same Jan–month window) for YoY delta ──────────────────
        $plLastYear = DB::selectOne("
            SELECT
                COALESCE(SUM(CASE WHEN a.account_type='REVENUE' THEN jl.credit_amount-jl.debit_amount ELSE 0 END),0) AS revenue,
                COALESCE(SUM(CASE WHEN a.account_type='EXPENSE' THEN jl.debit_amount-jl.credit_amount ELSE 0 END),0) AS expenses
            FROM journal_lines jl
            JOIN journal_entries je ON je.id=jl.journal_entry_id
            JOIN accounts a ON a.id=jl.account_id
            WHERE je.company_id=? AND je.status='POSTED' AND je.journal_type!='CLOSING_ENTRY'
              AND EXTRACT(YEAR FROM je.entry_date)=?
              AND EXTRACT(MONTH FROM je.entry_date) <= ?
        ", [$companyId, $year - 1, $month]);

        $revPY     = (float)($plLastYear->revenue  ?? 0);
        $expPY     = (float)($plLastYear->expenses ?? 0);
        $netPY     = $revPY - $expPY;

        $yoyRevDelta = $revPY > 0 ? round(($revenue  - $revPY)  / $revPY         * 100, 1) : null;
        $yoyExpDelta = $expPY > 0 ? round(($expenses - $expPY)  / $expPY         * 100, 1) : null;
        $yoyNetDelta = $netPY != 0 ? round(($netProfit - $netPY) / abs($netPY)   * 100, 1) : null;

        // ── Sparkline data: monthly values for current year (Jan → now) ───────────
        $sparklineData = DB::select("
            SELECT
                EXTRACT(MONTH FROM je.entry_date) AS month_num,
                TO_CHAR(je.entry_date, 'Mon') AS month_label,
                COALESCE(SUM(CASE WHEN a.account_type='REVENUE' THEN jl.credit_amount-jl.debit_amount ELSE 0 END),0) AS revenue,
                COALESCE(SUM(CASE WHEN a.account_type='EXPENSE' THEN jl.debit_amount-jl.credit_amount ELSE 0 END),0) AS expenses
            FROM journal_lines jl
            JOIN journal_entries je ON je.id=jl.journal_entry_id
            JOIN accounts a ON a.id=jl.account_id
            WHERE je.company_id=? AND je.status='POSTED' AND je.journal_type!='CLOSING_ENTRY'
              AND EXTRACT(YEAR FROM je.entry_date)=?
            GROUP BY month_num, month_label
            ORDER BY month_num
        ", [$companyId, $year]);

        // ── Cumulative YTD arrays (all months Jan → current, missing months = 0) ─
        $sparkByMonth = [];
        foreach ($sparklineData as $m) {
            $sparkByMonth[(int)$m->month_num] = $m;
        }
        $cumulRev = []; $cumulExp = []; $cumulNet = [];
        $runR = 0; $runE = 0;
        for ($mo = 1; $mo <= $month; $mo++) {
            $runR += isset($sparkByMonth[$mo]) ? (float)$sparkByMonth[$mo]->revenue  : 0;
            $runE += isset($sparkByMonth[$mo]) ? (float)$sparkByMonth[$mo]->expenses : 0;
            $cumulRev[] = $runR;
            $cumulExp[] = $runE;
            $cumulNet[] = $runR - $runE;
        }

        // ── Last year FULL-YEAR totals (flat reference line for sparklines) ──────
        $plFullPY = DB::selectOne("
            SELECT
                COALESCE(SUM(CASE WHEN a.account_type='REVENUE' THEN jl.credit_amount-jl.debit_amount ELSE 0 END),0) AS revenue,
                COALESCE(SUM(CASE WHEN a.account_type='EXPENSE' THEN jl.debit_amount-jl.credit_amount ELSE 0 END),0) AS expenses
            FROM journal_lines jl
            JOIN journal_entries je ON je.id=jl.journal_entry_id
            JOIN accounts a ON a.id=jl.account_id
            WHERE je.company_id=? AND je.status='POSTED' AND je.journal_type!='CLOSING_ENTRY'
              AND EXTRACT(YEAR FROM je.entry_date)=?
        ", [$companyId, $year - 1]);

        $lyRevTotal = (float)($plFullPY->revenue  ?? 0);
        $lyExpTotal = (float)($plFullPY->expenses ?? 0);
        $lyNetTotal = $lyRevTotal - $lyExpTotal;

        // ── Cash & Bank balances (journal movements + opening balances) ─────────
        $cash = DB::selectOne("
            SELECT
                COALESCE(SUM(jl.debit_amount - jl.credit_amount), 0)
              + COALESCE((
                    SELECT SUM(COALESCE(a2.opening_balance, 0))
                    FROM accounts a2
                    WHERE a2.company_id = ?
                      AND a2.account_type = 'ASSET'
                      AND (a2.is_cash = true OR a2.is_bank = true)
                ), 0) AS balance
            FROM journal_lines jl
            JOIN journal_entries je ON je.id = jl.journal_entry_id
            JOIN accounts a ON a.id = jl.account_id
            WHERE je.company_id = ? AND je.status = 'POSTED'
              AND a.account_type = 'ASSET'
              AND (a.is_cash = true OR a.is_bank = true)
        ", [$companyId, $companyId]);
        $cashBalance = (float) ($cash->balance ?? 0);

        // ── Cash monthly balances (last year Dec 31 as flat reference) ───────────
        // Opening = all cash movements + opening_balance fields before Jan 1 of current year
        $cashOpenRow = DB::selectOne("
            SELECT
                COALESCE(SUM(jl.debit_amount - jl.credit_amount), 0)
              + COALESCE((
                    SELECT SUM(COALESCE(a2.opening_balance, 0))
                    FROM accounts a2
                    WHERE a2.company_id = ? AND a2.account_type = 'ASSET'
                      AND (a2.is_cash = true OR a2.is_bank = true)
                ), 0) AS balance
            FROM journal_lines jl
            JOIN journal_entries je ON je.id = jl.journal_entry_id
            JOIN accounts a ON a.id = jl.account_id
            WHERE je.company_id = ? AND je.status = 'POSTED'
              AND a.account_type = 'ASSET' AND (a.is_cash = true OR a.is_bank = true)
              AND je.entry_date < ?
        ", [$companyId, $companyId, "{$year}-01-01"]);
        $lyDecBalance = (float)($cashOpenRow->balance ?? 0);

        $cashMoves = DB::select("
            SELECT CAST(EXTRACT(MONTH FROM je.entry_date) AS INTEGER) AS month_num,
                   COALESCE(SUM(jl.debit_amount - jl.credit_amount), 0) AS movement
            FROM journal_lines jl
            JOIN journal_entries je ON je.id = jl.journal_entry_id
            JOIN accounts a ON a.id = jl.account_id
            WHERE je.company_id = ? AND je.status = 'POSTED'
              AND a.account_type = 'ASSET' AND (a.is_cash = true OR a.is_bank = true)
              AND EXTRACT(YEAR FROM je.entry_date) = ?
            GROUP BY month_num ORDER BY month_num
        ", [$companyId, $year]);
        $moveByMo = [];
        foreach ($cashMoves as $m) { $moveByMo[$m->month_num] = (float)$m->movement; }
        $cashMonthlyBals = [];
        $runC = $lyDecBalance;
        for ($mo = 1; $mo <= $month; $mo++) {
            $runC += $moveByMo[$mo] ?? 0;
            $cashMonthlyBals[] = $runC;
        }

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

        // ── Outstanding invoices (unpaid, oldest due first) ────────────────────
        $outstandingInvoices = DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.company_id', $companyId)
            ->where('invoices.is_deleted', false)
            ->whereNotIn('invoices.status', ['PAID', 'VOID'])
            ->select('invoices.id', 'invoices.invoice_number', 'invoices.total_amount',
                     'invoices.status', 'invoices.due_date',
                     'customers.name as customer_name')
            ->orderBy('invoices.due_date')
            ->limit(7)->get();

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

        // ── VAT position: live from invoices/bills YTD ───────────────────────────
        $vatYtdFrom = "{$year}-01-01";
        $vatYtdTo   = date('Y-m-d');

        $vatOut = DB::table('invoices')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->whereNotIn('status', ['VOID', 'DRAFT'])
            ->whereBetween('invoice_date', [$vatYtdFrom, $vatYtdTo])
            ->selectRaw('COALESCE(SUM(total_vat_amount), 0) AS vat')
            ->first();

        $vatIn = DB::table('bills')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->whereNotIn('status', ['VOID', 'DRAFT'])
            ->whereBetween('bill_date', [$vatYtdFrom, $vatYtdTo])
            ->selectRaw('COALESCE(SUM(total_vat_amount), 0) AS vat')
            ->first();

        $vatCollected   = (float)($vatOut->vat ?? 0);
        $vatReclaimable = (float)($vatIn->vat  ?? 0);
        $vatNetDue      = $vatCollected - $vatReclaimable;
        $vatPeriod      = null;
        $vatQtrLabel    = "YTD {$year}";

        // ── CT estimate ────────────────────────────────────────────────────────────
        $estimatedCT = 0;
        if ($netProfit > 375000) {
            $estimatedCT = round(($netProfit - 375000) * 0.09, 2);
        }

        return view('dashboard', compact(
            'revenue', 'expenses', 'netProfit',
            'cashBalance',
            'receivables', 'payables',
            'monthlyData', 'sparklineData',
            'cumulRev', 'cumulExp', 'cumulNet',
            'lyRevTotal', 'lyExpTotal', 'lyNetTotal',
            'cashMonthlyBals', 'lyDecBalance',
            'recentInvoices', 'recentBills', 'outstandingInvoices',
            'vatCollected', 'vatReclaimable', 'vatNetDue', 'vatPeriod', 'vatQtrLabel',
            'estimatedCT',
            'year', 'month',
            'yoyRevDelta', 'yoyExpDelta', 'yoyNetDelta'
        ) + ['hasCompany' => true]);
    }
}
