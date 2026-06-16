<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
public function index()
{
    $companyId = session('company_id');
	\Log::info("ACCOUNTS INDEX", ['company_id' => $companyId, 'session' => session()->all()]);
    if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company first.');

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
        ->get();

    // Build hierarchy - parents first, children nested under them
    $allById   = $accounts->keyBy('id');
    $organized = [];

    foreach ($accounts as $account) {
        if (is_null($account->parent_id) || !$allById->has($account->parent_id)) {
            $account->depth = 0;
            $organized[] = $account;
            // Append children immediately after parent
            foreach ($accounts as $child) {
                if ($child->parent_id == $account->id) {
                    $child->depth = 1;
                    $organized[] = $child;
                    // Grandchildren
                    foreach ($accounts as $grandchild) {
                        if ($grandchild->parent_id == $child->id) {
                            $grandchild->depth = 2;
                            $organized[] = $grandchild;
                        }
                    }
                }
            }
        }
    }

    $grouped = collect($organized)->groupBy('account_type');
\Log::info("ACCOUNTS GROUPED", ['count' => $accounts->count(), 'organized' => count($organized), 'grouped_keys' => $grouped->keys()]);
    $typeTotals = [];
    foreach ($grouped as $type => $accts) {
        $typeTotals[$type] = $accts->count();
    }

    return view('accounts.index', compact('grouped', 'typeTotals'));
}

    public function create(Request $request)
    {
        $companyId = session('company_id');
        $accounts  = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        $parentId = $request->get('parent_id');
        return view('accounts.create', compact('accounts', 'parentId'));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');
        $request->validate([
            'code'           => 'required|string|max:20',
            'name'           => 'required|string|max:255',
            'account_type'   => 'required|in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE',
            'normal_balance' => 'required|in:DEBIT,CREDIT',
        ]);

        $exists = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('code', $request->code)
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['code' => 'Account code already exists for this company.']);
        }

        DB::table('accounts')->insert([
            'company_id'           => $companyId,
            'parent_id'            => $request->parent_id ?: null,
            'code'                 => strtoupper(trim($request->code)),
            'name'                 => trim($request->name),
            'name_arabic'          => $request->name_arabic ?: null,
            'description'          => $request->description ?: null,
            'account_type'         => $request->account_type,
            'account_sub_type'     => $request->account_sub_type ?: null,
            'normal_balance'       => $request->normal_balance,
            'is_control'           => $request->has('is_control'),
            'is_bank'              => $request->has('is_bank'),
            'is_cash'              => $request->has('is_cash'),
            'is_receivable'        => $request->has('is_receivable'),
            'is_payable'           => $request->has('is_payable'),
            'is_vat_account'       => $request->has('is_vat_account'),
            'currency_code'        => $request->currency_code ?: 'AED',
            'is_active'            => true,
            'notes'                => $request->notes ?: null,
            'opening_balance'      => $request->opening_balance ?: null,
            'opening_balance_date' => $request->opening_balance_date ?: null,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return redirect('/accounts')->with('success', 'Account created successfully.');
    }

    public function edit($id)
    {
        $companyId = session('company_id');
        $account   = DB::table('accounts')->where('id', $id)->where('company_id', $companyId)->first();
        if (!$account) abort(404);

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('id', '!=', $id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $parentId = $account->parent_id;

        return view('accounts.edit', compact('account', 'accounts', 'parentId'));
    }

    public function update(Request $request, $id)
    {
        $companyId = session('company_id');
        $request->validate([
            'code'           => 'required|string|max:20',
            'name'           => 'required|string|max:255',
            'account_type'   => 'required|in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE',
            'normal_balance' => 'required|in:DEBIT,CREDIT',
        ]);

        $exists = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('code', $request->code)
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['code' => 'Account code already exists for this company.']);
        }

        DB::table('accounts')->where('id', $id)->where('company_id', $companyId)->update([
            'parent_id'            => $request->parent_id ?: null,
            'code'                 => strtoupper(trim($request->code)),
            'name'                 => trim($request->name),
            'name_arabic'          => $request->name_arabic ?: null,
            'description'          => $request->description ?: null,
            'account_type'         => $request->account_type,
            'account_sub_type'     => $request->account_sub_type ?: null,
            'normal_balance'       => $request->normal_balance,
            'is_control'           => $request->has('is_control'),
            'is_bank'              => $request->has('is_bank'),
            'is_cash'              => $request->has('is_cash'),
            'is_receivable'        => $request->has('is_receivable'),
            'is_payable'           => $request->has('is_payable'),
            'is_vat_account'       => $request->has('is_vat_account'),
            'currency_code'        => $request->currency_code ?: 'AED',
            'is_active'            => $request->has('is_active'),
            'notes'                => $request->notes ?: null,
            'opening_balance'      => $request->opening_balance ?: null,
            'opening_balance_date' => $request->opening_balance_date ?: null,
            'updated_at'           => now(),
        ]);

        return redirect('/accounts')->with('success', 'Account updated successfully.');
    }

    public function destroy($id)
    {
        $companyId = session('company_id');

        $inUse = DB::table('journal_lines')->where('account_id', $id)->exists();
        if ($inUse) {
            return redirect('/accounts')->with('error', 'Cannot delete - this account has journal entries posted against it.');
        }

        $hasChildren = DB::table('accounts')->where('parent_id', $id)->exists();
        if ($hasChildren) {
            return redirect('/accounts')->with('error', 'Cannot delete - this account has sub-accounts. Delete or reassign them first.');
        }

        DB::table('accounts')->where('id', $id)->where('company_id', $companyId)->delete();
        return redirect('/accounts')->with('success', 'Account deleted.');
    }
}