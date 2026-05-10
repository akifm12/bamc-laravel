@extends('layouts.app')

@section('title', 'Journal Entries')

@section('content')

<div class="mb-4 flex items-center justify-between">
    <a href="/journals/create" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">➕ New Journal Entry</a>
</div>

<!-- Filters -->
<form method="GET" action="/journals" class="bg-white rounded-lg border border-gray-200 p-4 mb-4 flex gap-3 items-end">
    <div>
        <label class="text-xs text-gray-500 block mb-1">From</label>
        <input type="date" name="date_from" value="{{ $dateFrom }}"
            class="border border-gray-200 rounded px-3 py-1.5 text-sm">
    </div>
    <div>
        <label class="text-xs text-gray-500 block mb-1">To</label>
        <input type="date" name="date_to" value="{{ $dateTo }}"
            class="border border-gray-200 rounded px-3 py-1.5 text-sm">
    </div>
    <div>
        <label class="text-xs text-gray-500 block mb-1">Status</label>
        <select name="status" class="border border-gray-200 rounded px-3 py-1.5 text-sm">
            <option value="all"    {{ $status == 'all'    ? 'selected' : '' }}>All</option>
            <option value="draft"  {{ $status == 'draft'  ? 'selected' : '' }}>Draft</option>
            <option value="posted" {{ $status == 'posted' ? 'selected' : '' }}>Posted</option>
            <option value="void"   {{ $status == 'void'   ? 'selected' : '' }}>Void</option>
        </select>
    </div>
    <button type="submit" class="bg-gray-700 text-white text-sm px-4 py-1.5 rounded hover:bg-gray-800">Search</button>
</form>

<!-- Summary -->
<div class="grid grid-cols-3 gap-4 mb-4">
    <div class="bg-white rounded-lg border border-gray-200 p-3">
        <p class="text-xs text-gray-400">Entries</p>
        <p class="text-xl font-bold text-gray-800">{{ count($journals) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-3">
        <p class="text-xs text-gray-400">Total Debits</p>
        <p class="text-xl font-bold text-gray-800">AED {{ number_format($totalDebit, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-3">
        <p class="text-xs text-gray-400">Total Credits</p>
        <p class="text-xl font-bold text-gray-800">AED {{ number_format($totalCredit, 2) }}</p>
    </div>
</div>

<!-- Table -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-200 bg-gray-50">
                <th class="px-4 py-2 text-left">Number</th>
                <th class="px-4 py-2 text-left">Date</th>
                <th class="px-4 py-2 text-left">Description</th>
                <th class="px-4 py-2 text-left">Type</th>
                <th class="px-4 py-2 text-right">Debit</th>
                <th class="px-4 py-2 text-right">Credit</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($journals as $j)
            @php
                $status = strtolower(str_contains($j->status, '.') ? explode('.', $j->status)[1] : $j->status);
                $statusColor = match($status) {
                    'posted' => 'bg-green-100 text-green-700',
                    'draft'  => 'bg-yellow-100 text-yellow-700',
                    'void'   => 'bg-gray-100 text-gray-500',
                    default  => 'bg-gray-100 text-gray-500',
                };
            @endphp
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $j->entry_number }}</td>
                <td class="px-4 py-2 text-gray-600">{{ $j->entry_date }}</td>
                <td class="px-4 py-2 text-gray-800">{{ Str::limit($j->description, 50) }}</td>
                <td class="px-4 py-2 text-gray-500 text-xs">{{ $j->journal_type }}</td>
                <td class="px-4 py-2 text-right text-gray-800">{{ number_format($j->total_debit, 2) }}</td>
                <td class="px-4 py-2 text-right text-gray-800">{{ number_format($j->total_credit, 2) }}</td>
                <td class="px-4 py-2 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $statusColor }}">{{ strtoupper($status) }}</span>
                </td>
                <td class="px-4 py-2 text-right">
                    <a href="/journals/{{ $j->id }}" class="text-xs text-blue-500 hover:text-blue-700">View →</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-400">No journal entries found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection