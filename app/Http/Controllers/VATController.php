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
        $daysUntilDue  = (int) now()->diffInDays($currentPeriod['due'], false);

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
                'label' => $from->format('M Y') . ' - ' . $to->format('M Y'),
            ];
            $cursor->addMonths(3);
        }
        $allPeriods = array_reverse($allPeriods); // newest first

        // Sales from invoices (subtotal only — no VAT)
        $salesByMonth = DB::table('invoices')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->whereNotIn('status', ['VOID', 'DRAFT'])
            ->selectRaw("DATE_TRUNC('month', invoice_date::date)::date AS month, SUM(subtotal) AS sales")
            ->groupByRaw("DATE_TRUNC('month', invoice_date::date)")
            ->get()
            ->keyBy(fn($r) => \Carbon\Carbon::parse($r->month)->format('Y-m'));

        // Purchases from bills (subtotal only — no VAT)
        $purchasesByMonth = DB::table('bills')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->whereNotIn('status', ['VOID', 'DRAFT'])
            ->selectRaw("DATE_TRUNC('month', bill_date::date)::date AS month, SUM(subtotal) AS purchases")
            ->groupByRaw("DATE_TRUNC('month', bill_date::date)")
            ->get()
            ->keyBy(fn($r) => \Carbon\Carbon::parse($r->month)->format('Y-m'));

        // Output VAT from journal lines (credits to VAT output account) — same principle as trial balance
        $vatOutputAcctId = $company->default_vat_output_id;
        $outputVatByMonth = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('je.company_id', $companyId)
            ->where('je.is_deleted', false)
            ->where('je.status', 'POSTED')
            ->where('je.journal_type', '!=', 'CLOSING_ENTRY')
            ->where('jl.account_id', $vatOutputAcctId)
            ->where('jl.credit_amount', '>', 0)
            ->selectRaw("DATE_TRUNC('month', je.entry_date::date)::date AS month, SUM(jl.credit_amount) AS output_vat")
            ->groupByRaw("DATE_TRUNC('month', je.entry_date::date)")
            ->get()
            ->keyBy(fn($r) => \Carbon\Carbon::parse($r->month)->format('Y-m'));

        // Input VAT from journal lines (debits to VAT input account) — same principle as trial balance
        $vatInputAcctId = $company->default_vat_input_id;
        $inputVatByMonth = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('je.company_id', $companyId)
            ->where('je.is_deleted', false)
            ->where('je.status', 'POSTED')
            ->where('je.journal_type', '!=', 'CLOSING_ENTRY')
            ->where('jl.account_id', $vatInputAcctId)
            ->where('jl.debit_amount', '>', 0)
            ->selectRaw("DATE_TRUNC('month', je.entry_date::date)::date AS month, SUM(jl.debit_amount) AS input_vat")
            ->groupByRaw("DATE_TRUNC('month', je.entry_date::date)")
            ->get()
            ->keyBy(fn($r) => \Carbon\Carbon::parse($r->month)->format('Y-m'));

        // VAT Penalties & Fines totals by period (debits to the penalty expense account)
        $vatPenaltyAcctId = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('code', '4307')
            ->value('id');

        $penaltiesByPeriod = DB::table('journal_entries as je')
            ->join('journal_lines as jl', 'jl.journal_entry_id', '=', 'je.id')
            ->where('je.company_id', $companyId)
            ->where('je.is_deleted', false)
            ->where('je.status', 'POSTED')
            ->where('jl.account_id', $vatPenaltyAcctId)
            ->where('jl.debit_amount', '>', 0)
            ->selectRaw("je.entry_date::date AS entry_date, SUM(jl.debit_amount) AS penalty_total")
            ->groupByRaw("je.entry_date::date")
            ->get();

        // Pending FTA penalty bills from bills table (more accurate than the JE date-window heuristic)
        $pendingPenaltyBills = $vatPenaltyAcctId ? DB::table('bills as b')
            ->join('bill_lines as bl', 'bl.bill_id', '=', 'b.id')
            ->where('b.company_id', $companyId)
            ->where('b.is_deleted', false)
            ->whereIn('b.status', ['APPROVED', 'PARTIAL'])
            ->where('bl.account_id', $vatPenaltyAcctId)
            ->select('b.id', 'b.bill_number', 'b.bill_date', 'b.amount_due', 'b.notes')
            ->orderBy('b.bill_date')
            ->get() : collect();
        $totalPendingPenalties = (float) $pendingPenaltyBills->sum('amount_due');

        // Roll monthly data into each quarter
        $history = collect($allPeriods)->map(function ($period) use ($salesByMonth, $purchasesByMonth, $outputVatByMonth, $inputVatByMonth, $returns, $penaltiesByPeriod) {
            $from = \Carbon\Carbon::parse($period['from']);
            $to   = \Carbon\Carbon::parse($period['to']);
            $months = [
                $from->format('Y-m'),
                $from->copy()->addMonth()->format('Y-m'),
                $from->copy()->addMonths(2)->format('Y-m'),
            ];

            $sales      = collect($months)->sum(fn($m) => $salesByMonth->get($m)->sales ?? 0);
            $outputVAT  = collect($months)->sum(fn($m) => $outputVatByMonth->get($m)->output_vat ?? 0);
            $purchases  = collect($months)->sum(fn($m) => $purchasesByMonth->get($m)->purchases ?? 0);
            $inputVAT   = collect($months)->sum(fn($m) => $inputVatByMonth->get($m)->input_vat ?? 0);
            $netDue     = $outputVAT - $inputVAT;
            $vatReturn  = $returns->get($period['from']);
            $amountPaid = $vatReturn->amount_paid ?? 0;

            // Penalties incurred between period end and 90 days after (late-payment window)
            $penaltyWindowEnd = $to->copy()->addDays(90)->toDateString();
            $periodPenalties  = $penaltiesByPeriod
                ->filter(fn($p) => $p->entry_date >= $period['to'] && $p->entry_date <= $penaltyWindowEnd)
                ->sum('penalty_total');

            $penaltyPaid = (float) ($vatReturn->penalty_amount_paid ?? 0);
            // Penalties paid separately as bills are NOT included in the VAT return balance
            $penaltyForBalance = $penaltyPaid; // only what was explicitly recorded on this return
            // Balance = VAT net payable + explicitly-recorded penalties - total paid
            $balance     = ($vatReturn->box13_net_payable ?? $netDue) + $penaltyForBalance - $amountPaid;

            return array_merge($period, compact(
                'sales', 'outputVAT', 'purchases', 'inputVAT', 'netDue',
                'vatReturn', 'amountPaid', 'balance', 'periodPenalties', 'penaltyPaid'
            ));
        });

        return view('vat.index', compact(
            'returns', 'company',
            'currentPeriod', 'prevPeriod',
            'currentVAT', 'currentReturn',
            'daysUntilDue', 'history',
            'totalPendingPenalties', 'pendingPenaltyBills'
        ));
    }

    public function pay(Request $request, $id)
    {
        $companyId = session('company_id');

        $request->validate([
            'payment_source'  => 'required|in:bank,owner_card,vat_surplus,split',
            'payment_date'    => 'required|date',
            'bank_amount'     => 'nullable|numeric|min:0',
            'surplus_amount'  => 'nullable|numeric|min:0',
            'penalty_amount'  => 'nullable|numeric|min:0',
        ]);

        $vatReturn = DB::table('vat_returns')->where('id', $id)->where('company_id', $companyId)->first();
        if (!$vatReturn) abort(404);

        $source      = $request->payment_source;
        $payDate     = $request->payment_date;
        // Use remaining balance, not the original filed amount
        $netPayable  = max(0.0, (float) $vatReturn->box13_net_payable
            - (float) ($vatReturn->amount_paid ?? 0)
            + (float) ($vatReturn->penalty_amount_paid ?? 0));
        $penaltyAmt  = (float) ($request->penalty_amount ?? 0);
        $totalDue    = $netPayable + $penaltyAmt;

        // Resolve bank vs surplus amounts from source
        $bankAmount = 0.0;
        $surplusAmt = 0.0;
        if ($source === 'vat_surplus') {
            $surplusAmt = $netPayable;
        } elseif ($source === 'split') {
            $bankAmount = (float) ($request->bank_amount ?? 0);
            $surplusAmt = (float) ($request->surplus_amount ?? 0);
        } else {
            // bank or owner_card — full cash amount = VAT + penalties
            $bankAmount = (float) ($request->bank_amount ?? $totalDue);
        }

        if (abs(($bankAmount + $surplusAmt) - $totalDue) > 0.02) {
            return back()->withErrors(['amount' => sprintf(
                'Payment amounts (%.2f) must equal total due (%.2f).', $bankAmount + $surplusAmt, $totalDue
            )]);
        }

        $payJeId  = null;
        $jeNumber = null;

        if ($bankAmount > 0) {
            $jeYear   = date('Y', strtotime($payDate));
            $count    = DB::table('journal_entries')->where('company_id', $companyId)->whereYear('entry_date', $jeYear)->count() + 1;
            $jeNumber = 'JE-' . $jeYear . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
            $period   = DB::table('accounting_periods')
                            ->where('company_id', $companyId)
                            ->whereDate('start_date', '<=', $payDate)
                            ->whereDate('end_date', '>=', $payDate)
                            ->first();

            // VAT Tax clearing account (what gets debited for the VAT portion)
            $vatAcctId = (int) DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', '2301')
                ->value('id') ?: 674;

            // Penalty clearing: for bill-based workflows debit the AP account that penalty bill JEs credit
            // (finds the credit side of a PURCHASE JE that has a 4307-penalty debit line)
            // Falls back to code 5300 for JE-only penalty setups.
            $penaltyAcctId = DB::table('accounts')
                ->where('company_id', $companyId)->where('code', '4307')->value('id');
            $penaltyClearingAcctId = $penaltyAcctId ? (int) DB::table('journal_lines as jl')
                ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
                ->where('je.company_id', $companyId)
                ->where('je.is_deleted', false)
                ->where('je.journal_type', 'PURCHASE')
                ->whereExists(function ($q) use ($companyId, $penaltyAcctId) {
                    $q->select(DB::raw(1))
                      ->from('journal_lines as jl2')
                      ->whereColumn('jl2.journal_entry_id', 'je.id')
                      ->where('jl2.account_id', $penaltyAcctId)
                      ->where('jl2.debit_amount', '>', 0);
                })
                ->where('jl.credit_amount', '>', 0)
                ->value('jl.account_id') : 0;
            if (!$penaltyClearingAcctId) {
                $penaltyClearingAcctId = (int) DB::table('accounts')
                    ->where('company_id', $companyId)->where('code', '5300')->value('id');
            }

            if ($source === 'owner_card') {
                $creditAcctId   = $penaltyClearingAcctId ?: 1733;
                $creditAcctName = 'Shareholders Current Account';
                $jeType         = 'GENERAL';
            } else {
                $creditAcctId   = (int) DB::table('system_settings')->where('company_id', $companyId)->where('key', 'vat_bank_account_id')->value('value') ?: 647;
                $creditAcctName = DB::table('accounts')->where('id', $creditAcctId)->value('name') ?? 'Bank Account';
                $jeType         = 'BANK_PAYMENT';
            }

            $payJeId = DB::table('journal_entries')->insertGetId([
                'company_id'    => $companyId,
                'entry_number'  => $jeNumber,
                'entry_date'    => $payDate,
                'status'        => 'POSTED',
                'journal_type'  => $jeType,
                'description'   => 'VAT Payment to FTA - ' . $vatReturn->return_number,
                'currency_code' => 'AED',
                'exchange_rate' => 1.0,
                'total_debit'   => $bankAmount,
                'total_credit'  => $bankAmount,
                'period_id'     => $period?->id,
                'is_deleted'    => false,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $lines = [];
            $lineNo = 1;

            // VAT Tax debit line (clears 2301 liability)
            $vatLineAmt = min($netPayable, $bankAmount);
            if ($vatLineAmt > 0) {
                $lines[] = [
                    'journal_entry_id' => $payJeId,
                    'company_id'       => $companyId,
                    'account_id'       => $vatAcctId,
                    'line_number'      => $lineNo++,
                    'description'      => 'VAT payment to FTA - ' . $vatReturn->return_number,
                    'debit_amount'     => $vatLineAmt,
                    'credit_amount'    => 0,
                ];
            }

            // Penalty debit line (clears SCA liability created by penalty JEs)
            if ($penaltyAmt > 0 && $penaltyClearingAcctId) {
                $lines[] = [
                    'journal_entry_id' => $payJeId,
                    'company_id'       => $companyId,
                    'account_id'       => $penaltyClearingAcctId,
                    'line_number'      => $lineNo++,
                    'description'      => 'FTA penalties - ' . $vatReturn->return_number,
                    'debit_amount'     => $penaltyAmt,
                    'credit_amount'    => 0,
                ];
            }

            // Credit line (bank / SCA) for total outflow
            $lines[] = [
                'journal_entry_id' => $payJeId,
                'company_id'       => $companyId,
                'account_id'       => $creditAcctId,
                'line_number'      => $lineNo++,
                'description'      => $creditAcctName,
                'debit_amount'     => 0,
                'credit_amount'    => $bankAmount,
            ];

            DB::table('journal_lines')->insert($lines);
        }

        // Build payment note
        $noteLines = match ($source) {
            'bank'        => ['Bank transfer: AED ' . number_format($bankAmount, 2)],
            'owner_card'  => ["Owner's card: AED " . number_format($bankAmount, 2)],
            'vat_surplus' => ['Offset from VAT Tax surplus: AED ' . number_format($surplusAmt, 2)],
            'split'       => [
                'Bank transfer: AED ' . number_format($bankAmount, 2),
                'Offset from VAT Tax surplus: AED ' . number_format($surplusAmt, 2),
            ],
            default => [],
        };
        if ($penaltyAmt > 0) {
            $noteLines[] = 'Penalties: AED ' . number_format($penaltyAmt, 2);
        }

        DB::table('vat_returns')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->update([
                'amount_paid'               => $bankAmount + $surplusAmt,
                'payment_date'              => $payDate,
                'status'                    => 'PAID',
                'payment_source'            => $source,
                'bank_amount'               => $bankAmount,
                'surplus_used'              => $surplusAmt,
                'penalty_amount_paid'       => $penaltyAmt,
                'payment_journal_entry_id'  => $payJeId,
                'notes'                     => implode("\n", $noteLines),
                'updated_at'                => now(),
            ]);

        // Mark FTA penalty bills as PAID oldest-first up to the penalty amount recorded
        if ($penaltyAmt > 0) {
            $penaltyAcctId = $penaltyAcctId ?? DB::table('accounts')
                ->where('company_id', $companyId)->where('code', '4307')->value('id');
            if ($penaltyAcctId) {
                $pendingBills = DB::table('bills as b')
                    ->join('bill_lines as bl', 'bl.bill_id', '=', 'b.id')
                    ->where('b.company_id', $companyId)
                    ->where('b.is_deleted', false)
                    ->whereIn('b.status', ['APPROVED', 'PARTIAL'])
                    ->where('bl.account_id', $penaltyAcctId)
                    ->select('b.id', 'b.amount_due', 'b.amount_paid', 'b.total_amount')
                    ->orderBy('b.bill_date')
                    ->get();

                $remaining = $penaltyAmt;
                foreach ($pendingBills as $pb) {
                    if ($remaining <= 0) break;
                    if ($remaining >= $pb->amount_due) {
                        DB::table('bills')->where('id', $pb->id)->where('company_id', $companyId)->update([
                            'status'      => 'PAID',
                            'amount_paid' => $pb->total_amount,
                            'amount_due'  => 0,
                            'updated_at'  => now(),
                        ]);
                        $remaining -= $pb->amount_due;
                    } else {
                        DB::table('bills')->where('id', $pb->id)->where('company_id', $companyId)->update([
                            'status'      => 'PARTIAL',
                            'amount_paid' => $pb->amount_paid + $remaining,
                            'amount_due'  => $pb->amount_due - $remaining,
                            'updated_at'  => now(),
                        ]);
                        $remaining = 0;
                    }
                }
            }
        }

        $msg = $payJeId
            ? "Payment recorded. Journal entry {$jeNumber} created."
            : "Payment recorded (offset from VAT surplus — no cash JE needed).";

        return redirect('/vat')->with('success', $msg);
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
            'label' => $from->format('M Y') . ' - ' . $to->format('M Y'),
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
            'label' => $from->format('M Y') . ' - ' . $to->format('M Y'),
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
        $company = DB::table('companies')->find($companyId);

        // Standard sales from invoices (subtotal)
        $invoiceData = DB::table('invoices')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('status', '!=', 'VOID')
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->selectRaw('COALESCE(SUM(subtotal), 0) as standard_sales')
            ->first();

        // Standard purchases from bills (subtotal)
        $billData = DB::table('bills')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('status', '!=', 'VOID')
            ->whereBetween('bill_date', [$dateFrom, $dateTo])
            ->selectRaw('COALESCE(SUM(subtotal), 0) as standard_purchases')
            ->first();

        // Output VAT from journal lines — same principle as trial balance
        $outputVatData = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('je.company_id', $companyId)
            ->where('je.is_deleted', false)
            ->where('je.status', 'POSTED')
            ->where('je.journal_type', '!=', 'CLOSING_ENTRY')
            ->where('jl.account_id', $company->default_vat_output_id)
            ->where('jl.credit_amount', '>', 0)
            ->whereBetween('je.entry_date', [$dateFrom, $dateTo])
            ->selectRaw('COALESCE(SUM(jl.credit_amount), 0) as output_vat')
            ->first();

        // Input VAT from journal lines — same principle as trial balance
        $inputVatData = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('je.company_id', $companyId)
            ->where('je.is_deleted', false)
            ->where('je.status', 'POSTED')
            ->where('je.journal_type', '!=', 'CLOSING_ENTRY')
            ->where('jl.account_id', $company->default_vat_input_id)
            ->where('jl.debit_amount', '>', 0)
            ->whereBetween('je.entry_date', [$dateFrom, $dateTo])
            ->selectRaw('COALESCE(SUM(jl.debit_amount), 0) as input_vat')
            ->first();

        $standardSales     = $invoiceData->standard_sales ?? 0;
        $outputVatAmount   = $outputVatData->output_vat ?? 0;
        $standardPurchases = $billData->standard_purchases ?? 0;
        $inputVatAmount    = $inputVatData->input_vat ?? 0;
        $netVat            = $outputVatAmount - $inputVatAmount;

        return compact(
            'standardSales', 'outputVatAmount',
            'standardPurchases', 'inputVatAmount', 'netVat'
        );
    }
}