<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PeriodHelper
{
    public static function getOrCreate(int $companyId, string $date): int
    {
        // Check if period exists
        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if ($period) return $period->id;

        $d = Carbon::parse($date);

        // Get or create fiscal year (Jan-Dec)
        $fy = DB::table('fiscal_years')
            ->where('company_id', $companyId)
            ->where('start_date', $d->year . '-01-01')
            ->first();

        if (!$fy) {
            $fyId = DB::table('fiscal_years')->insertGetId([
                'company_id' => $companyId,
                'name'       => 'FY ' . $d->year,
                'start_date' => $d->year . '-01-01',
                'end_date'   => $d->year . '-12-31',
                'status'     => 'OPEN',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $fyId = $fy->id;
        }

        // Create all 12 months for the year at once
        for ($month = 1; $month <= 12; $month++) {
            $monthDate  = Carbon::create($d->year, $month, 1);
            $existing   = DB::table('accounting_periods')
                ->where('company_id', $companyId)
                ->where('start_date', $monthDate->startOfMonth()->toDateString())
                ->exists();

            if (!$existing) {
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
        }

        // Return the period for the requested date
        return DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->value('id');
    }
}