<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CompanySetupController extends Controller
{
    public function index()
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $companyId = session('company_id');
        $company   = DB::table('companies')->find($companyId);
        $accounts  = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        $emirates = ['Abu Dhabi','Dubai','Sharjah','Ajman','Umm Al Quwain','Ras Al Khaimah','Fujairah'];
        return view('settings.company', compact('company', 'accounts', 'emirates'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->is_super_admin) abort(403);

        $logoPath = DB::table('companies')->where('id', $id)->value('logo_path');

        if ($request->hasFile('logo')) {
            $request->validate(['logo' => 'image|max:2048']);
            $file     = $request->file('logo');
            $filename = 'logo.' . $file->getClientOriginalExtension();
            $file->move(public_path("logos/{$id}"), $filename);
            $logoPath = "logos/{$id}/{$filename}";
        }

        DB::table('companies')->where('id', $id)->update([
            'name'                    => $request->name,
            'name_arabic'             => $request->name_arabic,
            'trn'                     => $request->trn,
            'trade_license_no'        => $request->trade_license_no,
            'vat_registration_date'   => $request->vat_registration_date ?: null,
            'vat_scheme'              => $request->vat_scheme,
            'address_line1'           => $request->address_line1,
            'address_line2'           => $request->address_line2,
            'city'                    => $request->city,
            'emirate'                 => $request->emirate,
            'country'                 => $request->country ?? 'UAE',
            'po_box'                  => $request->po_box,
            'phone'                   => $request->phone,
            'email'                   => $request->email,
            'website'                 => $request->website,
            'default_vat_rate'        => $request->default_vat_rate ?? 5,
            'default_ar_account_id'   => $request->default_ar_account_id ?: null,
            'default_ap_account_id'   => $request->default_ap_account_id ?: null,
            'bank_name'               => $request->bank_name,
            'bank_account_title'      => $request->bank_account_title,
            'bank_account_number'     => $request->bank_account_number,
            'bank_iban'               => $request->bank_iban,
            'bank_swift'              => $request->bank_swift,
            'logo_path'               => $logoPath,
            'updated_at'              => now(),
        ]);

        session(['company_name' => $request->name]);

        return redirect('/settings/company')->with('success', 'Company details updated.');
    }

    public function fiscalYears()
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $companyId   = session('company_id');
        $fiscalYears = DB::table('fiscal_years')
            ->where('company_id', $companyId)
            ->orderBy('start_date', 'desc')
            ->get();

        foreach ($fiscalYears as $fy) {
            $fy->period_count = DB::table('accounting_periods')
                ->where('fiscal_year_id', $fy->id)
                ->count();
        }

        return view('settings.fiscal_years', compact('fiscalYears'));
    }

    public function storeFiscalYear(Request $request)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $companyId = session('company_id');

        $request->validate([
            'year'       => 'required|integer|min:2000|max:2100',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        $overlap = DB::table('fiscal_years')
            ->where('company_id', $companyId)
            ->where(function($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
            })
            ->exists();

        if ($overlap) {
            return back()->with('error', 'A fiscal year already exists that overlaps with these dates.');
        }

        $fyId = DB::table('fiscal_years')->insertGetId([
            'company_id' => $companyId,
            'name'       => 'FY ' . $request->year,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'status'     => 'OPEN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $start   = Carbon::parse($request->start_date);
        $end     = Carbon::parse($request->end_date);
        $current = $start->copy()->startOfMonth();

        while ($current->lte($end)) {
            $periodEnd = $current->copy()->endOfMonth();
            if ($periodEnd->gt($end)) $periodEnd = $end->copy();

            DB::table('accounting_periods')->insert([
                'company_id'     => $companyId,
                'fiscal_year_id' => $fyId,
                'name'           => $current->format('F Y'),
                'start_date'     => $current->toDateString(),
                'end_date'       => $periodEnd->toDateString(),
                'status'         => 'OPEN',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $current->addMonth()->startOfMonth();
        }

        return redirect('/settings/fiscal-years')->with('success', 'Fiscal year and monthly periods created.');
    }

    public function closeFiscalYear($id)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        DB::table('fiscal_years')->where('id', $id)->update(['status' => 'CLOSED', 'updated_at' => now()]);
        DB::table('accounting_periods')->where('fiscal_year_id', $id)->update(['status' => 'CLOSED', 'updated_at' => now()]);
        return back()->with('success', 'Fiscal year closed.');
    }

    public function reopenFiscalYear($id)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        DB::table('fiscal_years')->where('id', $id)->update(['status' => 'OPEN', 'updated_at' => now()]);
        DB::table('accounting_periods')->where('fiscal_year_id', $id)->update(['status' => 'OPEN', 'updated_at' => now()]);
        return back()->with('success', 'Fiscal year reopened.');
    }

    public function companies()
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $companies = DB::table('companies')->orderBy('name')->get();
        return view('settings.companies', compact('companies'));
    }

    public function createCompany()
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $emirates = ['Abu Dhabi','Dubai','Sharjah','Ajman','Umm Al Quwain','Ras Al Khaimah','Fujairah'];
        return view('settings.create_company', compact('emirates'));
    }

    public function storeCompany(Request $request)
    {
        if (!auth()->user()->is_super_admin) abort(403);

        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string',
        ]);

        // Graceful duplicate checks
        $codeExists = DB::table('companies')
            ->where('code', strtoupper($request->code))
            ->exists();
        if ($codeExists) {
            return back()->withInput()->with('error', 'A company with this code "' . strtoupper($request->code) . '" already exists. Please use a different code.');
        }

        if ($request->trn) {
            $trnExists = DB::table('companies')
                ->where('trn', $request->trn)
                ->exists();
            if ($trnExists) {
                $existing = DB::table('companies')->where('trn', $request->trn)->first();
                return back()->withInput()->with('error', 'TRN ' . $request->trn . ' is already registered to "' . $existing->name . '". Each company must have a unique TRN.');
            }
        }

        $companyId = DB::table('companies')->insertGetId([
            'code'                    => strtoupper($request->code),
            'name'                    => $request->name,
            'name_arabic'             => $request->name_arabic,
            'trn'                     => $request->trn ?: null,
            'trade_license_no'        => $request->trade_license_no,
            'vat_registration_date'   => $request->vat_registration_date ?: null,
            'address_line1'           => $request->address_line1,
            'address_line2'           => $request->address_line2,
            'city'                    => $request->city,
            'emirate'                 => $request->emirate,
            'country'                 => $request->country ?? 'UAE',
            'phone'                   => $request->phone,
            'email'                   => $request->email,
            'website'                 => $request->website,
            'base_currency_code'      => 'AED',
            'fiscal_year_start_month' => 1,
            'decimal_places'          => 2,
            'default_vat_rate'        => 5,
            'is_active'               => true,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        // Assign to current user
        DB::table('user_companies')->insert([
            'user_id'    => auth()->user()->id,
            'company_id' => $companyId,
            'role'       => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create default fiscal year for current year
        $fyId = DB::table('fiscal_years')->insertGetId([
            'company_id' => $companyId,
            'name'       => 'FY ' . date('Y'),
            'start_date' => date('Y') . '-01-01',
            'end_date'   => date('Y') . '-12-31',
            'status'     => 'OPEN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($month = 1; $month <= 12; $month++) {
            $monthDate = Carbon::create(date('Y'), $month, 1);
            DB::table('accounting_periods')->insert([
                'company_id'     => $companyId,
                'fiscal_year_id' => $fyId,
                'name'           => $monthDate->format('F Y'),
                'start_date'     => $monthDate->copy()->startOfMonth()->toDateString(),
                'end_date'       => $monthDate->copy()->endOfMonth()->toDateString(),
                'status'         => 'OPEN',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        return redirect('/settings/companies')->with('success', "Company '{$request->name}' created successfully with FY " . date('Y') . " and 12 monthly periods.");
    }
}