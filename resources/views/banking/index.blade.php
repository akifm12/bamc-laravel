@extends('layouts.app')

@section('title', 'Banking')

@section('content')

<div class="mb-4 flex items-center justify-between">
    <p class="text-sm text-gray-500">{{ count($accounts) }} bank accounts</p>
    <a href="/banking/create" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">➕ Add Bank Account</a>
</div>

<div class="grid grid-cols-3 gap-4">
    @forelse($accounts as $account)
    <a href="/banking/{{ $account->id }}"
        class="bg-white rounded-lg border border-gray-200 p-5 hover:border-green-300 hover:shadow-sm transition block">
        <div class="flex items-start justify-between mb-3">
            <div>
                <p class="font-semibold text-gray-800">{{ $account->account_name }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $account->bank_name }}</p>
            </div>
            @if($account->is_default)
                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Default</span>
            @endif
        </div>
        <div class="border-t border-gray-100 pt-3">
            <p class="text-xs text-gray-400">Current Balance</p>
            <p class="text-xl font-bold {{ $account->current_balance >= 0 ? 'text-gray-800' : 'text-red-600' }}">
                AED {{ number_format($account->current_balance, 2) }}
            </p>
        </div>
        @if($account->account_number)
            <p class="text-xs text-gray-400 mt-2">A/C: {{ $account->account_number }}</p>
        @endif
        @if($account->iban)
            <p class="text-xs text-gray-400">IBAN: {{ $account->iban }}</p>
        @endif
    </a>
    @empty
    <div class="col-span-3 bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-400">
        No bank accounts yet. <a href="/banking/create" class="text-green-600">Add one →</a>
    </div>
    @endforelse
</div>

@endsection