<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AIController extends Controller
{
    public function index()
    {
        return view('ai.index');
    }

    public function query(Request $request)
    {
        $companyId   = session('company_id');
        $companyName = session('company_name', 'Company');

        $request->validate(['question' => 'required|string|max:500']);

        // Gather financial context
        $context = $this->buildContext($companyId, $companyName);

        // Call Claude API
        $response = $this->callClaude($request->question, $context);

        return response()->json(['answer' => $response]);
    }

    private function buildContext(int $companyId, string $companyName): string
    {
        // P&L this year
        $dateFrom = date('Y-01-01');
        $dateTo   = date('Y-m-d');

        $pnl = DB::select("
            SELECT a.account_type, a.name,
                COALESCE(SUM(jl.credit_amount),0) - COALESCE(SUM(jl.debit_amount),0) as balance
            FROM accounts a
            JOIN journal_lines jl ON jl.account_id = a.id AND jl.company_id = ?
            JOIN journal_entries je ON je.id = jl.journal_entry_id
                AND je.status = 'POSTED'
                AND je.journal_type != 'CLOSING_ENTRY'
                AND je.entry_date BETWEEN ? AND ?
            WHERE a.company_id = ? AND a.account_type IN ('REVENUE','EXPENSE')
            GROUP BY a.id, a.account_type, a.name
            ORDER BY a.account_type, a.name
        ", [$companyId, $dateFrom, $dateTo, $companyId]);

        $revenue  = collect($pnl)->where('account_type', 'REVENUE')->sum('balance');
        $expenses = collect($pnl)->where('account_type', 'EXPENSE')->sum(fn($r) => -$r->balance);
        $profit   = $revenue - $expenses;

        // Balance sheet
        $bs = DB::select("
            SELECT a.account_type,
                COALESCE(SUM(jl.debit_amount),0) as dr,
                COALESCE(SUM(jl.credit_amount),0) as cr
            FROM accounts a
            JOIN journal_lines jl ON jl.account_id = a.id AND jl.company_id = ?
            JOIN journal_entries je ON je.id = jl.journal_entry_id AND je.status = 'POSTED'
            WHERE a.company_id = ?
            GROUP BY a.account_type
        ", [$companyId, $companyId]);

        $bsData = collect($bs)->keyBy('account_type');
        $assets      = ($bsData['ASSET']->dr ?? 0) - ($bsData['ASSET']->cr ?? 0);
        $liabilities = ($bsData['LIABILITY']->cr ?? 0) - ($bsData['LIABILITY']->dr ?? 0);
        $equity      = ($bsData['EQUITY']->cr ?? 0) - ($bsData['EQUITY']->dr ?? 0);

        // AR/AP
        $arTotal = DB::table('invoices')->where('company_id', $companyId)->where('is_deleted', false)->where('amount_due', '>', 0)->sum('amount_due');
        $apTotal = DB::table('bills')->where('company_id', $companyId)->where('is_deleted', false)->where('amount_due', '>', 0)->sum('amount_due');

        // Journal count
        $journalCount = DB::table('journal_entries')->where('company_id', $companyId)->where('is_deleted', false)->count();

        // Recent entries
        $recent = DB::table('journal_entries')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->orderBy('entry_date', 'desc')
            ->limit(5)
            ->get(['entry_number', 'entry_date', 'description', 'total_debit', 'status']);

        $recentStr = $recent->map(fn($j) => "{$j->entry_date} | {$j->entry_number} | {$j->description} | AED {$j->total_debit}")->join("\n");

        return "
COMPANY: {$companyName}
REPORT DATE: {$dateTo}
CURRENCY: AED

PROFIT & LOSS (Year to date: {$dateFrom} to {$dateTo}):
- Total Revenue: AED " . number_format($revenue, 2) . "
- Total Expenses: AED " . number_format($expenses, 2) . "
- Net Profit/(Loss): AED " . number_format($profit, 2) . "

BALANCE SHEET (as of {$dateTo}):
- Total Assets: AED " . number_format($assets, 2) . "
- Total Liabilities: AED " . number_format($liabilities, 2) . "
- Total Equity: AED " . number_format($equity, 2) . "

RECEIVABLES & PAYABLES:
- Outstanding AR (amount owed to company): AED " . number_format($arTotal, 2) . "
- Outstanding AP (amount owed by company): AED " . number_format($apTotal, 2) . "

ACTIVITY:
- Total journal entries: {$journalCount}

RECENT TRANSACTIONS:
{$recentStr}
";
    }

    private function callClaude(string $question, string $context): string
    {
        $apiKey = config('services.anthropic.key');

        if (!$apiKey) {
            return 'AI is not configured. Please add ANTHROPIC_API_KEY to your .env file.';
        }

        $payload = [
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 1024,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => "You are a UAE-based accounting expert and financial analyst. You have access to the following financial data for a company:\n\n{$context}\n\nUser question: {$question}\n\nProvide a clear, concise, professional answer based on the data above. Format numbers with commas and 2 decimal places. Use AED currency. If the question cannot be answered from the available data, say so clearly.",
                ]
            ],
        ];

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', $payload);

        if ($response->successful()) {
            return $response->json('content.0.text', 'No response received.');
        }

        return 'Error calling AI: ' . $response->body();
    }
}