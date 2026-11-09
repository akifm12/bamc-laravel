<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CTController extends Controller
{
    const THRESHOLD = 375000;
    const CT_RATE   = 0.09;
    const SBR_REVENUE_LIMIT = 3000000;

    // ─── Index: list of fiscal years ───────────────────────────────────────────
    public function index()
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $company = DB::table('companies')->find($companyId);

        // UAE CT effective from 1 June 2023; first full fiscal year filings from 2024
        $firstYear   = 2024;
        $currentYear = (int) date('Y');

        $years = range($firstYear, $currentYear);

        // Load existing CT returns
        $returns = DB::table('ct_returns')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->get()
            ->keyBy('fiscal_year');

        // For each year, pull revenue from P&L
        $history = [];
        foreach (array_reverse($years) as $year) {
            $revenue = $this->getRevenue($companyId, $year);
            $netProfit = $this->getNetProfit($companyId, $year);
            $ct = $returns->get($year);
            $history[] = [
                'year'      => $year,
                'revenue'   => $revenue,
                'netProfit' => $netProfit,
                'return'    => $ct,
                'due'       => ($year + 1) . '-09-30', // 9 months after Dec 31
                'sbrEligible' => $revenue <= self::SBR_REVENUE_LIMIT && $revenue > 0,
            ];
        }

        // Current year quick stats
        $currentYear = (int) date('Y');
        $currentRevenue  = $this->getRevenue($companyId, $currentYear);
        $currentProfit   = $this->getNetProfit($companyId, $currentYear);
        $estimatedCT     = $this->calcCT($currentProfit);

        return view('ct.index', compact(
            'company', 'history', 'currentYear',
            'currentRevenue', 'currentProfit', 'estimatedCT'
        ));
    }

    // ─── Create / show CT return for a year ────────────────────────────────────
    public function create(Request $request)
    {
        $companyId = session('company_id');
        $year      = (int) $request->get('year', date('Y'));
        $company   = DB::table('companies')->find($companyId);

        // Check if already exists
        $existing = DB::table('ct_returns')
            ->where('company_id', $companyId)
            ->where('fiscal_year', $year)
            ->first();
        if ($existing) return redirect("/ct/{$existing->id}");

        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->whereYear('start_date', $year)
            ->orderBy('start_date')
            ->first();

        $periodStart = $period ? $period->start_date : "{$year}-01-01";
        $periodEnd   = $period ? $period->end_date   : "{$year}-12-31";

        $revenue   = $this->getRevenue($companyId, $year);
        $netProfit = $this->getNetProfit($companyId, $year);

        $addCategories = [
            'Entertainment (excess of 50%)',
            'Fines and penalties',
            'Donations to non-qualifying entities',
            'Non-deductible depreciation',
            'Non-arm\'s length transactions',
            'Other non-deductible expenses',
        ];
        $lessCategories = [
            'Exempt dividends (qualifying)',
            'Qualifying group transactions',
            'Foreign-sourced income (exempt)',
            'Tax losses carried forward',
            'Other exempt income',
        ];

        return view('ct.create', compact(
            'company', 'year', 'periodStart', 'periodEnd',
            'revenue', 'netProfit', 'addCategories', 'lessCategories'
        ));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');
        $year      = (int) $request->year;

        $sbrElected    = $request->boolean('sbr_elected');
        $netProfit     = floatval($request->accounting_income);
        $foreignCredit = floatval($request->foreign_tax_credit ?? 0);

        // Build adjustments
        $additions   = 0;
        $deductions  = 0;
        $adjustments = [];

        foreach ($request->adj_type ?? [] as $i => $type) {
            $amt = floatval($request->adj_amount[$i] ?? 0);
            if (!$amt) continue;
            $cat  = $request->adj_category[$i] ?? '';
            $desc = $request->adj_description[$i] ?? '';
            if ($type === 'ADD') $additions  += $amt;
            else                 $deductions += $amt;
            $adjustments[] = compact('type', 'amt', 'cat', 'desc');
        }

        $taxableIncome = $sbrElected ? 0 : max(0, $netProfit + $additions - $deductions);
        $calc          = $this->calcCT($taxableIncome);
        $ctPayable     = max(0, $calc['tax'] - $foreignCredit);

        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->whereYear('start_date', $year)
            ->orderBy('start_date')->first();

        $returnId = DB::table('ct_returns')->insertGetId([
            'company_id'        => $companyId,
            'fiscal_year'       => $year,
            'period_start'      => $period->start_date ?? "{$year}-01-01",
            'period_end'        => $period->end_date   ?? "{$year}-12-31",
            'return_number'     => 'CT-' . $year . '-' . str_pad(
                DB::table('ct_returns')->where('company_id', $companyId)->count() + 1, 3, '0', STR_PAD_LEFT
            ),
            'accounting_basis'  => 'ACCRUAL',
            'total_revenue'     => floatval($request->total_revenue),
            'accounting_income' => $netProfit,
            'total_additions'   => $additions,
            'total_deductions'  => $deductions,
            'taxable_income'    => $taxableIncome,
            'income_at_zero_rate' => $calc['zero'],
            'income_at_nine_pct'  => $calc['nine'],
            'tax_at_nine_pct'     => $calc['tax'],
            'foreign_tax_credit'  => $foreignCredit,
            'ct_payable'          => $ctPayable,
            'sbr_elected'         => $sbrElected,
            'status'              => 'DRAFT',
            'notes'               => $request->notes,
            'created_by_id'       => auth()->id(),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        foreach ($adjustments as $adj) {
            DB::table('ct_adjustments')->insert([
                'ct_return_id' => $returnId,
                'company_id'   => $companyId,
                'type'         => $adj['type'],
                'category'     => $adj['cat'],
                'description'  => $adj['desc'],
                'amount'       => $adj['amt'],
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        return redirect("/ct/{$returnId}")->with('success', "CT Return for {$year} created.");
    }

    public function show($id)
    {
        $companyId = session('company_id');
        $return    = DB::table('ct_returns')->where('id', $id)->where('company_id', $companyId)->first();
        if (!$return) abort(404);

        $adjustments = DB::table('ct_adjustments')
            ->where('ct_return_id', $id)
            ->orderBy('type')->get();

        $company = DB::table('companies')->find($companyId);

        return view('ct.show', compact('return', 'adjustments', 'company'));
    }

    public function submit(Request $request, $id)
    {
        $companyId = session('company_id');
        DB::table('ct_returns')
            ->where('id', $id)->where('company_id', $companyId)
            ->update([
                'status'           => 'FILED',
                'filing_date'      => $request->filing_date ?? date('Y-m-d'),
                'filing_reference' => $request->filing_reference,
                'updated_at'       => now(),
            ]);

        return redirect("/ct/{$id}")->with('success', 'CT Return marked as filed.');
    }

    public function pay(Request $request, $id)
    {
        $companyId = session('company_id');
        DB::table('ct_returns')
            ->where('id', $id)->where('company_id', $companyId)
            ->update([
                'status'       => 'PAID',
                'amount_paid'  => $request->amount_paid,
                'payment_date' => $request->payment_date ?? date('Y-m-d'),
                'updated_at'   => now(),
            ]);

        return redirect("/ct/{$id}")->with('success', 'Payment recorded.');
    }

    // ─── SBR elections page ────────────────────────────────────────────────────
    public function sbr()
    {
        $companyId = session('company_id');
        $company   = DB::table('companies')->find($companyId);

        $years = [];
        foreach (range(2024, 2026) as $year) {
            $revenue  = $this->getRevenue($companyId, $year);
            $ct       = DB::table('ct_returns')
                ->where('company_id', $companyId)->where('fiscal_year', $year)->first();
            $years[] = [
                'year'       => $year,
                'revenue'    => $revenue,
                'eligible'   => $revenue > 0 && $revenue <= self::SBR_REVENUE_LIMIT,
                'elected'    => $ct?->sbr_elected ?? false,
                'ctStatus'   => $ct?->status ?? null,
                'ctId'       => $ct?->id ?? null,
            ];
        }

        return view('ct.sbr', compact('company', 'years'));
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────
    private function getRevenue(int $companyId, int $year): float
    {
        $result = DB::selectOne("
            SELECT COALESCE(SUM(jl.credit_amount - jl.debit_amount), 0) AS revenue
            FROM journal_lines jl
            JOIN journal_entries je ON je.id = jl.journal_entry_id
            JOIN accounts a ON a.id = jl.account_id
            WHERE je.company_id = ?
              AND je.status = 'POSTED'
              AND a.account_type = 'REVENUE'
              AND EXTRACT(YEAR FROM je.entry_date) = ?
        ", [$companyId, $year]);
        return (float) ($result->revenue ?? 0);
    }

    private function getNetProfit(int $companyId, int $year): float
    {
        $result = DB::selectOne("
            SELECT
                COALESCE(SUM(CASE WHEN a.account_type = 'REVENUE' THEN jl.credit_amount - jl.debit_amount ELSE 0 END), 0)
              - COALESCE(SUM(CASE WHEN a.account_type = 'EXPENSE' THEN jl.debit_amount - jl.credit_amount ELSE 0 END), 0)
              AS net_profit
            FROM journal_lines jl
            JOIN journal_entries je ON je.id = jl.journal_entry_id
            JOIN accounts a ON a.id = jl.account_id
            WHERE je.company_id = ?
              AND je.status = 'POSTED'
              AND je.journal_type != 'CLOSING_ENTRY'
              AND EXTRACT(YEAR FROM je.entry_date) = ?
        ", [$companyId, $year]);
        return (float) ($result->net_profit ?? 0);
    }

    private function calcCT(float $taxableIncome): array
    {
        if ($taxableIncome <= 0) {
            return ['zero' => 0, 'nine' => 0, 'tax' => 0];
        }
        $zero = min($taxableIncome, self::THRESHOLD);
        $nine = max(0, $taxableIncome - self::THRESHOLD);
        return [
            'zero' => $zero,
            'nine' => $nine,
            'tax'  => round($nine * self::CT_RATE, 2),
        ];
    }
}
