@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400 uppercase font-semibold">Accounts</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['accounts'] ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">Chart of Accounts</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400 uppercase font-semibold">Total Journals</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['journals'] ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">All entries</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400 uppercase font-semibold">Posted</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['posted'] ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">Posted to GL</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-400 uppercase font-semibold">This Year</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['this_year'] ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ date('Y') }} entries</p>
    </div>
</div>

<div class="bg-white rounded-lg border border-gray-200 p-5">
    <h3 class="font-semibold text-gray-700 mb-3">Quick Actions</h3>
    <div class="flex gap-3">
        <a href="/journals/create" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">➕ New Journal Entry</a>
        <a href="/accounts" class="bg-white border border-gray-300 text-gray-700 text-sm px-4 py-2 rounded hover:bg-gray-50">📋 Chart of Accounts</a>
        <a href="/reports" class="bg-white border border-gray-300 text-gray-700 text-sm px-4 py-2 rounded hover:bg-gray-50">📈 Reports</a>
    </div>
</div>

@endsection