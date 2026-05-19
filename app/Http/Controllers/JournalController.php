<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class JournalController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $dateFrom    = $request->get('date_from', date('Y-01-01'));
        $dateTo      = $request->get('date_to',   date('Y-m-d'));
        $status      = $request->get('status', 'all');
        $accountId   = $request->get('account_id');
        $amountMin   = $request->get('amount_min');
        $amountMax   = $request->get('amount_max');
        $reference   = $request->get('reference');
        $journalType = $request->get('journal_type');

        $query = DB::table('journal_entries')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.is_deleted', false)
            ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo])
            ->orderBy('journal_entries.entry_date', 'desc')
            ->orderBy('journal_entries.id', 'desc');

        if ($status !== 'all') {
            $query->where('journal_entries.status', strtoupper($status));
        }

        if ($journalType) {
            $query->where('journal_entries.journal_type', strtoupper($journalType));
        }

        if ($reference) {
            $query->where(function($q) use ($reference) {
                $q->where('journal_entries.reference', 'ilike', "%{$reference}%")
                ->orWhere('journal_entries.description', 'ilike', "%{$reference}%")
                ->orWhere('journal_entries.entry_number', 'ilike', "%{$reference}%");
            });
        }

        if ($accountId) {
            $query->whereExists(function($q) use ($accountId) {
                $q->select(DB::raw(1))
                ->from('journal_lines')
                ->whereColumn('journal_lines.journal_entry_id', 'journal_entries.id')
                ->where('journal_lines.account_id', $accountId);
            });
        }

        if ($amountMin) {
            $query->where('journal_entries.total_debit', '>=', $amountMin);
        }

        if ($amountMax) {
            $query->where('journal_entries.total_debit', '<=', $amountMax);
        }

        $journals    = $query->get();
        $totalDebit  = $journals->sum('total_debit');
        $totalCredit = $journals->sum('total_credit');

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('journals.index', compact(
            'journals', 'dateFrom', 'dateTo', 'status',
            'totalDebit', 'totalCredit', 'accounts',
            'accountId', 'amountMin', 'amountMax', 'reference', 'journalType'
        ));
    }
    public function create()
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByRaw("CASE account_type
                WHEN 'ASSET' THEN 1
                WHEN 'LIABILITY' THEN 2
                WHEN 'EQUITY' THEN 3
                WHEN 'REVENUE' THEN 4
                WHEN 'EXPENSE' THEN 5
                ELSE 6 END")
            ->orderBy('code')
            ->get()
            ->groupBy('account_type');

        return view('journals.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');
        $request->validate([
            'entry_date'  => 'required|date',
            'description' => 'required|string',
            'accounts'    => 'required|array|min:2',
            'debits'      => 'required|array',
            'credits'     => 'required|array',
        ]);

        $lines = [];
        $totalDebit = $totalCredit = 0;

        foreach ($request->accounts as $i => $accountId) {
            if (!$accountId) continue;
            $debit  = floatval($request->debits[$i]  ?? 0);
            $credit = floatval($request->credits[$i] ?? 0);
            if ($debit == 0 && $credit == 0) continue;
            $totalDebit  += $debit;
            $totalCredit += $credit;
            $lines[] = [
                'account_id'   => $accountId,
                'debit_amount' => $debit,
                'credit_amount'=> $credit,
                'description'  => $request->line_descriptions[$i] ?? $request->description,
                'line_number'  => $i + 1,
            ];
        }

        if (abs($totalDebit - $totalCredit) >= 0.01) {
            return back()->withInput()->with('error', 'Entry is not balanced. Debits must equal credits.');
        }

        if (count($lines) < 2) {
            return back()->withInput()->with('error', 'At least 2 lines required.');
        }

        // Get fiscal year period
        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $request->entry_date)
            ->where('end_date', '>=', $request->entry_date)
            ->first();

        // Generate entry number
        $jeYear      = date('Y', strtotime($request->entry_date));
        $count       = DB::table('journal_entries')->where('company_id', $companyId)->whereYear('entry_date', $jeYear)->count() + 1;
        $entryNumber = 'JE-' . $jeYear . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

        $autoPost = $request->has('auto_post');
        $status   = $autoPost ? 'POSTED' : 'DRAFT';

        DB::transaction(function () use ($companyId, $request, $lines, $totalDebit, $totalCredit, $period, $entryNumber, $status) {
            $jeId = DB::table('journal_entries')->insertGetId([
                'company_id'    => $companyId,
                'period_id'     => $period?->id,
                'entry_number'  => $entryNumber,
                'entry_date'    => $request->entry_date,
                'journal_type'  => $request->journal_type ?? 'GENERAL',
                'status'        => $status,
                'description'   => $request->description,
                'reference'     => $request->reference ?: null,
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

            foreach ($lines as $line) {
                DB::table('journal_lines')->insert([
                    'journal_entry_id' => $jeId,
                    'company_id'       => $companyId,
                    'account_id'       => $line['account_id'],
                    'line_number'      => $line['line_number'],
                    'description'      => $line['description'],
                    'debit_amount'     => $line['debit_amount'],
                    'credit_amount'    => $line['credit_amount'],
                    'currency_code'    => 'AED',
                    'exchange_rate'    => 1.0,
                    'is_reconciled'    => false,
                ]);
            }
        });

        return redirect('/journals')->with('success', "Journal {$entryNumber} saved successfully.");
    }

    public function show($id)
    {
        $companyId = session('company_id');
        $journal = DB::table('journal_entries')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if (!$journal) abort(404);

        $lines = DB::table('journal_lines')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_lines.journal_entry_id', $id)
            ->select('journal_lines.*', 'accounts.code', 'accounts.name')
            ->orderByRaw('CASE WHEN journal_lines.debit_amount > 0 THEN 0 ELSE 1 END')
            ->orderBy('journal_lines.line_number')
            ->get();

        return view('journals.show', compact('journal', 'lines'));
    }

    public function post($id)
    {
        $companyId = session('company_id');
        DB::table('journal_entries')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->where('status', 'DRAFT')
            ->update(['status' => 'POSTED', 'updated_at' => now()]);

        return redirect("/journals/{$id}")->with('success', 'Journal entry posted successfully.');
    }

public function void($id)
{
    if (!auth()->user()->is_super_admin) abort(403, 'Only super admins can void journal entries.');
    $companyId = session('company_id');
    DB::table('journal_entries')
        ->where('id', $id)
        ->where('company_id', $companyId)
        ->update(['status' => 'VOID', 'is_deleted' => true, 'updated_at' => now()]);


    return redirect('/journals')->with('success', 'Journal entry voided.');
}
public function edit($id)
{
    $companyId = session('company_id');
    $journal   = DB::table('journal_entries')
        ->where('id', $id)
        ->where('company_id', $companyId)
        ->first();

    if (!$journal) abort(404);
    if ($journal->status !== 'DRAFT' && !auth()->user()->is_super_admin) return redirect("/journals/{$id}")->with('error', 'Only draft journal entries can be edited.');

    $accounts = DB::table('accounts')
        ->where('company_id', $companyId)
        ->where('is_active', true)
        ->orderByRaw("CASE account_type WHEN 'ASSET' THEN 1 WHEN 'LIABILITY' THEN 2 WHEN 'EQUITY' THEN 3 WHEN 'REVENUE' THEN 4 WHEN 'EXPENSE' THEN 5 ELSE 6 END")
        ->orderBy('code')
        ->get()
        ->groupBy('account_type');

    $lines = DB::table('journal_lines')
        ->where('journal_entry_id', $id)
        ->orderByRaw('CASE WHEN debit_amount > 0 THEN 0 ELSE 1 END')
        ->orderBy('line_number')
        ->get();

    return view('journals.edit', compact('journal', 'accounts', 'lines'));
}

public function update(Request $request, $id)
{
    $companyId = session('company_id');
    $journal   = DB::table('journal_entries')
        ->where('id', $id)
        ->where('company_id', $companyId)
        ->first();

    if (!$journal) abort(404);
    if ($journal->status !== 'DRAFT' && !auth()->user()->is_super_admin) return redirect("/journals/{$id}")->with('error', 'Only draft journal entries can be edited.');

    $debits  = array_map('floatval', $request->debits ?? []);
    $credits = array_map('floatval', $request->credits ?? []);
    $totalDebit  = array_sum($debits);
    $totalCredit = array_sum($credits);

    if (round($totalDebit, 2) !== round($totalCredit, 2)) {
        return back()->withInput()->with('error', 'Debits and credits must balance.');
    }

    DB::transaction(function () use ($companyId, $id, $request, $totalDebit, $totalCredit) {
        DB::table('journal_entries')->where('id', $id)->update([
            'entry_date'   => $request->entry_date,
            'description'  => $request->description,
            'reference'    => $request->reference,
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'updated_at'   => now(),
        ]);

        DB::table('journal_lines')->where('journal_entry_id', $id)->delete();

        foreach ($request->accounts ?? [] as $i => $accountId) {
            if (!$accountId) continue;
            $debit  = floatval($request->debits[$i] ?? 0);
            $credit = floatval($request->credits[$i] ?? 0);
            if ($debit == 0 && $credit == 0) continue;

            DB::table('journal_lines')->insert([
                'journal_entry_id' => $id,
                'company_id'       => $companyId,
                'account_id'       => $accountId,
                'line_number'      => $i + 1,
                'description'      => $request->line_descriptions[$i] ?? $request->description,
                'debit_amount'     => $debit,
                'credit_amount'    => $credit,
                'currency_code'    => 'AED',
                'exchange_rate'    => 1.0,
                'is_reconciled'    => false,
            ]);
        }
    });

    return redirect("/journals/{$id}")->with('success', 'Journal entry updated.');
}
}