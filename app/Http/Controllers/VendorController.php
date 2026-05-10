<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    public function index()
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $vendors = DB::table('vendors')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get();

        return view('vendors.index', compact('vendors'));
    }

    public function create()
    {
        $companyId = session('company_id');
        $accounts  = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('account_type', 'LIABILITY')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $emirates = ['Abu Dhabi','Dubai','Sharjah','Ajman','Umm Al Quwain','Ras Al Khaimah','Fujairah'];
        return view('vendors.create', compact('accounts', 'emirates'));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');
        $request->validate(['name' => 'required|string', 'code' => 'required|string']);

        DB::table('vendors')->insert([
            'company_id'        => $companyId,
            'code'              => $request->code,
            'name'              => $request->name,
            'name_arabic'       => $request->name_arabic,
            'trn'               => $request->trn,
            'is_vat_registered' => $request->has('is_vat_registered'),
            'is_foreign_vendor' => $request->has('is_foreign_vendor'),
            'email'             => $request->email,
            'phone'             => $request->phone,
            'contact_person'    => $request->contact_person,
            'address_line1'     => $request->address_line1,
            'city'              => $request->city,
            'emirate'           => $request->emirate,
            'country'           => $request->country ?? 'UAE',
            'payment_terms_days'=> $request->payment_terms_days ?? 30,
            'currency_code'     => 'AED',
            'ap_account_id'     => $request->ap_account_id,
            'bank_name'         => $request->bank_name,
            'bank_account_no'   => $request->bank_account_no,
            'iban'              => $request->iban,
            'swift_code'        => $request->swift_code,
            'is_active'         => true,
            'is_deleted'        => false,
            'notes'             => $request->notes,
            'created_by_id'     => auth()->user()->id,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return redirect('/vendors')->with('success', 'Vendor created successfully.');
    }

    public function show($id)
    {
        $companyId = session('company_id');
        $vendor    = DB::table('vendors')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if (!$vendor) abort(404);

        $bills = DB::table('bills')
            ->where('vendor_id', $id)
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->orderBy('bill_date', 'desc')
            ->get();

        $totalBilled = $bills->sum('total_amount');
        $totalDue    = $bills->sum('amount_due');

        return view('vendors.show', compact('vendor', 'bills', 'totalBilled', 'totalDue'));
    }

    public function edit($id)
    {
        $companyId = session('company_id');
        $vendor    = DB::table('vendors')->where('id', $id)->where('company_id', $companyId)->first();
        if (!$vendor) abort(404);

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('account_type', 'LIABILITY')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $emirates = ['Abu Dhabi','Dubai','Sharjah','Ajman','Umm Al Quwain','Ras Al Khaimah','Fujairah'];
        return view('vendors.edit', compact('vendor', 'accounts', 'emirates'));
    }

    public function update(Request $request, $id)
    {
        $companyId = session('company_id');
        DB::table('vendors')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->update([
                'name'              => $request->name,
                'name_arabic'       => $request->name_arabic,
                'trn'               => $request->trn,
                'is_vat_registered' => $request->has('is_vat_registered'),
                'is_foreign_vendor' => $request->has('is_foreign_vendor'),
                'email'             => $request->email,
                'phone'             => $request->phone,
                'contact_person'    => $request->contact_person,
                'address_line1'     => $request->address_line1,
                'city'              => $request->city,
                'emirate'           => $request->emirate,
                'country'           => $request->country ?? 'UAE',
                'payment_terms_days'=> $request->payment_terms_days ?? 30,
                'ap_account_id'     => $request->ap_account_id,
                'bank_name'         => $request->bank_name,
                'bank_account_no'   => $request->bank_account_no,
                'iban'              => $request->iban,
                'swift_code'        => $request->swift_code,
                'notes'             => $request->notes,
                'updated_by_id'     => auth()->user()->id,
                'updated_at'        => now(),
            ]);

        return redirect("/vendors/{$id}")->with('success', 'Vendor updated.');
    }
}