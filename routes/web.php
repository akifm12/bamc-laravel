<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\CompanySetupController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\VATController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\AutoLoginController;

Route::get('/auto-login', [AutoLoginController::class, 'login'])->name('auto.login');

Route::get('/', function () { return redirect('/dashboard'); });

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/company/switch', [CompanyController::class, 'switch'])->name('company.switch');
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::get('/journals', [JournalController::class, 'index'])->name('journals.index');
    Route::get('/journals/create', [JournalController::class, 'create'])->name('journals.create');
    Route::post('/journals', [JournalController::class, 'store'])->name('journals.store');
    Route::get('/journals/{id}', [JournalController::class, 'show'])->name('journals.show');
    Route::post('/journals/{id}/post', [JournalController::class, 'post'])->name('journals.post');
    Route::post('/journals/{id}/void', [JournalController::class, 'void'])->name('journals.void');
	Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
	Route::get('/reports/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial_balance');
	Route::get('/reports/pnl', [ReportController::class, 'pnl'])->name('reports.pnl');
	Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance_sheet');
	Route::get('/users', [UserController::class, 'index'])->name('users.index');
	Route::post('/users/{id}/activate', [UserController::class, 'activate'])->name('users.activate');
	Route::post('/users/{id}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
	Route::post('/users/{id}/make-admin', [UserController::class, 'makeAdmin'])->name('users.makeAdmin');
	Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
	Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
	Route::post('/users/{id}/update', [UserController::class, 'update'])->name('users.update');
	Route::get('/forgot-password', [PasswordResetController::class, 'showForm'])->name('password.request');
	Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
	Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
	Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
	Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
	Route::post('/users', [UserController::class, 'store'])->name('users.store');
	Route::get('/users/{id}/companies', [UserController::class, 'companies'])->name('users.companies');
	Route::post('/users/{id}/companies', [UserController::class, 'assignCompany'])->name('users.assignCompany');
	Route::delete('/users/{id}/companies/{companyId}', [UserController::class, 'removeCompany'])->name('users.removeCompany');
	Route::get('/reports/trial-balance/export', [ReportController::class, 'exportTrialBalance'])->name('reports.trial_balance.export');
	Route::get('/reports/pnl/export', [ReportController::class, 'exportPnl'])->name('reports.pnl.export');
	Route::get('/reports/balance-sheet/export', [ReportController::class, 'exportBalanceSheet'])->name('reports.balance_sheet.export');
// Customers
	Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
	Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
	Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
	Route::get('/customers/{id}', [CustomerController::class, 'show'])->name('customers.show');
	Route::get('/customers/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
	Route::put('/customers/{id}', [CustomerController::class, 'update'])->name('customers.update');

// Invoices
	Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
	Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
	Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
	Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
	Route::get('/invoices/{id}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
	Route::post('/invoices/{id}/approve', [InvoiceController::class, 'approve'])->name('invoices.approve');
	Route::post('/invoices/{id}/void', [InvoiceController::class, 'void'])->name('invoices.void');

// Vendors
	Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
	Route::get('/vendors/create', [VendorController::class, 'create'])->name('vendors.create');
	Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
	Route::get('/vendors/{id}', [VendorController::class, 'show'])->name('vendors.show');
	Route::get('/vendors/{id}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
	Route::put('/vendors/{id}', [VendorController::class, 'update'])->name('vendors.update');

// Bills
	Route::get('/bills', [BillController::class, 'index'])->name('bills.index');
	Route::get('/bills/create', [BillController::class, 'create'])->name('bills.create');
	Route::post('/bills', [BillController::class, 'store'])->name('bills.store');
	Route::get('/bills/{id}', [BillController::class, 'show'])->name('bills.show');
	Route::post('/bills/{id}/approve', [BillController::class, 'approve'])->name('bills.approve');
	Route::post('/bills/{id}/void', [BillController::class, 'void'])->name('bills.void');
	
	Route::get('/invoices/{id}/payment', [InvoiceController::class, 'paymentForm'])->name('invoices.payment');
	Route::post('/invoices/{id}/payment', [InvoiceController::class, 'recordPayment'])->name('invoices.recordPayment');
	Route::get('/bills/{id}/payment', [BillController::class, 'paymentForm'])->name('bills.payment');
	Route::post('/bills/{id}/payment', [BillController::class, 'recordPayment'])->name('bills.recordPayment');

	Route::get('/reports/aged-ar', [ReportController::class, 'agedAR'])->name('reports.aged_ar');
	Route::get('/reports/aged-ap', [ReportController::class, 'agedAP'])->name('reports.aged_ap');
	Route::get('/reports/cash-flow', [ReportController::class, 'cashFlow'])->name('reports.cash_flow');

	Route::get('/settings/company', [CompanySetupController::class, 'index'])->name('settings.company');
	Route::post('/settings/company/{id}', [CompanySetupController::class, 'update'])->name('settings.company.update');
	Route::get('/settings/fiscal-years', [CompanySetupController::class, 'fiscalYears'])->name('settings.fiscal_years');
	Route::post('/settings/fiscal-years', [CompanySetupController::class, 'storeFiscalYear'])->name('settings.fiscal_years.store');
	Route::post('/settings/fiscal-years/{id}/close', [CompanySetupController::class, 'closeFiscalYear'])->name('settings.fiscal_years.close');
	Route::post('/settings/fiscal-years/{id}/reopen', [CompanySetupController::class, 'reopenFiscalYear'])->name('settings.fiscal_years.reopen');

	Route::get('/banking', [BankAccountController::class, 'index'])->name('banking.index');
	Route::get('/banking/create', [BankAccountController::class, 'create'])->name('banking.create');
	Route::post('/banking', [BankAccountController::class, 'store'])->name('banking.store');
	Route::get('/banking/{id}', [BankAccountController::class, 'show'])->name('banking.show');
	Route::post('/banking/{id}/transaction', [BankAccountController::class, 'addTransaction'])->name('banking.transaction');

	Route::get('/ai', [AIController::class, 'index'])->name('ai.index');
	Route::post('/ai/query', [AIController::class, 'query'])->name('ai.query');

	Route::get('/vat', [VATController::class, 'index'])->name('vat.index');
	Route::get('/vat/create', [VATController::class, 'create'])->name('vat.create');
	Route::post('/vat', [VATController::class, 'store'])->name('vat.store');
	Route::get('/vat/{id}', [VATController::class, 'show'])->name('vat.show');
	Route::post('/vat/{id}/submit', [VATController::class, 'submit'])->name('vat.submit');

	Route::get('/assets', [FixedAssetController::class, 'index'])->name('assets.index');
	Route::get('/assets/create', [FixedAssetController::class, 'create'])->name('assets.create');
	Route::post('/assets', [FixedAssetController::class, 'store'])->name('assets.store');
	Route::get('/assets/{id}', [FixedAssetController::class, 'show'])->name('assets.show');
	Route::post('/assets/{id}/depreciate', [FixedAssetController::class, 'runDepreciation'])->name('assets.depreciate');
	Route::post('/assets/{id}/dispose', [FixedAssetController::class, 'dispose'])->name('assets.dispose');

	Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
	Route::get('/payroll/employees', [PayrollController::class, 'employees'])->name('payroll.employees');
	Route::get('/payroll/employees/create', [PayrollController::class, 'createEmployee'])->name('payroll.employees.create');
	Route::post('/payroll/employees', [PayrollController::class, 'storeEmployee'])->name('payroll.employees.store');
	Route::get('/payroll/employees/{id}', [PayrollController::class, 'showEmployee'])->name('payroll.employees.show');
	Route::get('/payroll/run', [PayrollController::class, 'runForm'])->name('payroll.run');
	Route::post('/payroll/run', [PayrollController::class, 'processPayroll'])->name('payroll.process');
	Route::get('/payroll/{id}', [PayrollController::class, 'showRun'])->name('payroll.show');

	Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
	Route::get('/inventory/items/create', [InventoryController::class, 'createItem'])->name('inventory.items.create');
	Route::post('/inventory/items', [InventoryController::class, 'storeItem'])->name('inventory.items.store');
	Route::get('/inventory/items/{id}', [InventoryController::class, 'showItem'])->name('inventory.items.show');
	Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
	Route::post('/inventory/movements', [InventoryController::class, 'recordMovement'])->name('inventory.movements.store');
	// Edit routes
	Route::get('/invoices/{id}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
	Route::put('/invoices/{id}', [InvoiceController::class, 'update'])->name('invoices.update');
	Route::get('/bills/{id}/edit', [BillController::class, 'edit'])->name('bills.edit');
	Route::put('/bills/{id}', [BillController::class, 'update'])->name('bills.update');
	Route::get('/journals/{id}/edit', [JournalController::class, 'edit'])->name('journals.edit');
	Route::put('/journals/{id}', [JournalController::class, 'update'])->name('journals.update');

	Route::get('/data', [DataController::class, 'index'])->name('data.index');
	Route::get('/data/export/journals', [DataController::class, 'exportJournals'])->name('data.export.journals');
	Route::get('/data/export/accounts', [DataController::class, 'exportAccounts'])->name('data.export.accounts');
	Route::get('/data/export/customers', [DataController::class, 'exportCustomers'])->name('data.export.customers');
	Route::get('/data/export/vendors', [DataController::class, 'exportVendors'])->name('data.export.vendors');
	Route::get('/data/export/dbdump', [DataController::class, 'dbDump'])->name('data.export.dbdump');
	Route::get('/data/templates/{type}', [DataController::class, 'downloadTemplate'])->name('data.template');
	Route::post('/data/import/accounts', [DataController::class, 'importAccounts'])->name('data.import.accounts');
	Route::post('/data/import/journals', [DataController::class, 'importJournals'])->name('data.import.journals');

	Route::get('/settings/companies', [CompanySetupController::class, 'companies'])->name('settings.companies');
	Route::get('/settings/companies/create', [CompanySetupController::class, 'createCompany'])->name('settings.companies.create');
	Route::post('/settings/companies', [CompanySetupController::class, 'storeCompany'])->name('settings.companies.store');
	Route::post('/data/import/customers', [DataController::class, 'importCustomers'])->name('data.import.customers');

	Route::get('/reports/ar-ledger', [ReportController::class, 'arLedger'])->name('reports.ar_ledger');
	Route::get('/customers/{id}/details', [CustomerController::class, 'details'])->name('customers.details');
	Route::post('/invoices/{id}/send-email', [InvoiceController::class, 'sendEmail'])->name('invoices.send_email');
	Route::get('/pending-approval', fn() => view('auth.pending-approval'))->name('pending-approval');

	Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
	Route::get('/accounts/create', [AccountController::class, 'create'])->name('accounts.create');
	Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
	Route::delete('/accounts/{id}', [AccountController::class, 'destroy'])->name('accounts.destroy');
	Route::get('/accounts/{id}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
	Route::post('/accounts/{id}/update', [AccountController::class, 'update'])->name('accounts.update');

});

require __DIR__.'/auth.php';
