<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CompanyController extends Controller
{
    public function switch(Request $request)
    {
        $user      = auth()->user();
        $companyId = $request->input('company_id');

        // Verify user has access to this company
        if (!$user->is_super_admin) {
            $hasAccess = DB::table('user_companies')
                ->where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->exists();
            if (!$hasAccess) abort(403);
        }

        $company = DB::table('companies')->where('id', $companyId)->first();
        if ($company) {
            session(['company_id' => $company->id, 'company_name' => $company->name]);
        }

        return redirect()->back();
    }
}