<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VATController extends Controller
{
    public function index()
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $returns = DB::table('vat_returns')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->orderBy('period_from', 'desc')
            ->get();

        return view('vat.index', compact('returns'));
    }

    public function create(Request $request)
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $dateFrom = $request->get('date_from', date('Y-01-01'));
        $dateTo   = $request->get('date_to',   date('Y-m-d'));

        // Auto-calculate VAT return boxes from journal data
        $data = $this->calculateVATBoxes($companyId, $dateFrom, $dateTo);

        return view('vat.create', array_merge($data, compact('dateFrom', 'dateTo')));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');

        $count        = DB::table('vat_returns')->where('company_id', $companyId)->count() + 1;
        $returnNumber = 'VAT-' . date('Y') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        // Calculate net VAT
        $totalOutput = floatval($request->box6_total_output_tax);
        $totalInput  = floatval($request->box9_total_input_tax);
        $adjustment  = floatval($request->box10_adjustment ?? 0);
        $netVat      = $totalOutput - $totalInput + $adjustment;
        $netPayable  = $netVat + floatval($request->box12_vat_on_imports ?? 0);

        DB::table('vat_returns')->insert([
            'company_id'                   => $companyId,
            'return_number'                => $returnNumber,
            'period_from'                  => $request->period_from,
            'period_to'                    => $request->period_to,
            'due_date'                     => $request->due_date,
            'status'                       => 'DRAFT',
            'box1_standard_rated_sales'    => $request->box1_standard_rated_sales ?? 0,
            'box1_vat_amount'              => $request->box1_vat_amount ?? 0,
            'box2_zero_rated_sales'        => $request->box2_zero_rated_sales ?? 0,
            'box3_exempt_sales'            => $request->box3_exempt_sales ?? 0,
            'box4_goods_imported'          => $request->box4_goods_imported ?? 0,
            'box4_vat_amount'              => $request->box4_vat_amount ?? 0,
            'box5_reverse_charge'          => $request->box5_reverse_charge ?? 0,
            'box5_vat_amount'              => $request->box5_vat_amount ?? 0,
            'box6_total_output_tax'        => $totalOutput,
            'box7_standard_purchases'      => $request->box7_standard_purchases ?? 0,
            'box7_recoverable_vat'         => $request->box7_recoverable_vat ?? 0,
            'box8_reverse_charge_purchases'=> $request->box8_reverse_charge_purchases ?? 0,
            'box8_recoverable_vat'         => $request->box8_recoverable_vat ?? 0,
            'box9_total_input_tax'         => $totalInput,
            'box10_adjustment'             => $adjustment,
            'box11_net_vat_due'            => $netVat,
            'box12_vat_on_imports'         => $request->box12_vat_on_imports ?? 0,
            'box13_net_payable'            => $netPayable,
            'notes'                        => $request->notes,
            'is_deleted'                   => false,
            'created_by_id'                => auth()->user()->id,
            'created_at'                   => now(),
            'updated_at'                   => now(),
        ]);

        return redirect('/vat')->with('success', "VAT Return {$returnNumber} created.");
    }

    public function show($id)
    {
        $companyId = session('company_id');
        $return    = DB::table('vat_returns')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if (!$return) abort(404);

        return view('vat.show', compact('return'));
    }

    public function submit($id)
    {
        $companyId = session('company_id');
        DB::table('vat_returns')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->update([
                'status'          => 'SUBMITTED',
                'submission_date' => now()->toDateString(),
                'updated_at'      => now(),
            ]);

        return redirect("/vat/{$id}")->with('success', 'VAT Return marked as submitted.');
    }

    private function calculateVATBoxes(int $companyId, string $dateFrom, string $dateTo): array
    {
        // Output VAT — from invoice VAT amounts
        $outputVAT = DB::table('invoices')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('status', '!=', 'VOID')
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->selectRaw('
                COALESCE(SUM(subtotal), 0) as standard_sales,
                COALESCE(SUM(total_vat_amount), 0) as output_vat
            ')
            ->first();

        // Input VAT — from bill VAT amounts
        $inputVAT = DB::table('bills')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('status', '!=', 'VOID')
            ->whereBetween('bill_date', [$dateFrom, $dateTo])
            ->selectRaw('
                COALESCE(SUM(subtotal), 0) as standard_purchases,
                COALESCE(SUM(total_vat_amount), 0) as input_vat
            ')
            ->first();

        $standardSales     = $outputVAT->standard_sales ?? 0;
        $outputVatAmount   = $outputVAT->output_vat ?? 0;
        $standardPurchases = $inputVAT->standard_purchases ?? 0;
        $inputVatAmount    = $inputVAT->input_vat ?? 0;
        $netVat            = $outputVatAmount - $inputVatAmount;

        return compact(
            'standardSales', 'outputVatAmount',
            'standardPurchases', 'inputVatAmount', 'netVat'
        );
    }
}