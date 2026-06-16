<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - @yield('title', 'Dashboard')</title>
	<meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans text-gray-800 text-sm">

<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-56 bg-white border-r border-gray-200 flex flex-col flex-shrink-0">
        <div class="px-5 py-4 border-b border-gray-200">
            <h1 class="text-green-700 font-bold text-lg">🏦 BAMC</h1>
            <p class="text-gray-400 text-xs">Accounting System</p>
        </div>

<!-- Company selector -->
<div class="px-3 py-2 border-b border-gray-100">
    @php
        $user = auth()->user();
        if ($user->is_super_admin) {
            $sessionCompanies = \Illuminate\Support\Facades\DB::table('companies')
                ->select('id', 'name')->get()
                ->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray();
        } else {
            $sessionCompanies = \Illuminate\Support\Facades\DB::table('companies')
                ->join('user_companies', 'companies.id', '=', 'user_companies.company_id')
                ->where('user_companies.user_id', $user->id)
                ->select('companies.id', 'companies.name')->get()
                ->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray();
        }
        session(['companies' => $sessionCompanies]);
        if (!session('company_id') && count($sessionCompanies) > 0) {
            session(['company_id' => $sessionCompanies[0]['id'], 'company_name' => $sessionCompanies[0]['name']]);
        }
    @endphp
    <form method="POST" action="/company/switch">
        @csrf
        <select name="company_id" onchange="this.form.submit()"
            style="background-color:#fff; background-image:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 12 12%22><path fill=%22%23333%22 d=%22M6 8L1 3h10z%22/></svg>'); background-repeat:no-repeat; background-position:right 6px center; padding-right:22px; appearance:none; -webkit-appearance:none;"
            class="text-xs border border-gray-200 rounded px-2 py-1 w-full">
            @foreach($sessionCompanies as $company)
                <option value="{{ $company['id'] }}"
                    {{ session('company_id') == $company['id'] ? 'selected' : '' }}>
                    {{ $company['name'] }}
                </option>
            @endforeach
        </select>
    </form>
</div>

	<!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-2 py-3 space-y-1">

            <p class="text-xs font-bold text-gray-800 uppercase px-2 mt-3 mb-1">Overview</p>
            <a href="/dashboard" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">🏠 Dashboard</a>

            <p class="text-xs font-bold text-gray-800 uppercase px-2 mt-3 mb-1">Receivables</p>
            <a href="/customers" class="nav-link {{ request()->is('customers*') ? 'active' : '' }}">👤 Customers</a>
            <a href="/invoices" class="nav-link {{ request()->is('invoices*') ? 'active' : '' }}">🧾 Invoices</a>

            <p class="text-xs font-bold text-gray-800 uppercase px-2 mt-3 mb-1">Payables</p>
            <a href="/vendors" class="nav-link {{ request()->is('vendors*') ? 'active' : '' }}">🏭 Vendors</a>
            <a href="/bills" class="nav-link {{ request()->is('bills*') ? 'active' : '' }}">📋 Bills</a>
                        
            <p class="text-xs font-bold text-gray-800 uppercase px-2 mt-3 mb-1">Banking</p>
            <a href="/banking" class="nav-link {{ request()->is('banking*') ? 'active' : '' }}">🏦 Bank Accounts</a>

            <p class="text-xs font-bold text-gray-800 uppercase px-2 mt-3 mb-1">Accounting</p>
            <a href="/accounts" class="nav-link {{ request()->is('accounts*') ? 'active' : '' }}">📋 Chart of Accounts</a>
            <a href="/journals" class="nav-link {{ request()->is('journals*') ? 'active' : '' }}">📓 Journal Entries</a>

            <p class="text-xs font-bold text-gray-800 uppercase px-2 mt-3 mb-1">Reports</p>
            <a href="/reports" class="nav-link {{ request()->is('reports*') ? 'active' : '' }}">📈 Financial Reports</a>
            <a href="/ai" class="nav-link {{ request()->is('ai*') ? 'active' : '' }}">🤖 AI Analyst</a>
             
            <p class="text-xs font-bold text-gray-800 uppercase px-2 mt-3 mb-1">Operations</p>
            <a href="/assets" class="nav-link {{ request()->is('assets*') ? 'active' : '' }}">🏗️ Fixed Assets</a>      
			<a href="/payroll" class="nav-link {{ request()->is('payroll*') ? 'active' : '' }}">👷 Payroll</a>
            <a href="/inventory" class="nav-link {{ request()->is('inventory*') ? 'active' : '' }}">📦 Inventory</a>
                        
            <p class="text-xs font-bold text-gray-800 uppercase px-2 mt-3 mb-1">Tax</p>
            <a href="/vat" class="nav-link {{ request()->is('vat*') ? 'active' : '' }}">🧾 VAT Returns</a>

            <p class="text-xs font-bold text-gray-800 uppercase px-2 mt-3 mb-1">Settings</p>
            <a href="/users" class="nav-link {{ request()->is('users*') ? 'active' : '' }}">👥 Users</a>
            <a href="/settings/companies" class="nav-link {{ request()->is('settings/companies*') ? 'active' : '' }}">🏢 Companies</a>
            <a href="/settings/fiscal-years" class="nav-link {{ request()->is('settings/fiscal*') ? 'active' : '' }}">📅 Fiscal Years</a>
            <a href="/data" class="nav-link {{ request()->is('data*') ? 'active' : '' }}">🗄️ Data Management</a>
        </nav>

        <!-- User + Logout -->
        <div class="px-4 py-3 border-t border-gray-200">
            <p class="text-xs font-medium text-gray-700">{{ auth()->user()->full_name }}</p>
            <form method="POST" action="/logout">
                @csrf
                <button class="text-xs text-red-500 hover:text-red-700 mt-1">🚪 Logout</button>
            </form>
        </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top bar -->
        <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
            <h2 class="font-semibold text-gray-700">@yield('title', 'Dashboard')</h2>
            <span class="text-xs text-gray-400">{{ session('company_name', 'No company selected') }}</span>
        </header>

        <!-- Page content -->
        <main class="flex-1 overflow-y-auto p-6">
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded text-sm">
                    {{ session('error') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

<style>
.nav-link {
    display: block;
    padding: 3px 25px;
    border-radius: 6px;
    color: #4b5563;
    font-size: 13px;
    text-decoration: none;
    transition: background 0.15s;
}
.nav-link:hover { background: #f3f4f6; color: #111827; }
.nav-link.active { background: #f0fdf4; color: #15803d; font-weight: 600; }
</style>

</body>
</html>