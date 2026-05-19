<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $dateFrom = $request->get('date_from', date('Y-01-01'));
        $dateTo   = $request->get('date_to',   date('Y-m-d'));
        $status   = $request->get('status', 'all');

        $query = DB::table('bills')
            ->join('vendors', 'vendors.id', '=', 'bills.vendor_id')
            ->where('bills.company_id', $companyId)
            ->where('bills.is_deleted', false)
            ->whereBetween('bills.bill_date', [$dateFrom, $dateTo])
            ->select('bills.*', 'vendors.name as vendor_name')
            ->orderBy('bills.bill_date', 'desc');

        if ($status !== 'all') {
            $query->where('bills.status', strtoupper($status));
        }

        $bills       = $query->get();
        $totalAmount = $bills->sum('total_amount');
        $totalDue    = $bills->sum('amount_due');

        return view('bills.index', compact('bills', 'dateFrom', 'dateTo', 'status', 'totalAmount', 'totalDue'));
    }

    public function create(Request $request)
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $vendors = DB::table('vendors')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get();

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByRaw("CASE account_type WHEN 'ASSET' THEN 1 WHEN 'LIABILITY' THEN 2 WHEN 'EQUITY' THEN 3 WHEN 'REVENUE' THEN 4 WHEN 'EXPENSE' THEN 5 ELSE 6 END")
            ->orderBy('code')
            ->get()
            ->groupBy('account_type');

        $selectedVendor = null;
        if ($request->get('vendor_id')) {
            $selectedVendor = DB::table('vendors')->find($request->get('vendor_id'));
        }

        return view('bills.create', compact('vendors', 'accounts', 'selectedVendor'));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');
        $request->validate([
            'vendor_id' => 'required|integer',
            'bill_date' => 'required|date',
        ]);

        $vendor = DB::table('vendors')->find($request->vendor_id);

        $subtotal = 0;
        $totalVat = 0;
        $lines    = [];

        foreach ($request->accounts ?? [] as $i => $accountId) {
            if (!$accountId) continue;
            $qty        = floatval($request->quantities[$i] ?? 1);
            $price      = floatval($request->unit_prices[$i] ?? 0);
            $vatRate    = floatval($request->vat_rates[$i] ?? 0);
            $desc       = $request->descriptions[$i] ?? '';
            $lineAmount = $qty * $price;
            $vatAmount  = $lineAmount * ($vatRate / 100);
            $subtotal  += $lineAmount;
            $totalVat  += $vatAmount;
            $lines[]    = [
                'account_id'   => $accountId,
                'description'  => $desc,
                'quantity'     => $qty,
                'unit_price'   => $price,
                'line_amount'  => $lineAmount,
                'vat_rate'     => $vatRate,
                'vat_amount'   => $vatAmount,
                'total_amount' => $lineAmount + $vatAmount,
            ];
        }

        $totalAmount = $subtotal + $totalVat;
        $dueDate     = $request->due_date ?? date('Y-m-d', strtotime('+' . ($vendor->payment_terms_days ?? 30) . ' days', strtotime($request->bill_date)));
        $count       = DB::table('bills')->where('company_id', $companyId)->count() + 1;
        $billNumber  = 'BILL-' . date('Y') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $request->bill_date)
            ->where('end_date', '>=', $request->bill_date)
            ->first();

        if (!$period) {
            return redirect()->back()->withInput()->with('error', 'No accounting period found for the bill date. Please create a fiscal year and periods first.');
        }

        try {
            DB::transaction(function () use (
                $companyId, $request, $vendor, $lines, $subtotal, $totalVat,
                $totalAmount, $dueDate, $billNumber, $period
            ) {
                $billId = DB::table('bills')->insertGetId([
                    'company_id'        => $companyId,
                    'vendor_id'         => $request->vendor_id,
                    'period_id'         => $period->id,
                    'bill_number'       => $billNumber,
                    'vendor_ref'        => $request->vendor_ref,
                    'bill_type'         => 'TAX_INVOICE',
                    'bill_date'         => $request->bill_date,
                    'due_date'          => $dueDate,
                    'status'            => 'DRAFT',
                    'currency_code'     => 'AED',
                    'exchange_rate'     => 1.0,
                    'vendor_name'       => $vendor->name,
                    'vendor_trn'        => $vendor->trn,
                    'subtotal'          => $subtotal,
                    'discount_amount'   => 0,
                    'total_vat_amount'  => $totalVat,
                    'total_amount'      => $totalAmount,
                    'amount_paid'       => 0,
                    'amount_due'        => $totalAmount,
                    'is_reverse_charge' => false,
                    'reverse_charge_vat'=> 0,
                    'notes'             => $request->notes,
                    'is_deleted'        => false,
                    'created_by_id'     => auth()->user()->id,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                foreach ($lines as $i => $line) {
                    DB::table('bill_lines')->insert([
                        'bill_id'           => $billId,
                        'company_id'        => $companyId,
                        'account_id'        => $line['account_id'],
                        'line_number'       => $i + 1,
                        'description'       => $line['description'],
                        'quantity'          => $line['quantity'],
                        'unit_price'        => $line['unit_price'],
                        'line_amount'       => $line['line_amount'],
                        'vat_rate'          => $line['vat_rate'],
                        'vat_amount'        => $line['vat_amount'],
                        'total_amount'      => $line['total_amount'],
                        'is_reverse_charge' => false,
                    ]);
                }
            });

            return redirect('/bills')->with('success', "Bill {$billNumber} created successfully.");

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create bill: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $companyId = session('company_id');
        $bill      = DB::table('bills')
            ->join('vendors', 'vendors.id', '=', 'bills.vendor_id')
            ->where('bills.id', $id)
            ->where('bills.company_id', $companyId)
            ->select('bills.*', 'vendors.name as vendor_name_full', 'vendors.email as vendor_email')
            ->first();

        if (!$bill) abort(404);

        $lines = DB::table('bill_lines')
            ->leftJoin('accounts', 'accounts.id', '=', 'bill_lines.account_id')
            ->where('bill_lines.bill_id', $id)
            ->select('bill_lines.*', 'accounts.code', 'accounts.name as account_name')
            ->orderBy('bill_lines.line_number')
            ->get();

        return view('bills.show', compact('bill', 'lines'));
    }

    public function approve($id)
    {
        $companyId = session('company_id');

        $bill = DB::table('bills')
            ->join('vendors', 'vendors.id', '=', 'bills.vendor_id')
            ->where('bills.id', $id)
            ->where('bills.company_id', $companyId)
            ->select('bills.*', 'vendors.ap_account_id')
            ->first();

        if (!$bill) abort(404);

        $lines = DB::table('bill_lines')->where('bill_id', $id)->get();

        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $bill->bill_date)
            ->where('end_date', '>=', $bill->bill_date)
            ->first();

        if (!$period) {
            return redirect("/bills/{$id}")->with('error', 'No accounting period found for this bill date. Please create a fiscal year and periods first.');
        }

        // Resolve AP account — vendor specific or company default
        $apAccountId = $bill->ap_account_id;
        if (!$apAccountId) {
            $apAccountId = DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('is_payable', true)
                ->value('id');
        }

        if (!$apAccountId) {
            return redirect("/bills/{$id}")->with('error', 'No Accounts Payable account found. Please create one in the Chart of Accounts and mark it as Payable.');
        }

        try {
            DB::transaction(function () use ($companyId, $bill, $lines, $period, $id, $apAccountId) {
                $jeYear   = date('Y', strtotime($bill->bill_date));
                $count    = DB::table('journal_entries')->where('company_id', $companyId)->whereYear('entry_date', $jeYear)->count() + 1;
                $jeNumber = 'JE-' . $jeYear . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

                $jeId = DB::table('journal_entries')->insertGetId([
                    'company_id'    => $companyId,
                    'period_id'     => $period->id,
                    'entry_number'  => $jeNumber,
                    'entry_date'    => $bill->bill_date,
                    'journal_type'  => 'GENERAL',
                    'status'        => 'POSTED',
                    'description'   => "Bill {$bill->bill_number} — {$bill->vendor_name}",
                    'reference'     => $bill->bill_number,
                    'total_debit'   => $bill->total_amount,
                    'total_credit'  => $bill->total_amount,
                    'currency_code' => 'AED',
                    'exchange_rate' => 1.0,
                    'is_reversal'   => false,
                    'is_recurring'  => false,
                    'is_deleted'    => false,
                    'created_by_id' => auth()->user()->id,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                $lineNum = 1;

                // DR Expense accounts per line
                foreach ($lines as $line) {
                    if (!$line->account_id) continue;
                    DB::table('journal_lines')->insert([
                        'journal_entry_id' => $jeId,
                        'company_id'       => $companyId,
                        'account_id'       => $line->account_id,
                        'line_number'      => $lineNum++,
                        'description'      => $line->description,
                        'debit_amount'     => $line->line_amount,
                        'credit_amount'    => 0,
                        'currency_code'    => 'AED',
                        'exchange_rate'    => 1.0,
                        'is_reconciled'    => false,
                    ]);
                }

                // DR VAT receivable if applicable
                if ($bill->total_vat_amount > 0) {
                    $vatAccount = DB::table('accounts')
                        ->where('company_id', $companyId)
                        ->where('account_type', 'ASSET')
                        ->where(function($q) {
                            $q->where('name', 'like', '%VAT%')
                              ->orWhere('name', 'like', '%Tax%');
                        })
                        ->first();

                    if ($vatAccount) {
                        DB::table('journal_lines')->insert([
                            'journal_entry_id' => $jeId,
                            'company_id'       => $companyId,
                            'account_id'       => $vatAccount->id,
                            'line_number'      => $lineNum++,
                            'description'      => 'Input VAT',
                            'debit_amount'     => $bill->total_vat_amount,
                            'credit_amount'    => 0,
                            'currency_code'    => 'AED',
                            'exchange_rate'    => 1.0,
                            'is_reconciled'    => false,
                        ]);
                    }
                }

                // CR Accounts Payable
                DB::table('journal_lines')->insert([
                    'journal_entry_id' => $jeId,
                    'company_id'       => $companyId,
                    'account_id'       => $apAccountId,
                    'line_number'      => $lineNum++,
                    'description'      => "AP — {$bill->vendor_name}",
                    'debit_amount'     => 0,
                    'credit_amount'    => $bill->total_amount,
                    'currency_code'    => 'AED',
                    'exchange_rate'    => 1.0,
                    'is_reconciled'    => false,
                ]);

                DB::table('bills')->where('id', $id)->update([
                    'status'           => 'APPROVED',
                    'journal_entry_id' => $jeId,
                    'updated_at'       => now(),
                ]);
            });

            return redirect("/bills/{$id}")->with('success', 'Bill approved and journal entry created.');

        } catch (\Exception $e) {
            return redirect("/bills/{$id}")->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    public function void($id)
    {
        if (!auth()->user()->is_super_admin) abort(403, 'Only super admins can void bills.');
        $companyId = session('company_id');
        DB::table('bills')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->update(['status' => 'VOID', 'is_deleted' => true, 'updated_at' => now()]);

        return redirect('/bills')->with('success', 'Bill voided.');
    }

    public function paymentForm($id)
    {
        $companyId = session('company_id');
        $bill      = DB::table('bills')->where('id', $id)->where('company_id', $companyId)->first();
        if (!$bill) abort(404);

        $bankAccounts = DB::table('bank_accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->get();

        $cashAccounts = DB::table('accounts')
            ->where('company_id', (int)$companyId)
            ->whereRaw("name ILIKE '%cash%'")
            ->get();

        return view('bills.payment', compact('bill', 'bankAccounts', 'cashAccounts'));
    }

    public function recordPayment(Request $request, $id)
    {
        $companyId = session('company_id');
        $request->validate([
            'payment_date'  => 'required|date',
            'amount'        => 'required|numeric|min:0.01',
            'gl_account_id' => 'required|integer',
        ]);

        $bill = DB::table('bills')
            ->join('vendors', 'vendors.id', '=', 'bills.vendor_id')
            ->where('bills.id', $id)
            ->where('bills.company_id', $companyId)
            ->select('bills.*', 'vendors.ap_account_id')
            ->first();

        if (!$bill) abort(404);

        $amount = floatval($request->amount);

        // Resolve AP account
        $apAccountId = $bill->ap_account_id;
        if (!$apAccountId) {
            $apAccountId = DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('is_payable', true)
                ->value('id');
        }

        if (!$apAccountId) {
            return redirect("/bills/{$id}")->with('error', 'No Accounts Payable account found. Please create one in the Chart of Accounts.');
        }

        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $request->payment_date)
            ->where('end_date', '>=', $request->payment_date)
            ->first();

        if (!$period) {
            return redirect("/bills/{$id}")->with('error', 'No accounting period found for the payment date.');
        }

        try {
            DB::transaction(function () use ($companyId, $bill, $amount, $request, $period, $id, $apAccountId) {
                $jeYear   = date('Y', strtotime($request->payment_date));
                $count    = DB::table('journal_entries')->where('company_id', $companyId)->whereYear('entry_date', $jeYear)->count() + 1;
                $jeNumber = 'JE-' . $jeYear . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

                $jeId = DB::table('journal_entries')->insertGetId([
                    'company_id'    => $companyId,
                    'period_id'     => $period->id,
                    'entry_number'  => $jeNumber,
                    'entry_date'    => $request->payment_date,
                    'journal_type'  => 'BANK_PAYMENT',
                    'status'        => 'POSTED',
                    'description'   => "Payment to {$bill->vendor_name} — {$bill->bill_number}",
                    'reference'     => $bill->bill_number,
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

                // DR AP
                DB::table('journal_lines')->insert([
                    'journal_entry_id' => $jeId,
                    'company_id'       => $companyId,
                    'account_id'       => $apAccountId,
                    'line_number'      => 1,
                    'description'      => "AP cleared — {$bill->vendor_name}",
                    'debit_amount'     => $amount,
                    'credit_amount'    => 0,
                    'currency_code'    => 'AED',
                    'exchange_rate'    => 1.0,
                    'is_reconciled'    => false,
                ]);

                // CR Bank/Cash
                DB::table('journal_lines')->insert([
                    'journal_entry_id' => $jeId,
                    'company_id'       => $companyId,
                    'account_id'       => $request->gl_account_id,
                    'line_number'      => 2,
                    'description'      => "Payment to {$bill->vendor_name}",
                    'debit_amount'     => 0,
                    'credit_amount'    => $amount,
                    'currency_code'    => 'AED',
                    'exchange_rate'    => 1.0,
                    'is_reconciled'    => false,
                ]);

                $newPaid = $bill->amount_paid + $amount;
                $newDue  = $bill->total_amount - $newPaid;
                $status  = $newDue <= 0 ? 'PAID' : 'PARTIAL';

                DB::table('bills')->where('id', $id)->update([
                    'amount_paid' => $newPaid,
                    'amount_due'  => max(0, $newDue),
                    'status'      => $status,
                    'updated_at'  => now(),
                ]);
            });

            return redirect("/bills/{$id}")->with('success', 'Payment recorded successfully.');

        } catch (\Exception $e) {
            return redirect("/bills/{$id}")->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $companyId = session('company_id');
        $bill      = DB::table('bills')->where('id', $id)->where('company_id', $companyId)->first();
        if (!$bill) abort(404);
        if ($bill->status !== 'DRAFT' && !auth()->user()->is_super_admin) {
            return redirect("/bills/{$id}")->with('error', 'Only draft bills can be edited.');
        }

        $vendors = DB::table('vendors')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get();

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByRaw("CASE account_type WHEN 'ASSET' THEN 1 WHEN 'LIABILITY' THEN 2 WHEN 'EQUITY' THEN 3 WHEN 'REVENUE' THEN 4 WHEN 'EXPENSE' THEN 5 ELSE 6 END")
            ->orderBy('code')
            ->get()
            ->groupBy('account_type');

        $lines = DB::table('bill_lines')
            ->where('bill_id', $id)
            ->orderBy('line_number')
            ->get();

        return view('bills.edit', compact('bill', 'vendors', 'accounts', 'lines'));
    }

    public function update(Request $request, $id)
    {
        $companyId = session('company_id');
        $bill      = DB::table('bills')->where('id', $id)->where('company_id', $companyId)->first();
        if (!$bill) abort(404);
        if ($bill->status !== 'DRAFT' && !auth()->user()->is_super_admin) {
            return redirect("/bills/{$id}")->with('error', 'Only draft bills can be edited.');
        }

        $vendor   = DB::table('vendors')->find($request->vendor_id);
        $subtotal = 0;
        $totalVat = 0;
        $lines    = [];

        foreach ($request->accounts ?? [] as $i => $accountId) {
            if (!$accountId) continue;
            $qty        = floatval($request->quantities[$i] ?? 1);
            $price      = floatval($request->unit_prices[$i] ?? 0);
            $vatRate    = floatval($request->vat_rates[$i] ?? 0);
            $desc       = $request->descriptions[$i] ?? '';
            $lineAmount = $qty * $price;
            $vatAmount  = $lineAmount * ($vatRate / 100);
            $subtotal  += $lineAmount;
            $totalVat  += $vatAmount;
            $lines[]    = [
                'account_id'   => $accountId,
                'description'  => $desc,
                'quantity'     => $qty,
                'unit_price'   => $price,
                'line_amount'  => $lineAmount,
                'vat_rate'     => $vatRate,
                'vat_amount'   => $vatAmount,
                'total_amount' => $lineAmount + $vatAmount,
            ];
        }

        $totalAmount = $subtotal + $totalVat;
        $dueDate     = $request->due_date ?? date('Y-m-d', strtotime('+' . ($vendor->payment_terms_days ?? 30) . ' days', strtotime($request->bill_date)));

        try {
            DB::transaction(function () use ($companyId, $id, $request, $vendor, $lines, $subtotal, $totalVat, $totalAmount, $dueDate) {
                DB::table('bills')->where('id', $id)->update([
                    'vendor_id'         => $request->vendor_id,
                    'vendor_name'       => $vendor->name,
                    'vendor_trn'        => $vendor->trn,
                    'vendor_ref'        => $request->vendor_ref,
                    'bill_date'         => $request->bill_date,
                    'due_date'          => $dueDate,
                    'subtotal'          => $subtotal,
                    'discount_amount'   => 0,
                    'total_vat_amount'  => $totalVat,
                    'total_amount'      => $totalAmount,
                    'amount_due'        => $totalAmount,
                    'notes'             => $request->notes,
                    'updated_at'        => now(),
                ]);

                DB::table('bill_lines')->where('bill_id', $id)->delete();

                foreach ($lines as $i => $line) {
                    DB::table('bill_lines')->insert([
                        'bill_id'           => $id,
                        'company_id'        => $companyId,
                        'account_id'        => $line['account_id'],
                        'line_number'       => $i + 1,
                        'description'       => $line['description'],
                        'quantity'          => $line['quantity'],
                        'unit_price'        => $line['unit_price'],
                        'line_amount'       => $line['line_amount'],
                        'vat_rate'          => $line['vat_rate'],
                        'vat_amount'        => $line['vat_amount'],
                        'total_amount'      => $line['total_amount'],
                        'is_reverse_charge' => false,
                    ]);
                }
            });

            return redirect("/bills/{$id}")->with('success', 'Bill updated.');

        } catch (\Exception $e) {
            return redirect("/bills/{$id}")->with('error', 'Update failed: ' . $e->getMessage());
        }
    }
}
