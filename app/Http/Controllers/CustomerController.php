<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index()
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $customers = DB::table('customers')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get();

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        $companyId = session('company_id');
        $accounts  = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('account_type', 'ASSET')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $emirates = ['Abu Dhabi','Dubai','Sharjah','Ajman','Umm Al Quwain','Ras Al Khaimah','Fujairah'];
        return view('customers.create', compact('accounts', 'emirates'));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');
        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string',
        ]);

        // Generate code if not provided
        $code = $request->code ?: 'CUST-' . strtoupper(substr($request->name, 0, 4)) . '-' . rand(100, 999);

        DB::table('customers')->insert([
            'company_id'        => $companyId,
            'code'              => $code,
            'name'              => $request->name,
            'name_arabic'       => $request->name_arabic,
            'trn'               => $request->trn,
            'is_vat_registered' => $request->has('is_vat_registered'),
            'email'             => $request->email,
            'phone'             => $request->phone,
            'mobile'            => $request->mobile,
            'contact_person'    => $request->contact_person,
            'address_line1'     => $request->address_line1,
            'address_line2'     => $request->address_line2,
            'city'              => $request->city,
            'emirate'           => $request->emirate,
            'country'           => $request->country ?? 'UAE',
            'payment_terms_days'=> $request->payment_terms_days ?? 30,
            'credit_limit'      => $request->credit_limit ?? 0,
            'currency_code'     => 'AED',
            'ar_account_id'     => $request->ar_account_id,
            'is_active'         => true,
            'is_deleted'        => false,
            'blacklisted'       => false,
            'notes'             => $request->notes,
            'created_by_id'     => auth()->user()->id,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return redirect('/customers')->with('success', 'Customer created successfully.');
    }

    public function show($id)
    {
        $companyId = session('company_id');
        $customer  = DB::table('customers')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if (!$customer) abort(404);

        $invoices = DB::table('invoices')
            ->where('customer_id', $id)
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->orderBy('invoice_date', 'desc')
            ->get();

        $totalBilled = $invoices->sum('total_amount');
        $totalDue    = $invoices->sum('amount_due');

        return view('customers.show', compact('customer', 'invoices', 'totalBilled', 'totalDue'));
    }

    public function edit($id)
    {
        $companyId = session('company_id');
        $customer  = DB::table('customers')->where('id', $id)->where('company_id', $companyId)->first();
        if (!$customer) abort(404);

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('account_type', 'ASSET')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $emirates = ['Abu Dhabi','Dubai','Sharjah','Ajman','Umm Al Quwain','Ras Al Khaimah','Fujairah'];
        return view('customers.edit', compact('customer', 'accounts', 'emirates'));
    }

    public function update(Request $request, $id)
    {
        $companyId = session('company_id');
        DB::table('customers')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->update([
                'name'              => $request->name,
                'name_arabic'       => $request->name_arabic,
                'trn'               => $request->trn,
                'is_vat_registered' => $request->has('is_vat_registered'),
                'email'             => $request->email,
                'phone'             => $request->phone,
                'mobile'            => $request->mobile,
                'contact_person'    => $request->contact_person,
                'address_line1'     => $request->address_line1,
                'address_line2'     => $request->address_line2,
                'city'              => $request->city,
                'emirate'           => $request->emirate,
                'country'           => $request->country ?? 'UAE',
                'payment_terms_days'=> $request->payment_terms_days ?? 30,
                'credit_limit'      => $request->credit_limit ?? 0,
                'ar_account_id'     => $request->ar_account_id,
                'notes'             => $request->notes,
                'updated_by_id'     => auth()->user()->id,
                'updated_at'        => now(),
            ]);

        return redirect("/customers/{$id}")->with('success', 'Customer updated.');
    }
public function details($id)
{
    $companyId = session('company_id');
    $customer  = DB::table('customers')
        ->where('id', $id)
        ->where('company_id', $companyId)
        ->first();

    if (!$customer) return response()->json(['error' => 'Not found'], 404);

    // Find their revenue account
    $revenueAccount = DB::table('accounts')
        ->where('company_id', $companyId)
        ->where('account_type', 'REVENUE')
        ->where('name', $customer->name)
        ->first();

    return response()->json([
        'id'              => $customer->id,
        'name'            => $customer->name,
        'client_number'   => $customer->client_number,
        'client_acronym'  => $customer->client_acronym,
        'payment_terms'   => $customer->payment_terms_days,
        'revenue_account' => $revenueAccount ? [
            'id'   => $revenueAccount->id,
            'code' => $revenueAccount->code,
            'name' => $revenueAccount->name,
        ] : null,
    ]);
}
}