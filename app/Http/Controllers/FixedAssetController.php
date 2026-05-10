<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FixedAssetController extends Controller
{
    public function index()
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $assets = DB::table('fixed_assets')
            ->leftJoin('asset_categories', 'asset_categories.id', '=', 'fixed_assets.category_id')
            ->where('fixed_assets.company_id', $companyId)
            ->where('fixed_assets.is_deleted', false)
            ->select('fixed_assets.*', 'asset_categories.name as category_name')
            ->orderBy('fixed_assets.asset_number')
            ->get();

        $totalCost  = $assets->sum('purchase_cost');
        $totalAccum = $assets->sum('accumulated_depreciation');
        $totalNBV   = $assets->sum('net_book_value');

        return view('assets.index', compact('assets', 'totalCost', 'totalAccum', 'totalNBV'));
    }

    public function create()
    {
        $companyId  = session('company_id');
        $categories = DB::table('asset_categories')
            ->where('company_id', $companyId)
            ->get();

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->whereRaw("is_active = true")
            ->orderBy('code')
            ->get()
            ->groupBy('account_type');

        $vendors = DB::table('vendors')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get();

        return view('assets.create', compact('categories', 'accounts', 'vendors'));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');
        $request->validate([
            'name'          => 'required|string',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
        ]);

        $cost           = floatval($request->purchase_cost);
        $residual       = floatval($request->residual_value ?? 0);
        $depreciable    = $cost - $residual;
        $usefulLife     = floatval($request->useful_life_years ?? 5);
        $deprRate       = $usefulLife > 0 ? (100 / $usefulLife) : 20;

        $count       = DB::table('fixed_assets')->where('company_id', $companyId)->count() + 1;
        $assetNumber = 'FA-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        DB::table('fixed_assets')->insert([
            'company_id'              => $companyId,
            'category_id'             => $request->category_id ?: null,
            'asset_number'            => $assetNumber,
            'name'                    => $request->name,
            'description'             => $request->description,
            'serial_number'           => $request->serial_number,
            'location'                => $request->location,
            'purchase_date'           => $request->purchase_date,
            'in_service_date'         => $request->in_service_date ?? $request->purchase_date,
            'purchase_cost'           => $cost,
            'residual_value'          => $residual,
            'depreciable_amount'      => $depreciable,
            'depreciation_method'     => $request->depreciation_method ?? 'straight_line',
            'useful_life_years'       => $usefulLife,
            'depreciation_rate'       => $deprRate,
            'accumulated_depreciation'=> 0,
            'net_book_value'          => $cost,
            'asset_account_id'        => $request->asset_account_id ?: null,
            'accum_depr_account_id'   => $request->accum_depr_account_id ?: null,
            'depr_expense_account_id' => $request->depr_expense_account_id ?: null,
            'supplier_id'             => $request->supplier_id ?: null,
            'purchase_invoice_ref'    => $request->purchase_invoice_ref,
            'insurance_policy_no'     => $request->insurance_policy_no,
            'insurance_expiry'        => $request->insurance_expiry ?: null,
            'status'                  => 'active',
            'notes'                   => $request->notes,
            'is_deleted'              => false,
            'created_by_id'           => auth()->user()->id,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        return redirect('/assets')->with('success', "Asset {$assetNumber} created.");
    }

    public function show($id)
    {
        $companyId = session('company_id');
        $asset     = DB::table('fixed_assets')
            ->leftJoin('asset_categories', 'asset_categories.id', '=', 'fixed_assets.category_id')
            ->where('fixed_assets.id', $id)
            ->where('fixed_assets.company_id', $companyId)
            ->select('fixed_assets.*', 'asset_categories.name as category_name')
            ->first();

        if (!$asset) abort(404);

        $depreciationHistory = DB::table('depreciation_lines')
            ->where('asset_id', $id)
            ->orderBy('depreciation_date', 'desc')
            ->get();

        return view('assets.show', compact('asset', 'depreciationHistory'));
    }

    public function runDepreciation(Request $request, $id)
    {
        $companyId = session('company_id');
        $asset     = DB::table('fixed_assets')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if (!$asset) abort(404);

        $request->validate(['depreciation_date' => 'required|date']);

        // Calculate monthly depreciation
        $annualDepr  = $asset->depreciable_amount / $asset->useful_life_years;
        $monthlyDepr = $annualDepr / 12;

        // Cap at remaining depreciable amount
        $remaining  = $asset->depreciable_amount - $asset->accumulated_depreciation;
        $deprAmount = min($monthlyDepr, $remaining);

        if ($deprAmount <= 0) {
            return back()->with('error', 'Asset is fully depreciated.');
        }

        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $request->depreciation_date)
            ->where('end_date', '>=', $request->depreciation_date)
            ->first();

        if (!$period) {
            return back()->with('error', 'No accounting period found for this date.');
        }

        DB::transaction(function () use ($companyId, $asset, $deprAmount, $request, $period, $id) {
            // Journal entry
            $count    = DB::table('journal_entries')->where('company_id', $companyId)->count() + 1;
            $jeNumber = 'JE-' . date('Y', strtotime($request->depreciation_date)) . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

            $jeId = DB::table('journal_entries')->insertGetId([
                'company_id'    => $companyId,
                'period_id'     => $period->id,
                'entry_number'  => $jeNumber,
                'entry_date'    => $request->depreciation_date,
                'journal_type'  => 'DEPRECIATION',
                'status'        => 'POSTED',
                'description'   => "Depreciation — {$asset->name}",
                'reference'     => $asset->asset_number,
                'total_debit'   => $deprAmount,
                'total_credit'  => $deprAmount,
                'currency_code' => 'AED',
                'exchange_rate' => 1.0,
                'is_reversal'   => false,
                'is_recurring'  => false,
                'is_deleted'    => false,
                'created_by_id' => auth()->user()->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Debit depreciation expense
            if ($asset->depr_expense_account_id) {
                DB::table('journal_lines')->insert([
                    'journal_entry_id' => $jeId,
                    'company_id'       => $companyId,
                    'account_id'       => $asset->depr_expense_account_id,
                    'line_number'      => 1,
                    'description'      => "Depreciation — {$asset->name}",
                    'debit_amount'     => $deprAmount,
                    'credit_amount'    => 0,
                    'currency_code'    => 'AED',
                    'exchange_rate'    => 1.0,
                    'is_reconciled'    => false,
                ]);
            }

            // Credit accumulated depreciation
            if ($asset->accum_depr_account_id) {
                DB::table('journal_lines')->insert([
                    'journal_entry_id' => $jeId,
                    'company_id'       => $companyId,
                    'account_id'       => $asset->accum_depr_account_id,
                    'line_number'      => 2,
                    'description'      => "Accumulated depreciation — {$asset->name}",
                    'debit_amount'     => 0,
                    'credit_amount'    => $deprAmount,
                    'currency_code'    => 'AED',
                    'exchange_rate'    => 1.0,
                    'is_reconciled'    => false,
                ]);
            }

            // Update asset
            $newAccum = $asset->accumulated_depreciation + $deprAmount;
            $newNBV   = $asset->purchase_cost - $newAccum;
            $status   = $newNBV <= $asset->residual_value ? 'fully_depreciated' : 'active';

            DB::table('fixed_assets')->where('id', $id)->update([
                'accumulated_depreciation' => $newAccum,
                'net_book_value'           => max(0, $newNBV),
                'status'                   => $status,
                'updated_at'               => now(),
            ]);

            // Log depreciation line
           DB::table('depreciation_lines')->insert([
    			'asset_id'          => $id,
    			'company_id'        => $companyId,
    			'period_id'         => $period->id,
    			'journal_entry_id'  => $jeId,
    			'depreciation_date' => $request->depreciation_date,
    			'amount'            => $deprAmount,
    			'accumulated_after' => $newAccum,
    			'nbv_after'         => max(0, $newNBV),
    			'is_posted'         => true,
    			'created_at'        => now(),
    			'updated_at'        => now(),
]);
        });

        return redirect("/assets/{$id}")->with('success', 'Depreciation posted: AED ' . number_format($deprAmount, 2));
    }

    public function dispose(Request $request, $id)
    {
        $companyId = session('company_id');
        DB::table('fixed_assets')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->update([
                'status'       => 'disposed',
                'disposal_date'=> $request->disposal_date ?? now()->toDateString(),
                'updated_at'   => now(),
            ]);

        return redirect('/assets')->with('success', 'Asset marked as disposed.');
    }
}