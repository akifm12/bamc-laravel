<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankAccountController extends Controller
{
    public function index()
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $accounts = DB::table('bank_accounts')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->orderBy('account_name')
            ->get();

        // Get current balance for each account from GL
        foreach ($accounts as $account) {
            if ($account->gl_account_id) {
                $balance = DB::table('journal_lines')
                    ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                    ->where('journal_lines.account_id', $account->gl_account_id)
                    ->where('journal_entries.status', 'POSTED')
                    ->selectRaw('COALESCE(SUM(debit_amount), 0) - COALESCE(SUM(credit_amount), 0) as balance')
                    ->value('balance');
                $account->current_balance = $balance ?? 0;
            } else {
                $account->current_balance = 0;
            }
        }

        return view('banking.index', compact('accounts'));
    }

    public function create()
    {
        $companyId = session('company_id');
        $glAccounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('account_type', 'ASSET')
            ->whereRaw("is_active = true")
            ->orderBy('code')
            ->get();

        return view('banking.create', compact('glAccounts'));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');
        $request->validate([
            'account_name' => 'required|string',
            'bank_name'    => 'required|string',
            'gl_account_id'=> 'required|integer',
        ]);

        DB::table('bank_accounts')->insert([
            'company_id'      => $companyId,
            'account_name'    => $request->account_name,
            'bank_name'       => $request->bank_name,
            'branch'          => $request->branch,
            'account_number'  => $request->account_number,
            'iban'            => $request->iban,
            'swift_code'      => $request->swift_code,
            'currency_code'   => $request->currency_code ?? 'AED',
            'gl_account_id'   => $request->gl_account_id,
            'opening_balance' => $request->opening_balance ?? 0,
            'opening_date'    => $request->opening_date ?? date('Y-m-d'),
            'is_active'       => true,
            'is_default'      => $request->has('is_default'),
            'is_deleted'      => false,
            'created_by_id'   => auth()->user()->id,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return redirect('/banking')->with('success', 'Bank account created.');
    }

    public function show(Request $request, $id)
    {
        $companyId = session('company_id');
        $account   = DB::table('bank_accounts')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if (!$account) abort(404);

        $dateFrom = $request->get('date_from', date('Y-01-01'));
        $dateTo   = $request->get('date_to',   date('Y-m-d'));

        // Get GL transactions for this bank account
        $transactions = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.account_id', $account->gl_account_id)
            ->where('journal_entries.status', 'POSTED')
            ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo])
            ->select(
                'journal_entries.entry_date as date',
                'journal_entries.entry_number',
                'journal_entries.description',
                'journal_entries.reference',
                'journal_lines.debit_amount',
                'journal_lines.credit_amount'
            )
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->get();

        // Running balance
        $runningBalance = $account->opening_balance ?? 0;
        foreach ($transactions as $txn) {
            $runningBalance += $txn->debit_amount - $txn->credit_amount;
            $txn->running_balance = $runningBalance;
        }

        $totalDebits  = $transactions->sum('debit_amount');
        $totalCredits = $transactions->sum('credit_amount');

        return view('banking.show', compact(
            'account', 'transactions', 'totalDebits', 'totalCredits',
            'runningBalance', 'dateFrom', 'dateTo'
        ));
    }

    public function addTransaction(Request $request, $id)
    {
        $companyId = session('company_id');
        $account   = DB::table('bank_accounts')->where('id', $id)->where('company_id', $companyId)->first();
        if (!$account) abort(404);

        $request->validate([
            'date'           => 'required|date',
            'description'    => 'required|string',
            'amount'         => 'required|numeric|min:0.01',
            'type'           => 'required|in:debit,credit',
            'contra_account' => 'required|integer',
        ]);

        $amount   = floatval($request->amount);
        $isDebit  = $request->type === 'debit';

        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $request->date)
            ->where('end_date', '>=', $request->date)
            ->first();

        if (!$period) {
            return back()->with('error', 'No accounting period found for ' . $request->date . '. Please create a fiscal year first.');
        }

        $count    = DB::table('journal_entries')->where('company_id', $companyId)->count() + 1;
        $jeNumber = 'JE-' . date('Y', strtotime($request->date)) . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($companyId, $request, $account, $amount, $isDebit, $period, $jeNumber) {
            $jeId = DB::table('journal_entries')->insertGetId([
                'company_id'    => $companyId,
                'period_id'     => $period->id,
                'entry_number'  => $jeNumber,
                'entry_date'    => $request->date,
                'journal_type'  => $isDebit ? 'BANK_RECEIPT' : 'BANK_PAYMENT',
                'status'        => 'POSTED',
                'description'   => $request->description,
                'reference'     => $request->reference,
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

            // Bank account line
            DB::table('journal_lines')->insert([
                'journal_entry_id' => $jeId,
                'company_id'       => $companyId,
                'account_id'       => $account->gl_account_id,
                'line_number'      => 1,
                'description'      => $request->description,
                'debit_amount'     => $isDebit ? $amount : 0,
                'credit_amount'    => $isDebit ? 0 : $amount,
                'currency_code'    => 'AED',
                'exchange_rate'    => 1.0,
                'is_reconciled'    => false,
            ]);

            // Contra account line
            DB::table('journal_lines')->insert([
                'journal_entry_id' => $jeId,
                'company_id'       => $companyId,
                'account_id'       => $request->contra_account,
                'line_number'      => 2,
                'description'      => $request->description,
                'debit_amount'     => $isDebit ? 0 : $amount,
                'credit_amount'    => $isDebit ? $amount : 0,
                'currency_code'    => 'AED',
                'exchange_rate'    => 1.0,
                'is_reconciled'    => false,
            ]);
        });

        return redirect("/banking/{$id}")->with('success', 'Transaction recorded.');
    }
}