@extends('layouts.app')

@section('title', 'Data Management')

@section('content')

<div class="max-w-4xl space-y-6">

    <!-- Export Section -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h3 class="font-semibold text-gray-700">Export Data</h3>
            <p class="text-xs text-gray-400 mt-0.5">Export your data to CSV or XML for use in other software.</p>
        </div>
        <div class="p-5 space-y-4">

            <!-- Journal Entries -->
            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <div>
                    <p class="text-sm font-medium text-gray-700">Journal Entries</p>
                    <p class="text-xs text-gray-400">{{ number_format($journalCount) }} entries — all lines with account codes</p>
                </div>
                <div class="flex gap-2">
                    <a href="/data/export/journals?format=csv"
                        class="text-xs bg-gray-700 text-white px-3 py-1.5 rounded hover:bg-gray-800">📄 CSV</a>
                    <a href="/data/export/journals?format=xml"
                        class="text-xs bg-gray-700 text-white px-3 py-1.5 rounded hover:bg-gray-800">📋 XML</a>
                </div>
            </div>

            <!-- Chart of Accounts -->
            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <div>
                    <p class="text-sm font-medium text-gray-700">Chart of Accounts</p>
                    <p class="text-xs text-gray-400">{{ number_format($accountCount) }} accounts</p>
                </div>
                <div class="flex gap-2">
                    <a href="/data/export/accounts?format=csv"
                        class="text-xs bg-gray-700 text-white px-3 py-1.5 rounded hover:bg-gray-800">📄 CSV</a>
                    <a href="/data/export/accounts?format=xml"
                        class="text-xs bg-gray-700 text-white px-3 py-1.5 rounded hover:bg-gray-800">📋 XML</a>
                </div>
            </div>

            <!-- Customers -->
            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <div>
                    <p class="text-sm font-medium text-gray-700">Customers</p>
                    <p class="text-xs text-gray-400">{{ number_format($customerCount) }} customers</p>
                </div>
                <div class="flex gap-2">
                    <a href="/data/export/customers?format=csv"
                        class="text-xs bg-gray-700 text-white px-3 py-1.5 rounded hover:bg-gray-800">📄 CSV</a>
                    <a href="/data/export/customers?format=xml"
                        class="text-xs bg-gray-700 text-white px-3 py-1.5 rounded hover:bg-gray-800">📋 XML</a>
                </div>
            </div>

            <!-- Vendors -->
            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <div>
                    <p class="text-sm font-medium text-gray-700">Vendors</p>
                    <p class="text-xs text-gray-400">{{ number_format($vendorCount) }} vendors</p>
                </div>
                <div class="flex gap-2">
                    <a href="/data/export/vendors?format=csv"
                        class="text-xs bg-gray-700 text-white px-3 py-1.5 rounded hover:bg-gray-800">📄 CSV</a>
                    <a href="/data/export/vendors?format=xml"
                        class="text-xs bg-gray-700 text-white px-3 py-1.5 rounded hover:bg-gray-800">📋 XML</a>
                </div>
            </div>

            <!-- DB Dump -->
            <div class="flex items-center justify-between py-3">
                <div>
                    <p class="text-sm font-medium text-gray-700">Full Database Dump</p>
                    <p class="text-xs text-gray-400">SQL export of all company data — for backup or migration</p>
                </div>
                <div class="flex gap-2">
                    <a href="/data/export/dbdump"
                        class="text-xs bg-green-700 text-white px-3 py-1.5 rounded hover:bg-green-800">💾 Download SQL</a>
                </div>
            </div>

        </div>
    </div>

    <!-- Import Templates -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h3 class="font-semibold text-gray-700">Import Templates</h3>
            <p class="text-xs text-gray-400 mt-0.5">Download a template, fill it in, then upload below.</p>
        </div>
        <div class="p-5 space-y-3">
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                <div>
                    <p class="text-sm font-medium text-gray-700">Chart of Accounts Template</p>
                    <p class="text-xs text-gray-400">CSV template with required columns and sample data</p>
                </div>
                <a href="/data/templates/accounts"
                    class="text-xs border border-gray-300 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50">
                    ⬇ Download Template
                </a>
            </div>
            <div class="flex items-center justify-between py-2">
                <div>
                    <p class="text-sm font-medium text-gray-700">Journal Entries Template</p>
                    <p class="text-xs text-gray-400">CSV template — one row per journal line, grouped by entry number</p>
                </div>
                <a href="/data/templates/journals"
                    class="text-xs border border-gray-300 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50">
                    ⬇ Download Template
                </a>
            </div>
        </div>
    </div>

    <!-- Import Section -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h3 class="font-semibold text-gray-700">Import Data</h3>
            <p class="text-xs text-gray-400 mt-0.5">Upload a CSV file using the templates above. Existing records will be skipped.</p>
        </div>
        <div class="p-5 grid grid-cols-2 gap-6">

            <!-- Import Accounts -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-1">Import Accounts</h4>
                <p class="text-xs text-gray-400 mb-3">Accounts with duplicate codes will be skipped.</p>
                <form method="POST" action="/data/import/accounts" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="file" accept=".csv,.txt" required
                            class="w-full text-xs text-gray-600 border border-gray-200 rounded px-2 py-1.5">
                    </div>
                    <button type="submit"
                        class="w-full bg-green-700 text-white text-xs px-3 py-2 rounded hover:bg-green-800">
                        ⬆ Import Accounts
                    </button>
                </form>
            </div>

            <!-- Import Journals -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-1">Import Journal Entries</h4>
                <p class="text-xs text-gray-400 mb-3">Unbalanced entries will be skipped automatically.</p>
                <form method="POST" action="/data/import/journals" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="file" accept=".csv,.txt" required
                            class="w-full text-xs text-gray-600 border border-gray-200 rounded px-2 py-1.5">
                    </div>
                    <button type="submit"
                        class="w-full bg-green-700 text-white text-xs px-3 py-2 rounded hover:bg-green-800">
                        ⬆ Import Journals
                    </button>
                </form>
            </div>
                    
            <!-- Import Customers -->
			<div class="border border-gray-200 rounded-lg p-4">
				<h4 class="text-sm font-semibold text-gray-700 mb-1">Import Customers</h4>
				<p class="text-xs text-gray-400 mb-3">Customers with duplicate codes will be skipped. AR account matched by name automatically.</p>
				<form method="POST" action="/data/import/customers" enctype="multipart/form-data">
					@csrf
					<div class="mb-3">
						<input type="file" name="file" accept=".csv,.txt" required
							class="w-full text-xs text-gray-600 border border-gray-200 rounded px-2 py-1.5">
					</div>
					<button type="submit"
						class="w-full bg-green-700 text-white text-xs px-3 py-2 rounded hover:bg-green-800">
						⬆ Import Customers
					</button>
				</form>
			</div>

        </div>
    </div>

    <!-- Warning -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-xs text-yellow-800">
        <strong>Important:</strong> Import and export operations affect live company data.
        Always download a database backup before importing large datasets.
        Imports are permanent and cannot be automatically reversed.
    </div>

</div>

@endsection