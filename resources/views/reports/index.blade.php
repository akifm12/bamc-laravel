@extends('layouts.app')

@section('title', 'Financial Reports')

@section('content')

<div class="grid grid-cols-3 gap-4">

    <a href="/reports/trial-balance" class="bg-white rounded-lg border border-gray-200 p-5 hover:border-green-300 hover:shadow-sm transition">
        <div class="text-2xl mb-2">⚖️</div>
        <h3 class="font-semibold text-gray-800">Trial Balance</h3>
        <p class="text-xs text-gray-400 mt-1">View debit and credit balances for all accounts</p>
    </a>

    <a href="/reports/pnl" class="bg-white rounded-lg border border-gray-200 p-5 hover:border-green-300 hover:shadow-sm transition">
        <div class="text-2xl mb-2">📊</div>
        <h3 class="font-semibold text-gray-800">Profit & Loss</h3>
        <p class="text-xs text-gray-400 mt-1">Revenue, expenses and net profit for a period</p>
    </a>

    <a href="/reports/balance-sheet" class="bg-white rounded-lg border border-gray-200 p-5 hover:border-green-300 hover:shadow-sm transition">
        <div class="text-2xl mb-2">🏦</div>
        <h3 class="font-semibold text-gray-800">Balance Sheet</h3>
        <p class="text-xs text-gray-400 mt-1">Assets, liabilities and equity as of a date</p>
    </a>
    
    <a href="/reports/aged-ar" class="bg-white rounded-lg border border-gray-200 p-5 hover:border-green-300 hover:shadow-sm transition">
        <div class="text-2xl mb-2">👥</div>
        <h3 class="font-semibold text-gray-800">Aged Receivables</h3>
        <p class="text-xs text-gray-400 mt-1">Outstanding invoices by customer and age bracket</p>
    </a>

    <a href="/reports/aged-ap" class="bg-white rounded-lg border border-gray-200 p-5 hover:border-green-300 hover:shadow-sm transition">
        <div class="text-2xl mb-2">🏭</div>
        <h3 class="font-semibold text-gray-800">Aged Payables</h3>
        <p class="text-xs text-gray-400 mt-1">Outstanding bills by vendor and age bracket</p>
    </a>

    <a href="/reports/cash-flow" class="bg-white rounded-lg border border-gray-200 p-5 hover:border-green-300 hover:shadow-sm transition">
        <div class="text-2xl mb-2">💰</div>
        <h3 class="font-semibold text-gray-800">Cash Flow</h3>
        <p class="text-xs text-gray-400 mt-1">Operating, investing and financing cash movements</p>
    </a>
    <a href="/reports/ar-ledger" class="bg-white rounded-lg border border-gray-200 p-5 hover:border-green-300 hover:shadow-sm transition">
        <div class="text-2xl mb-2">📒</div>
        <h3 class="font-semibold text-gray-800">AR Ledger</h3>
        <p class="text-xs text-gray-400 mt-1">GL-based receivables by client with full transaction history</p>
    </a>

    <a href="/reports/vat-ledger" class="bg-white rounded-lg border border-gray-200 p-5 hover:border-green-300 hover:shadow-sm transition">
        <div class="text-2xl mb-2">🧾</div>
        <h3 class="font-semibold text-gray-800">VAT Ledger</h3>
        <p class="text-xs text-gray-400 mt-1">Output VAT collected, input VAT paid, and settlements to FTA</p>
    </a>

</div>

@endsection