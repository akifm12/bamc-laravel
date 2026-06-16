<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index()
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $runs = DB::table('payroll_runs')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->orderBy('pay_date', 'desc')
            ->get();

        $totalEmployees = DB::table('employees')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereRaw("is_deleted = false")
            ->count();

        return view('payroll.index', compact('runs', 'totalEmployees'));
    }

    public function employees()
    {
        $companyId = session('company_id');
        $employees = DB::table('employees')
            ->where('company_id', $companyId)
            ->whereRaw("is_deleted = false")
            ->orderBy('full_name')
            ->get();

        return view('payroll.employees', compact('employees'));
    }

    public function createEmployee()
    {
        $companyId   = session('company_id');
        $departments = DB::table('departments')
            ->where('company_id', $companyId)
            ->get();

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->whereRaw("is_active = true")
            ->whereIn('account_type', ['ASSET', 'LIABILITY', 'EXPENSE'])
            ->orderBy('code')
            ->get()
            ->groupBy('account_type');

        return view('payroll.create_employee', compact('departments', 'accounts'));
    }

    public function storeEmployee(Request $request)
    {
        $companyId = session('company_id');
        $request->validate([
            'first_name'   => 'required|string',
            'last_name'    => 'required|string',
            'join_date'    => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
        ]);

        $count      = DB::table('employees')->where('company_id', $companyId)->count() + 1;
        $employeeId = 'EMP-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        $basic     = floatval($request->basic_salary);
        $housing   = floatval($request->housing_allowance ?? 0);
        $transport = floatval($request->transport_allowance ?? 0);
        $other     = floatval($request->other_allowances ?? 0);
        $gross     = $basic + $housing + $transport + $other;

        DB::table('employees')->insert([
            'company_id'          => $companyId,
            'employee_id'         => $employeeId,
            'department_id'       => $request->department_id ?: null,
            'first_name'          => $request->first_name,
            'last_name'           => $request->last_name,
            'full_name'           => $request->first_name . ' ' . $request->last_name,
            'nationality'         => $request->nationality,
            'date_of_birth'       => $request->date_of_birth ?: null,
            'gender'              => $request->gender,
            'emirates_id'         => $request->emirates_id,
            'passport_no'         => $request->passport_no,
            'visa_no'             => $request->visa_no,
            'visa_expiry'         => $request->visa_expiry ?: null,
            'labour_card_no'      => $request->labour_card_no,
            'designation'         => $request->designation,
            'join_date'           => $request->join_date,
            'status'              => 'active',
            'basic_salary'        => $basic,
            'housing_allowance'   => $housing,
            'transport_allowance' => $transport,
            'other_allowances'    => $other,
            'gross_salary'        => $gross,
            'payment_frequency'   => $request->payment_frequency ?? 'monthly',
            'bank_name'           => $request->bank_name,
            'bank_account_no'     => $request->bank_account_no,
            'iban'                => $request->iban,
            'wps_routing_code'    => $request->wps_routing_code,
            'salary_account_id'   => $request->salary_account_id ?: null,
            'notes'               => $request->notes,
            'is_deleted'          => false,
            'created_by_id'       => auth()->user()->id,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return redirect('/payroll/employees')->with('success', "Employee {$employeeId} created.");
    }

    public function showEmployee($id)
    {
        $companyId = session('company_id');
        $employee  = DB::table('employees')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if (!$employee) abort(404);

        $payrollLines = DB::table('payroll_lines')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_lines.payroll_run_id')
            ->where('payroll_lines.employee_id', $id)
            ->orderBy('payroll_runs.pay_date', 'desc')
            ->select('payroll_lines.*', 'payroll_runs.pay_date', 'payroll_runs.run_number')
            ->limit(12)
            ->get();

        return view('payroll.show_employee', compact('employee', 'payrollLines'));
    }

    public function runForm()
    {
        $companyId = session('company_id');
        $employees = DB::table('employees')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereRaw("is_deleted = false")
            ->orderBy('full_name')
            ->get();

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->whereRaw("is_active = true")
            ->orderBy('code')
            ->get()
            ->groupBy('account_type');

        return view('payroll.run', compact('employees', 'accounts'));
    }

    public function processPayroll(Request $request)
    {
        $companyId = session('company_id');
        $request->validate([
            'pay_date'        => 'required|date',
            'pay_period_start'=> 'required|date',
            'pay_period_end'  => 'required|date',
        ]);

        $employees = DB::table('employees')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereRaw("is_deleted = false")
            ->get();

        if ($employees->isEmpty()) {
            return back()->with('error', 'No active employees found.');
        }

        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $request->pay_date)
            ->where('end_date', '>=', $request->pay_date)
            ->first();

        if (!$period) {
            return back()->with('error', 'No accounting period found for ' . $request->pay_date . '. Please create a fiscal year first.');
        }

        // Calculate totals outside transaction
        $count           = DB::table('payroll_runs')->where('company_id', $companyId)->count() + 1;
        $runNumber       = 'PR-' . date('Y', strtotime($request->pay_date)) . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        $totalBasic      = $employees->sum('basic_salary');
        $totalAllowances = $employees->sum(fn($e) => floatval($e->housing_allowance) + floatval($e->transport_allowance) + floatval($e->other_allowances));
        $totalGross      = $employees->sum('gross_salary');

        DB::transaction(function () use (
            $companyId, $request, $employees, $period,
            $runNumber, $totalBasic, $totalAllowances, $totalGross
        ) {
            $runId = DB::table('payroll_runs')->insertGetId([
                'company_id'         => $companyId,
                'period_id'          => $period->id,
                'run_number'         => $runNumber,
                'pay_period_start'   => $request->pay_period_start,
                'pay_period_end'     => $request->pay_period_end,
                'pay_date'           => $request->pay_date,
                'total_basic_salary' => $totalBasic,
                'total_allowances'   => $totalAllowances,
                'total_deductions'   => 0,
                'total_net_pay'      => $totalGross,
                'employee_count'     => $employees->count(),
                'status'             => 'DRAFT',
                'wps_file_generated' => false,
                'is_deleted'         => false,
                'created_by_id'      => auth()->user()->id,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            foreach ($employees as $emp) {
                DB::table('payroll_lines')->insert([
                    'payroll_run_id'      => $runId,
                    'company_id'          => $companyId,
                    'employee_id'         => $emp->id,
                    'basic_salary'        => $emp->basic_salary,
                    'housing_allowance'   => $emp->housing_allowance,
                    'transport_allowance' => $emp->transport_allowance,
                    'other_allowances'    => $emp->other_allowances,
                    'overtime_amount'     => 0,
                    'gross_pay'           => $emp->gross_salary,
                    'loan_deduction'      => 0,
                    'absence_deduction'   => 0,
                    'other_deductions'    => 0,
                    'total_deductions'    => 0,
                    'net_pay'             => $emp->gross_salary,
                    'days_worked'         => 30,
                    'days_absent'         => 0,
                    'overtime_hours'      => 0,
                    'eosb_accrual'        => 0,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            // Create journal entry if accounts provided
            if ($request->salary_expense_account && $request->salary_payable_account) {
                $jeYear   = date('Y', strtotime($request->pay_date));
                $jeCount  = DB::table('journal_entries')->where('company_id', $companyId)->whereYear('entry_date', $jeYear)->count() + 1;
                $jeNumber = 'JE-' . $jeYear . '-' . str_pad($jeCount, 5, '0', STR_PAD_LEFT);

                $jeId = DB::table('journal_entries')->insertGetId([
                    'company_id'    => $companyId,
                    'period_id'     => $period->id,
                    'entry_number'  => $jeNumber,
                    'entry_date'    => $request->pay_date,
                    'journal_type'  => 'PAYROLL',
                    'status'        => 'POSTED',
                    'description'   => "Payroll - {$runNumber}",
                    'reference'     => $runNumber,
                    'total_debit'   => $totalGross,
                    'total_credit'  => $totalGross,
                    'currency_code' => 'AED',
                    'exchange_rate' => 1.0,
                    'is_reversal'   => false,
                    'is_recurring'  => false,
                    'is_deleted'    => false,
                    'created_by_id' => auth()->user()->id,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                DB::table('journal_lines')->insert([
                    'journal_entry_id' => $jeId,
                    'company_id'       => $companyId,
                    'account_id'       => $request->salary_expense_account,
                    'line_number'      => 1,
                    'description'      => "Salary expense - {$runNumber}",
                    'debit_amount'     => $totalGross,
                    'credit_amount'    => 0,
                    'currency_code'    => 'AED',
                    'exchange_rate'    => 1.0,
                    'is_reconciled'    => false,
                ]);

                DB::table('journal_lines')->insert([
                    'journal_entry_id' => $jeId,
                    'company_id'       => $companyId,
                    'account_id'       => $request->salary_payable_account,
                    'line_number'      => 2,
                    'description'      => "Salary payable - {$runNumber}",
                    'debit_amount'     => 0,
                    'credit_amount'    => $totalGross,
                    'currency_code'    => 'AED',
                    'exchange_rate'    => 1.0,
                    'is_reconciled'    => false,
                ]);

                DB::table('payroll_runs')->where('id', $runId)->update([
                    'journal_entry_id' => $jeId,
                    'status'           => 'POSTED',
                    'updated_at'       => now(),
                ]);
            }
        });

        return redirect('/payroll')->with('success', 'Payroll run ' . $runNumber . ' created for ' . $employees->count() . ' employees.');
    }

    public function showRun($id)
    {
        $companyId = session('company_id');
        $run       = DB::table('payroll_runs')->where('id', $id)->where('company_id', $companyId)->first();
        if (!$run) abort(404);

        $lines = DB::table('payroll_lines')
            ->join('employees', 'employees.id', '=', 'payroll_lines.employee_id')
            ->where('payroll_lines.payroll_run_id', $id)
            ->select(
                'payroll_lines.*',
                'employees.full_name',
                'employees.employee_id as emp_code',
                'employees.designation',
                'employees.iban',
                'employees.bank_name',
                'employees.wps_routing_code'
            )
            ->orderBy('employees.full_name')
            ->get();

        return view('payroll.show_run', compact('run', 'lines'));
    }
}