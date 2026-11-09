<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BankReconciliationController extends Controller
{
    public function index()
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $reconciliations = DB::table('bank_reconciliations as br')
            ->join('accounts as a', 'a.id', '=', 'br.account_id')
            ->where('br.company_id', $companyId)
            ->where('br.is_deleted', false)
            ->select(
                'br.*',
                'a.name as account_name',
                DB::raw("(SELECT COUNT(*) FROM bank_statement_lines bsl WHERE bsl.reconciliation_id = br.id) as total_lines"),
                DB::raw("(SELECT COUNT(*) FROM bank_statement_lines bsl WHERE bsl.reconciliation_id = br.id AND bsl.matched = true) as matched_lines")
            )
            ->orderByDesc('br.period_to')
            ->get();

        $bankAccounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where(function ($q) {
                $q->where('is_bank', true)->orWhere('is_cash', true);
            })
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get();

        return view('reconciliation.index', compact('reconciliations', 'bankAccounts'));
    }

    public function create()
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $bankAccounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where(function ($q) {
                $q->where('is_bank', true)->orWhere('is_cash', true);
            })
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get();

        return view('reconciliation.create', compact('bankAccounts'));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');
        $request->validate([
            'account_id'                => 'required|integer',
            'period_from'               => 'required|date',
            'period_to'                 => 'required|date|after_or_equal:period_from',
            'opening_balance'           => 'required|numeric',
            'closing_balance_statement' => 'required|numeric',
            'csv_file'                  => 'required|file|mimes:csv,txt',
        ]);

        $accountId = (int) $request->account_id;

        // Parse CSV
        $file   = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $headerRow = fgetcsv($handle);
        if (!$headerRow) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'Could not read CSV header row.']);
        }

        $cols = $this->detectColumns($headerRow);
        if ($cols['date'] === null) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'Could not detect a Date column in the CSV.']);
        }

        // Find journal lines already reconciled for this account (across all reconciliations)
        $alreadyMatchedJlIds = DB::table('bank_statement_lines as bsl')
            ->join('bank_reconciliations as br', 'br.id', '=', 'bsl.reconciliation_id')
            ->where('br.company_id', $companyId)
            ->where('br.account_id', $accountId)
            ->where('bsl.matched', true)
            ->whereNotNull('bsl.journal_line_id')
            ->pluck('bsl.journal_line_id')
            ->toArray();

        // Create reconciliation record
        $reconciliationId = DB::table('bank_reconciliations')->insertGetId([
            'company_id'                => $companyId,
            'account_id'                => $accountId,
            'period_from'               => $request->period_from,
            'period_to'                 => $request->period_to,
            'opening_balance'           => $request->opening_balance,
            'closing_balance_statement' => $request->closing_balance_statement,
            'status'                    => 'OPEN',
            'notes'                     => $request->notes,
            'created_by_id'             => auth()->user()->id,
            'is_deleted'                => false,
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);

        // Parse rows and auto-match
        $rows = [];
        $matchedThisImport = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) continue;

            $dateStr = isset($cols['date']) ? trim($row[$cols['date']] ?? '') : '';
            $date    = $this->parseDate($dateStr);
            if (!$date) continue;

            $desc  = isset($cols['desc'])  ? trim($row[$cols['desc']] ?? '')  : '';
            $ref   = isset($cols['ref'])   ? trim($row[$cols['ref']] ?? '')   : '';

            $debit  = 0.0;
            $credit = 0.0;

            if ($cols['debit'] !== null && $cols['credit'] !== null) {
                $debit  = $this->parseAmount($row[$cols['debit']]  ?? '0');
                $credit = $this->parseAmount($row[$cols['credit']] ?? '0');
            } elseif ($cols['amount'] !== null) {
                $amt = $this->parseAmount($row[$cols['amount']] ?? '0');
                if ($amt >= 0) $credit = $amt;
                else           $debit  = abs($amt);
            }

            if ($debit == 0 && $credit == 0) continue;

            $rows[] = compact('date', 'desc', 'ref', 'debit', 'credit');
        }
        fclose($handle);

        // Auto-match against journal lines
        foreach ($rows as &$r) {
            $stmtNet  = $r['credit'] - $r['debit'];
            $dateFrom = Carbon::parse($r['date'])->subDays(5)->toDateString();
            $dateTo   = Carbon::parse($r['date'])->addDays(5)->toDateString();

            $exclude = array_merge($alreadyMatchedJlIds, $matchedThisImport);

            $jlMatch = DB::table('journal_lines as jl')
                ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
                ->where('jl.account_id', $accountId)
                ->where('je.company_id', $companyId)
                ->where('je.status', 'POSTED')
                ->whereBetween('je.entry_date', [$dateFrom, $dateTo])
                ->whereRaw('(jl.debit_amount - jl.credit_amount) = ?', [$stmtNet])
                ->when(!empty($exclude), fn($q) => $q->whereNotIn('jl.id', $exclude))
                ->select('jl.id')
                ->first();

            if ($jlMatch) {
                $r['journal_line_id'] = $jlMatch->id;
                $r['matched']         = true;
                $matchedThisImport[]  = $jlMatch->id;
            } else {
                $r['journal_line_id'] = null;
                $r['matched']         = false;
            }
        }
        unset($r);

        // Bulk insert statement lines
        $inserts = array_map(fn($r) => [
            'reconciliation_id' => $reconciliationId,
            'company_id'        => $companyId,
            'transaction_date'  => $r['date'],
            'description'       => $r['desc'],
            'reference'         => $r['ref'],
            'debit_amount'      => $r['debit'],
            'credit_amount'     => $r['credit'],
            'matched'           => $r['matched'],
            'journal_line_id'   => $r['journal_line_id'],
            'created_at'        => now(),
            'updated_at'        => now(),
        ], $rows);

        if (!empty($inserts)) {
            foreach (array_chunk($inserts, 200) as $chunk) {
                DB::table('bank_statement_lines')->insert($chunk);
            }
        }

        $autoMatched = count(array_filter($rows, fn($r) => $r['matched']));
        $total       = count($rows);

        return redirect("/reconciliation/{$reconciliationId}")
            ->with('success', "Imported {$total} lines. Auto-matched {$autoMatched}.");
    }

    public function show($id)
    {
        $companyId = session('company_id');
        $recon = DB::table('bank_reconciliations')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->first();

        if (!$recon) abort(404);

        $account = DB::table('accounts')->find($recon->account_id);

        // Statement lines
        $stmtLines = DB::table('bank_statement_lines')
            ->where('reconciliation_id', $id)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        // IDs of journal lines already matched anywhere for this account
        $globalMatchedJlIds = DB::table('bank_statement_lines as bsl')
            ->join('bank_reconciliations as br', 'br.id', '=', 'bsl.reconciliation_id')
            ->where('br.company_id', $companyId)
            ->where('br.account_id', $recon->account_id)
            ->where('bsl.matched', true)
            ->whereNotNull('bsl.journal_line_id')
            ->pluck('bsl.journal_line_id')
            ->toArray();

        // Unmatched journal lines for the account within period ± 60 days
        $jlFrom = Carbon::parse($recon->period_from)->subDays(60)->toDateString();
        $jlTo   = Carbon::parse($recon->period_to)->addDays(30)->toDateString();

        $journalLines = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('jl.account_id', $recon->account_id)
            ->where('je.company_id', $companyId)
            ->where('je.status', 'POSTED')
            ->whereBetween('je.entry_date', [$jlFrom, $jlTo])
            ->when(!empty($globalMatchedJlIds), fn($q) => $q->whereNotIn('jl.id', $globalMatchedJlIds))
            ->select('jl.*', 'je.entry_date', 'je.entry_number', 'je.description as entry_desc')
            ->orderBy('je.entry_date')
            ->get();

        // Book balance = SUM(debit-credit) on all posted journal_lines for account up to period_to
        $bookBalance = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('jl.account_id', $recon->account_id)
            ->where('je.company_id', $companyId)
            ->where('je.status', 'POSTED')
            ->whereDate('je.entry_date', '<=', $recon->period_to)
            ->selectRaw('COALESCE(SUM(jl.debit_amount - jl.credit_amount), 0) as net')
            ->value('net');

        $bookBalance = (float)$bookBalance;
        $stmtBalance = (float)$recon->closing_balance_statement;
        $difference  = $stmtBalance - $bookBalance;

        return view('reconciliation.show', compact(
            'recon', 'account', 'stmtLines', 'journalLines',
            'bookBalance', 'stmtBalance', 'difference'
        ));
    }

    public function matchLines(Request $request, $id)
    {
        $companyId = session('company_id');
        $recon = DB::table('bank_reconciliations')
            ->where('id', $id)->where('company_id', $companyId)->where('is_deleted', false)->first();
        if (!$recon || $recon->status === 'LOCKED') abort(403);

        $stmtIds = array_filter(array_map('intval', (array)$request->input('stmt_ids', [])));
        $jlIds   = array_filter(array_map('intval', (array)$request->input('jl_ids', [])));

        if (empty($stmtIds) || empty($jlIds)) {
            return back()->withErrors(['match' => 'Select at least one item from each side.']);
        }

        // Validate amounts match
        $stmtNet = DB::table('bank_statement_lines')
            ->where('reconciliation_id', $id)
            ->whereIn('id', $stmtIds)
            ->selectRaw('COALESCE(SUM(credit_amount - debit_amount), 0) as net')
            ->value('net');

        $jlNet = DB::table('journal_lines')
            ->whereIn('id', $jlIds)
            ->selectRaw('COALESCE(SUM(debit_amount - credit_amount), 0) as net')
            ->value('net');

        if (abs((float)$stmtNet - (float)$jlNet) > 0.02) {
            return back()->withErrors(['match' => "Amounts don't balance: Statement AED " . number_format($stmtNet, 2) . " vs Books AED " . number_format($jlNet, 2)]);
        }

        // Match the first stmt line to the first jl; multiple-to-one supported
        $primaryJlId = $jlIds[0];
        DB::table('bank_statement_lines')
            ->where('reconciliation_id', $id)
            ->whereIn('id', $stmtIds)
            ->update(['matched' => true, 'journal_line_id' => $primaryJlId, 'updated_at' => now()]);

        return redirect("/reconciliation/{$id}")->with('success', 'Lines matched successfully.');
    }

    public function unmatch(Request $request, $id, $stmtId)
    {
        $companyId = session('company_id');
        $recon = DB::table('bank_reconciliations')
            ->where('id', $id)->where('company_id', $companyId)->where('is_deleted', false)->first();
        if (!$recon || $recon->status === 'LOCKED') abort(403);

        DB::table('bank_statement_lines')
            ->where('id', $stmtId)
            ->where('reconciliation_id', $id)
            ->update(['matched' => false, 'journal_line_id' => null, 'updated_at' => now()]);

        return redirect("/reconciliation/{$id}")->with('success', 'Match removed.');
    }

    public function lock(Request $request, $id)
    {
        $companyId = session('company_id');
        $recon = DB::table('bank_reconciliations')
            ->where('id', $id)->where('company_id', $companyId)->where('is_deleted', false)->first();
        if (!$recon) abort(404);
        if ($recon->status === 'LOCKED') return back()->with('error', 'Already locked.');

        // Recalculate difference
        $bookBalance = (float) DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('jl.account_id', $recon->account_id)
            ->where('je.company_id', $companyId)
            ->where('je.status', 'POSTED')
            ->whereDate('je.entry_date', '<=', $recon->period_to)
            ->selectRaw('COALESCE(SUM(jl.debit_amount - jl.credit_amount), 0) as net')
            ->value('net');

        $diff = abs((float)$recon->closing_balance_statement - $bookBalance);
        if ($diff > 1.00) {
            return back()->with('error', "Cannot lock — difference of AED " . number_format($diff, 2) . " remains. Resolve all unmatched items first.");
        }

        DB::table('bank_reconciliations')->where('id', $id)->update([
            'status'    => 'LOCKED',
            'locked_at' => now(),
            'locked_by_id' => auth()->user()->id,
            'updated_at'   => now(),
        ]);

        return redirect("/reconciliation")->with('success', 'Reconciliation locked successfully.');
    }

    public function createEntry(Request $request, $id)
    {
        $companyId = session('company_id');
        $recon = DB::table('bank_reconciliations')
            ->where('id', $id)->where('company_id', $companyId)->where('is_deleted', false)->first();
        if (!$recon || $recon->status === 'LOCKED') abort(403);

        $stmtId = (int)$request->stmt_id;
        $stmtLine = DB::table('bank_statement_lines')
            ->where('id', $stmtId)->where('reconciliation_id', $id)->first();
        if (!$stmtLine) abort(404);

        $debitAccountId  = (int)$request->debit_account_id;
        $creditAccountId = (int)$request->credit_account_id;
        $description     = $request->description ?? $stmtLine->description;
        $entryDate       = $request->entry_date ?? $stmtLine->transaction_date;
        $amount          = max((float)$stmtLine->debit_amount, (float)$stmtLine->credit_amount);

        $year   = date('Y', strtotime($entryDate));
        $count  = DB::table('journal_entries')->where('company_id', $companyId)->whereYear('entry_date', $year)->count() + 1;
        $jeNum  = 'JE-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->whereDate('start_date', '<=', $entryDate)
            ->whereDate('end_date', '>=', $entryDate)
            ->first();

        $jeId = DB::table('journal_entries')->insertGetId([
            'company_id'    => $companyId,
            'entry_number'  => $jeNum,
            'entry_date'    => $entryDate,
            'status'        => 'POSTED',
            'journal_type'  => 'BANK_ADJUSTMENT',
            'description'   => $description,
            'currency_code' => 'AED',
            'exchange_rate' => 1.0,
            'total_debit'   => $amount,
            'total_credit'  => $amount,
            'period_id'     => $period?->id,
            'is_deleted'    => false,
            'created_by_id' => auth()->user()->id,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        DB::table('journal_lines')->insert([
            ['journal_entry_id' => $jeId, 'company_id' => $companyId, 'account_id' => $debitAccountId,  'line_number' => 1, 'description' => $description, 'debit_amount' => $amount, 'credit_amount' => 0],
            ['journal_entry_id' => $jeId, 'company_id' => $companyId, 'account_id' => $creditAccountId, 'line_number' => 2, 'description' => $description, 'debit_amount' => 0, 'credit_amount' => $amount],
        ]);

        // Auto-match the statement line to the new journal line
        $newJlId = DB::table('journal_lines')
            ->where('journal_entry_id', $jeId)
            ->where('account_id', $recon->account_id)
            ->value('id');

        if ($newJlId) {
            DB::table('bank_statement_lines')
                ->where('id', $stmtId)
                ->update(['matched' => true, 'journal_line_id' => $newJlId, 'updated_at' => now()]);
        }

        return redirect("/reconciliation/{$id}")->with('success', "Journal entry {$jeNum} created and line matched.");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function detectColumns(array $headers): array
    {
        $lower = array_map(fn($h) => strtolower(trim((string)$h)), $headers);

        $find = function (array $patterns) use ($lower): ?int {
            foreach ($patterns as $p) {
                foreach ($lower as $i => $h) {
                    if (str_contains($h, $p)) return $i;
                }
            }
            return null;
        };

        return [
            'date'   => $find(['date', 'trans date', 'value date', 'posting date']),
            'desc'   => $find(['description', 'narration', 'details', 'particular', 'remark', 'transaction detail']),
            'ref'    => $find(['ref', 'cheque', 'chq', 'transaction id', 'txn id', 'voucher']),
            'debit'  => $find(['debit', 'dr', 'withdrawal', 'paid out', 'debit amount']),
            'credit' => $find(['credit', 'cr', 'deposit', 'paid in', 'credit amount']),
            'amount' => $find(['amount', 'amt', 'net']),
        ];
    }

    private function parseDate(string $val): ?string
    {
        $val = trim($val);
        if (!$val) return null;
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'd M Y', 'd-M-Y', 'm/d/Y', 'd/m/y', 'Y/m/d'];
        foreach ($formats as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $val)->format('Y-m-d');
            } catch (\Exception $e) {}
        }
        try {
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Exception $e) {}
        return null;
    }

    private function parseAmount(string $val): float
    {
        $cleaned = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', trim($val)));
        return $cleaned === '' ? 0.0 : (float)$cleaned;
    }
}
