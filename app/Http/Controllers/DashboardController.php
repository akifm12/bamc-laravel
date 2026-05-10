<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Load companies for this user
        $companies = DB::table('companies')
            ->join('user_companies', 'companies.id', '=', 'user_companies.company_id')
            ->where('user_companies.user_id', $user->id)
            ->select('companies.id', 'companies.name')
            ->get()
            ->toArray();

        // If super admin, show all companies
        if ($user->is_super_admin) {
            $companies = DB::table('companies')
                ->select('id', 'name')
                ->get()
                ->toArray();
        }

        // Store companies in session
        session(['companies' => array_map(fn($c) => ['id' => $c->id, 'name' => $c->name], $companies)]);

        // Set default company if not set
        if (!session('company_id') && count($companies) > 0) {
            session([
                'company_id'   => $companies[0]->id,
                'company_name' => $companies[0]->name,
            ]);
        }

        $companyId = session('company_id');

        // Basic stats
        $stats = [];
        if ($companyId) {
            $stats['accounts']  = DB::table('accounts')->where('company_id', $companyId)->count();
            $stats['journals']  = DB::table('journal_entries')->where('company_id', $companyId)->where('is_deleted', false)->count();
            $stats['posted']    = DB::table('journal_entries')->where('company_id', $companyId)->where('status', 'POSTED')->where('is_deleted', false)->count();
            $stats['this_year'] = DB::table('journal_entries')
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->whereYear('entry_date', date('Y'))
                ->count();
        }

        return view('dashboard', compact('stats'));
    }
}