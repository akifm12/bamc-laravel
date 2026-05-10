<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        if (!auth()->user()->is_super_admin) {
            abort(403, 'Super admin only.');
        }

        $users = DB::table('users')
            ->orderBy('created_at', 'desc')
            ->get();

        $companies = DB::table('companies')->get();

        $userCompanies = DB::table('user_companies')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->select('user_companies.user_id', 'companies.name', 'user_companies.role')
            ->get()
            ->groupBy('user_id');

        return view('users.index', compact('users', 'companies', 'userCompanies'));
    }

    public function activate($id)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        DB::table('users')->where('id', $id)->update(['is_active' => true, 'updated_at' => now()]);
        return back()->with('success', 'User activated.');
    }

    public function deactivate($id)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        if ($id == auth()->id()) return back()->with('error', 'Cannot deactivate yourself.');
        DB::table('users')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);
        return back()->with('success', 'User deactivated.');
    }

    public function makeAdmin($id)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        DB::table('users')->where('id', $id)->update(['is_super_admin' => true, 'updated_at' => now()]);
        return back()->with('success', 'User promoted to super admin.');
    }

    public function destroy($id)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        if ($id == auth()->id()) return back()->with('error', 'Cannot delete yourself.');
        DB::table('user_companies')->where('user_id', $id)->delete();
        DB::table('password_reset_tokens')->where('user_id', $id)->delete();
        DB::table('users')->where('id', $id)->delete();
        return back()->with('success', 'User deleted.');
    }
	
	public function create()
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $companies = DB::table('companies')->get();
        return view('users.create', compact('companies'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->is_super_admin) abort(403);

        $request->validate([
            'full_name' => 'required|string',
            'email'     => 'required|email|unique:users,email',
            'username'  => 'required|string|unique:users,username',
            'password'  => 'required|min:8|confirmed',
        ]);

        $hash = '$2y$' . substr(password_hash($request->password, PASSWORD_BCRYPT), 4);

        $userId = DB::table('users')->insertGetId([
            'full_name'       => $request->full_name,
            'email'           => $request->email,
            'username'        => $request->username,
            'hashed_password' => $hash,
            'is_active'       => true,
            'is_super_admin'  => $request->has('is_super_admin'),
            'failed_logins'   => 0,
            'mfa_enabled'     => false,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        if ($request->company_id) {
            DB::table('user_companies')->insert([
                'user_id'    => $userId,
                'company_id' => $request->company_id,
                'role'       => $request->role ?? 'VIEWER',
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect('/users')->with('success', 'User created successfully.');
    }

    public function assignCompany(Request $request, $id)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        $exists = DB::table('user_companies')
            ->where('user_id', $id)
            ->where('company_id', $request->company_id)
            ->exists();
        if (!$exists) {
            DB::table('user_companies')->insert([
                'user_id'    => $id,
                'company_id' => $request->company_id,
                'role'       => $request->role ?? 'VIEWER',
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return back()->with('success', 'Company assigned.');
    }

    public function removeCompany($id, $companyId)
    {
        if (!auth()->user()->is_super_admin) abort(403);
        DB::table('user_companies')
            ->where('user_id', $id)
            ->where('company_id', $companyId)
            ->delete();
        return back()->with('success', 'Company removed.');
    }  
    public function edit($id)
{
    if (!auth()->user()->is_super_admin) abort(403);
    $user      = DB::table('users')->where('id', $id)->first();
    $companies = DB::table('companies')->get();
    $assigned  = DB::table('user_companies')
        ->where('user_id', $id)
        ->pluck('company_id')
        ->toArray();
    return view('users.edit', compact('user', 'companies', 'assigned'));
}

public function update(Request $request, $id)
{
    if (!auth()->user()->is_super_admin) abort(403);

    $request->validate([
        'full_name' => 'required|string|max:255',
        'username'  => 'required|string|max:255|unique:users,username,' . $id,
        'email'     => 'required|email|max:255|unique:users,email,' . $id,
    ]);

    DB::table('users')->where('id', $id)->update([
        'full_name'     => $request->full_name,
        'username'      => $request->username,
        'email'         => $request->email,
        'is_active'     => $request->has('is_active'),
        'is_super_admin'=> $request->has('is_super_admin'),
        'updated_at'    => now(),
    ]);

    // Update password only if provided
    if ($request->filled('password')) {
        $request->validate(['password' => 'min:8|confirmed']);
        DB::table('users')->where('id', $id)->update([
            'hashed_password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'updated_at'      => now(),
        ]);
    }

    // Sync company assignments
    DB::table('user_companies')->where('user_id', $id)->delete();
    if ($request->has('companies')) {
        foreach ($request->companies as $companyId) {
            DB::table('user_companies')->insert([
                'user_id'    => $id,
                'company_id' => $companyId,
                'role'       => $request->input("role_{$companyId}", 'VIEWER'),
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    return redirect('/users')->with('success', 'User updated successfully.');
}
}