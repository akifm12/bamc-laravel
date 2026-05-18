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

        $company = DB::table('companies')->find($companyId);

        $returns = DB::table('vat_returns')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->get()
            ->keyBy('period_from'); // keyed by period_from for quick lookup

        $cycleStart    = (int) ($company->vat_quarter_start_month ?? 1);
        $currentPeriod = $this->getCurrentPeriod($cycleStart);
        $prevPeriod    = $this->getPreviousPeriod($cycleStart);

        // VAT so far in the current period
        $currentVAT = $this->calculateVATBoxes($companyId, $currentPeriod['from'], $currentPeriod['to']);

        $currentReturn = $returns->get($currentPeriod['from']);
        $daysUntilDue  = now()->diffInDays($currentPeriod['due'], false);

        // --- Historical quarters ---
        // Start from vat_registration_date, fallback to earliest invoice
        $startDate = $company->vat_registration_date;
        if (!$startDate) {
            $earliest = DB::table('invoices')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->min('invoice_date');
            $startDate = $earliest ?? date('Y-01-01');
        }

        // Snap start date back to the quarter boundary for this cycle
        $startCarbon  = \Carbon\Carbon::parse($startDate)->startOfMonth();
        $offset       = (($startCarbon->month - $cycleStart) % 3 + 3) % 3;
        $startCarbon->subMonths($offset);

        // Generate all quarter periods from start up to (but not including) current period
        $allPeriods = [];
        $cursor = $startCarbon->copy();
        $currentFrom = \Carbon\Carbon::parse($currentPeriod['from']);

        while ($cursor->lt($currentFrom)) {
            $from = $cursor->copy()->startOfMonth();
            $to   = $cursor->copy()->addMonths(2)->endOfMonth();
            $due  = $to->copy()->addDays(28);
            $allPeriods[] = [
                'from'  => $from->toDateString(),
                'to'    => $to->toDateString(),
                'due'   => $due->toDateString(),
                'label' => $from->format('M Y') . ' — ' . $to->format('M Y'),
            ];
            $cursor->addMonths(3);
        }
        $allPeriods = array_reverse($allPeriods); // newest first

        // Two aggregate queries — invoices and bills — grouped by month
        $invoicesByMonth = DB::table('invoices')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->whereNotIn('status', ['VOID', 'DRAFT'])
            ->selectRaw("DATE_TRUNC('month', invoice_date::date)::date AS month, SUM(subtotal) AS sales, SUM(total_vat_amount) AS output_vat")
            ->groupByRaw("DATE_TRUNC('month', invoice_date::date)")
            ->get()
            ->keyBy(fn($r) => \Carbon\Carbon::parse($r->month)->format('Y-m'));

        $billsByMonth = DB::table('bills')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->whereNotIn('status', ['VOID', 'DRAFT'])
            ->selectRaw("DATE_TRUNC('month', bill_date::date)::date AS month, SUM(subtotal) AS purchases, SUM(total_vat_amount) AS input_vat")
            ->groupByRaw("DATE_TRUNC('month', bill_date::date)")
            ->get()
            ->keyBy(fn($r) => \Carbon\Carbon::parse($r->month)->format('Y-m'));

        // Roll monthly data into each quarter
        $history = collect($allPeriods)->map(function ($period) use ($invoicesByMonth, $billsByMonth, $returns) {
            $from = \Carbon\Carbon::parse($period['from']);
            $months = [
                $from->format('Y-m'),
                $from->copy()->addMonth()->format('Y-m'),
                $from->copy()->addMonths(2)->format('Y-m'),
            ];

            $sales      = collect($months)->sum(fn($m) => $invoicesByMonth->get($m)->sales ?? 0);
            $outputVAT  = collect($months)->sum(fn($m) => $invoicesByMonth->get($m)->output_vat ?? 0);
            $purchases  = collect($months)->sum(fn($m) => $billsByMonth->get($m)->purchases ?? 0);
            $inputVAT   = collect($months)->sum(fn($m) => $billsByMonth->get($m)->input_vat ?? 0);
            $netDue     = $outputVAT - $inputVAT;
            $vatReturn  = $returns->get($period['from']);
            $amountPaid = $vatReturn->amount_paid ?? 0;
            $balance    = ($vatReturn->box13_net_payable ?? $netDue) - $amountPaid;

            return array_merge($period, compact('sales', 'outputVAT', 'purchases', 'inputVAT', 'netDue', 'vatReturn', 'amountPaid', 'balance'));
        });

        return view('vat.index', compact(
            'returns', 'company',
            'currentPeriod', 'prevPeriod',
            'currentVAT', 'currentReturn',
            'daysUntilDue', 'history'
        ));
    }

    public function pay(Request $request, $id)
    {
        $companyId = session('company_id');
        $request->validate([
            'amount_paid'  => 'required|numeric|min:0',
            'payment_date' => 'required|date',
        ]);

        DB::table('vat_returns')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->update([
                'amount_paid'  => $request->amount_paid,
                'payment_date' => $request->payment_date,
                'status'       => 'PAID',
                'updated_at'   => now(),
            ]);

        return redirect('/vat')->with('success', 'Payment recorded successfully.');
    }

    private function getCurrentPeriod(int $cycleStart): array
    {
        $month = (int) now()->month;
        $year  = (int) now()->year;

        // How many months into the current quarter are we? (0, 1, or 2)
        $offset     = (($month - $cycleStart) % 3 + 3) % 3;
        $startMonth = $month - $offset;
        $startYear  = $year;

        if ($startMonth < 1) {
            $startMonth += 12;
            $startYear  -= 1;
        }

        $endMonth = $startMonth + 2;
        $endYear  = $startYear;
        if ($endMonth > 12) {
            $endMonth -= 12;
            $endYear  += 1;
        }

        $from    = \Carbon\Carbon::create($startYear, $startMonth, 1)->startOfMonth();
        $to      = \Carbon\Carbon::create($endYear, $endMonth, 1)->endOfMonth();
        $due     = $to->copy()->addDays(28);

        return [
            'from'  => $from->toDateString(),
            'to'    => $to->toDateString(),
            'due'   => $due->toDateString(),
            'label' => $from->format('M Y') . ' — ' . $to->format('M Y'),
        ];
    }

    private function getPreviousPeriod(int $cycleStart): array
    {
        $month = (int) now()->month;
        $year  = (int) now()->year;

        $offset     = (($month - $cycleStart) % 3 + 3) % 3;
        $startMonth = $month - $offset - 3; // one quarter back
        $startYear  = $year;

        while ($startMonth < 1) {
            $startMonth += 12;
            $startYear  -= 1;
        }

        $endMonth = $startMonth + 2;
        $endYear  = $startYear;
        if ($endMonth > 12) {
            $endMonth -= 12;
            $endYear  += 1;
        }

        $from = \Carbon\Carbon::create($startYear, $startMonth, 1)->startOfMonth();
        $to   = \Carbon\Carbon::create($endYear, $endMonth, 1)->endOfMonth();
        $due  = $to->copy()->addDays(28);

        return [
            'from'  => $from->toDateString(),
            'to'    => $to->toDateString(),
            'due'   => $due->toDateString(),
            'label' => $from->format('M Y') . ' — ' . $to->format('M Y'),
        ];
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