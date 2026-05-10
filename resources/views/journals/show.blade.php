@extends('layouts.app')

@section('title', 'Journal Entry — ' . $journal->entry_number)

@section('content')

<div class="max-w-4xl">

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $journal->entry_number }}</h2>
                <p class="text-gray-500 text-sm mt-1">{{ $journal->description }}</p>
                @if($journal->reference)
                    <p class="text-xs text-gray-400 mt-1">Ref: {{ $journal->reference }}</p>
                @endif
            </div>
            <div class="text-right">
                @php
                    $status = strtolower(str_contains($journal->status, '.') ? explode('.', $journal->status)[1] : $journal->status);
                    $statusColor = match($status) {
                        'posted' => 'bg-green-100 text-green-700',
                        'draft'  => 'bg-yellow-100 text-yellow-700',
                        'void'   => 'bg-gray-100 text-gray-500',
                        default  => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <span class="text-xs px-3 py-1 rounded-full {{ $statusColor }}">{{ strtoupper($status) }}</span>
                <p class="text-sm text-gray-500 mt-2">{{ $journal->entry_date }}</p>
                <p class="text-xs text-gray-400">{{ $journal->journal_type }}</p>
            </div>
        </div>
    </div>

    <!-- Lines -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-200 bg-gray-50">
                    <th class="px-4 py-2 text-left w-8">#</th>
                    <th class="px-4 py-2 text-left w-28">Code</th>
                    <th class="px-4 py-2 text-left">Account</th>
                    <th class="px-4 py-2 text-left">Description</th>
                    <th class="px-4 py-2 text-right w-36">Debit</th>
                    <th class="px-4 py-2 text-right w-36">Credit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $line)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 text-gray-400 text-xs">{{ $line->line_number }}</td>
                    <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $line->code }}</td>
                    <td class="px-4 py-2 font-medium text-gray-800">{{ $line->name }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $line->description ?? '—' }}</td>
                    <td class="px-4 py-2 text-right text-gray-800">
                        {{ $line->debit_amount > 0 ? number_format($line->debit_amount, 2) : '—' }}
                    </td>
                    <td class="px-4 py-2 text-right text-gray-800">
                        {{ $line->credit_amount > 0 ? number_format($line->credit_amount, 2) : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t border-gray-200 font-semibold">
                    <td colspan="4" class="px-4 py-2 text-right text-xs text-gray-500">TOTALS</td>
                    <td class="px-4 py-2 text-right">AED {{ number_format($journal->total_debit, 2) }}</td>
                    <td class="px-4 py-2 text-right">AED {{ number_format($journal->total_credit, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="px-4 py-2 text-center text-xs">
                        @if(abs($journal->total_debit - $journal->total_credit) < 0.01)
                            <span class="text-green-600 font-semibold">Entry balances</span>
                        @else
                            <span class="text-red-600 font-semibold">Out of balance</span>
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Actions -->
    <div class="flex gap-3">
        @if($status === 'draft' || auth()->user()->is_super_admin)
            <a href="/journals/{{ $journal->id }}/edit"
                class="text-sm border border-gray-200 text-gray-600 px-4 py-2 rounded hover:bg-gray-50">
                ✏️ Edit
            </a>
            <form method="POST" action="/journals/{{ $journal->id }}/post">
                @csrf
                <button type="submit" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">
                    ✅ Post to GL
                </button>
            </form>
        @endif
        @if(auth()->user()->is_super_admin && $status !== 'void')
            <form method="POST" action="/journals/{{ $journal->id }}/void">
                @csrf
                <button type="submit"
                    onclick="return confirm('Are you sure you want to void this entry?')"
                    class="bg-red-600 text-white text-sm px-4 py-2 rounded hover:bg-red-700">
                    🔴 Void Entry
                </button>
            </form>
        @endif
        <a href="/journals" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">← Back to Journals</a>
    </div>

</div>

@endsection