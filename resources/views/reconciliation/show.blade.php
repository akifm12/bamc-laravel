@extends('layouts.app')
@section('title', 'Reconcile — ' . ($account->name ?? ''))
@section('content')

@php
    $isLocked   = $recon->status === 'LOCKED';
    $totalLines = $stmtLines->count();
    $matchedCnt = $stmtLines->where('matched', true)->count();
    $unmatchedStmt = $stmtLines->where('matched', false);
    $matchedStmt   = $stmtLines->where('matched', true);
    $pct = $totalLines > 0 ? round($matchedCnt / $totalLines * 100) : 0;
    $diffColor = abs($difference) < 0.01 ? '#16a34a' : '#dc2626';
@endphp

<!-- Breadcrumb -->
<div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
    <a href="/reconciliation" class="hover:text-gray-700">Bank Reconciliation</a>
    <span>›</span>
    <span class="text-gray-700">{{ $account->name }}</span>
    <span class="text-gray-400">·</span>
    <span class="text-gray-500">
        {{ \Carbon\Carbon::parse($recon->period_from)->format('d M') }}
        – {{ \Carbon\Carbon::parse($recon->period_to)->format('d M Y') }}
    </span>
    @if($isLocked)
    <span class="ml-2 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">🔒 Locked</span>
    @endif
</div>

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded text-sm">
    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
</div>
@endif

<!-- Summary strip -->
<div class="grid grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
        <p class="text-xs text-gray-400 mb-0.5">Statement Closing</p>
        <p class="text-lg font-bold text-gray-800">AED {{ number_format($stmtBalance, 2) }}</p>
        <p class="text-xs text-gray-400">As per bank statement</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
        <p class="text-xs text-gray-400 mb-0.5">Book Balance</p>
        <p class="text-lg font-bold text-gray-800">AED {{ number_format($bookBalance, 2) }}</p>
        <p class="text-xs text-gray-400">GL as of {{ \Carbon\Carbon::parse($recon->period_to)->format('d M Y') }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
        <p class="text-xs text-gray-400 mb-0.5">Difference</p>
        <p class="text-lg font-bold" style="color:{{ $diffColor }}">
            AED {{ number_format(abs($difference), 2) }}
            @if(abs($difference) < 0.01) ✅ @endif
        </p>
        <p class="text-xs text-gray-400">{{ abs($difference) < 0.01 ? 'Fully reconciled' : ($difference > 0 ? 'Statement > Books' : 'Books > Statement') }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
        <p class="text-xs text-gray-400 mb-0.5">Progress</p>
        <div class="flex items-center gap-2 mt-1">
            <div class="flex-1 bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full {{ $pct == 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width:{{ $pct }}%"></div>
            </div>
            <span class="text-sm font-semibold text-gray-700">{{ $pct }}%</span>
        </div>
        <p class="text-xs text-gray-400 mt-1">{{ $matchedCnt }} / {{ $totalLines }} lines matched</p>
    </div>
</div>

<!-- Lock button -->
@if(!$isLocked)
<div class="mb-4 flex items-center justify-end gap-3">
    @if(abs($difference) < 0.01)
    <form method="POST" action="/reconciliation/{{ $recon->id }}/lock">
        @csrf
        <button type="submit"
            onclick="return confirm('Lock this reconciliation? This cannot be undone.')"
            class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">
            🔒 Lock Reconciliation
        </button>
    </form>
    @else
    <span class="text-xs text-gray-400">Resolve all differences to unlock the Lock button</span>
    <button disabled class="bg-gray-200 text-gray-400 text-sm px-4 py-2 rounded cursor-not-allowed">
        🔒 Lock Reconciliation
    </button>
    @endif
</div>
@endif

<!-- Main workspace -->
<div id="workspace" class="grid grid-cols-2 gap-4">

    <!-- LEFT: Unmatched Statement Lines -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden flex flex-col">
        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <div>
                <span class="text-sm font-semibold text-gray-700">Bank Statement</span>
                <span class="ml-2 text-xs bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded-full">
                    {{ $unmatchedStmt->count() }} unmatched
                </span>
            </div>
            <span class="text-xs text-gray-400">Select items, then match against book entries →</span>
        </div>

        @if($unmatchedStmt->isEmpty())
        <div class="px-4 py-8 text-center text-gray-400 text-sm">
            ✅ All statement lines matched
        </div>
        @else
        <form method="POST" action="/reconciliation/{{ $recon->id }}/match" id="match-form">
            @csrf
            <div class="overflow-y-auto" style="max-height:420px">
                <table class="w-full text-xs">
                    <thead class="sticky top-0 bg-gray-50 border-b border-gray-100">
                        <tr class="text-gray-400 uppercase">
                            <th class="px-3 py-2 w-6"></th>
                            <th class="px-3 py-2 text-left">Date</th>
                            <th class="px-3 py-2 text-left">Description</th>
                            <th class="px-3 py-2 text-right">Out (Dr)</th>
                            <th class="px-3 py-2 text-right">In (Cr)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unmatchedStmt as $sl)
                        <tr class="border-b border-gray-50 hover:bg-blue-50 cursor-pointer stmt-row"
                            onclick="toggleStmt({{ $sl->id }}, this)">
                            <td class="px-3 py-2">
                                <input type="checkbox" name="stmt_ids[]" value="{{ $sl->id }}"
                                    id="stmt_{{ $sl->id }}"
                                    class="stmt-cb"
                                    onclick="event.stopPropagation()"
                                    onchange="updateTotals()">
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-600">
                                {{ \Carbon\Carbon::parse($sl->transaction_date)->format('d M') }}
                            </td>
                            <td class="px-3 py-2 text-gray-600 max-w-0" style="max-width:120px">
                                <div class="truncate" title="{{ $sl->description }}">{{ $sl->description ?: '—' }}</div>
                                @if($sl->reference)<div class="text-gray-400 truncate">{{ $sl->reference }}</div>@endif
                            </td>
                            <td class="px-3 py-2 text-right {{ $sl->debit_amount > 0 ? 'text-red-600 font-medium' : 'text-gray-300' }}">
                                {{ $sl->debit_amount > 0 ? number_format($sl->debit_amount, 2) : '—' }}
                            </td>
                            <td class="px-3 py-2 text-right {{ $sl->credit_amount > 0 ? 'text-green-600 font-medium' : 'text-gray-300' }}">
                                {{ $sl->credit_amount > 0 ? number_format($sl->credit_amount, 2) : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Running totals + match button -->
            <div class="px-4 py-3 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                <div class="text-xs text-gray-500">
                    Selected: <span id="stmt-total" class="font-semibold text-gray-700">AED 0.00</span>
                </div>
                <div class="flex items-center gap-2">
                    <span id="match-status" class="text-xs text-gray-400"></span>
                    <button type="submit" id="match-btn" disabled
                        class="text-xs bg-green-700 text-white px-3 py-1.5 rounded disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed">
                        ✓ Match Selected
                    </button>
                </div>
            </div>
        </form>
        @endif

        @if(!$isLocked && $unmatchedStmt->isNotEmpty())
        <!-- Create Journal Entry for bank-only lines -->
        <div class="border-t border-gray-100 px-4 py-2">
            <button onclick="document.getElementById('create-entry-panel').classList.toggle('hidden')"
                class="text-xs text-blue-500 hover:text-blue-700">
                ➕ Create journal entry for a bank-only line (charges, interest, etc.)
            </button>
        </div>
        <div id="create-entry-panel" class="hidden border-t border-gray-100 px-4 py-3 bg-blue-50">
            <form method="POST" action="/reconciliation/{{ $recon->id }}/create-entry">
                @csrf
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="col-span-2">
                        <label class="text-gray-500 block mb-1">Statement Line</label>
                        <select name="stmt_id" required class="w-full border border-gray-200 rounded px-2 py-1 text-xs">
                            <option value="">— Pick an unmatched line —</option>
                            @foreach($unmatchedStmt as $sl)
                            <option value="{{ $sl->id }}">
                                {{ \Carbon\Carbon::parse($sl->transaction_date)->format('d M') }}
                                · {{ Str::limit($sl->description ?? 'No desc', 30) }}
                                · AED {{ number_format(max($sl->debit_amount, $sl->credit_amount), 2) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-gray-500 block mb-1">Debit Account</label>
                        @php
                            $allAccts = \Illuminate\Support\Facades\DB::table('accounts')
                                ->where('company_id', session('company_id'))
                                ->where('is_active', true)
                                ->orderBy('name')->get();
                        @endphp
                        <select name="debit_account_id" required class="w-full border border-gray-200 rounded px-2 py-1 text-xs">
                            <option value="">— Account —</option>
                            @foreach($allAccts as $ac)
                            <option value="{{ $ac->id }}">{{ $ac->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-gray-500 block mb-1">Credit Account</label>
                        <select name="credit_account_id" required class="w-full border border-gray-200 rounded px-2 py-1 text-xs">
                            <option value="">— Account —</option>
                            @foreach($allAccts as $ac)
                            <option value="{{ $ac->id }}" {{ $ac->id == $recon->account_id ? 'selected' : '' }}>
                                {{ $ac->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-gray-500 block mb-1">Date</label>
                        <input type="date" name="entry_date" value="{{ date('Y-m-d') }}"
                            class="w-full border border-gray-200 rounded px-2 py-1 text-xs">
                    </div>
                    <div>
                        <label class="text-gray-500 block mb-1">Description</label>
                        <input type="text" name="description" placeholder="Bank charge / interest..."
                            class="w-full border border-gray-200 rounded px-2 py-1 text-xs">
                    </div>
                    <div class="col-span-2 flex justify-end">
                        <button type="submit" class="text-xs bg-blue-700 text-white px-3 py-1.5 rounded hover:bg-blue-800">
                            Create Entry & Match
                        </button>
                    </div>
                </div>
            </form>
        </div>
        @endif
    </div>

    <!-- RIGHT: Unmatched Journal Lines -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden flex flex-col">
        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <div>
                <span class="text-sm font-semibold text-gray-700">Book Entries</span>
                <span class="ml-2 text-xs bg-purple-100 text-purple-600 px-1.5 py-0.5 rounded-full">
                    {{ $journalLines->count() }} unmatched
                </span>
            </div>
            <span class="text-xs text-gray-400">← Check items to match with statement</span>
        </div>

        @if($journalLines->isEmpty())
        <div class="px-4 py-8 text-center text-gray-400 text-sm">
            ✅ No unmatched journal entries in this period
        </div>
        @else
        <div class="overflow-y-auto" style="max-height:460px">
            <table class="w-full text-xs">
                <thead class="sticky top-0 bg-gray-50 border-b border-gray-100">
                    <tr class="text-gray-400 uppercase">
                        <th class="px-3 py-2 w-6"></th>
                        <th class="px-3 py-2 text-left">Date</th>
                        <th class="px-3 py-2 text-left">Entry</th>
                        <th class="px-3 py-2 text-right">Debit</th>
                        <th class="px-3 py-2 text-right">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($journalLines as $jl)
                    <tr class="border-b border-gray-50 hover:bg-purple-50 cursor-pointer jl-row"
                        onclick="toggleJl({{ $jl->id }}, this)">
                        <td class="px-3 py-2">
                            <input type="checkbox" form="match-form" name="jl_ids[]" value="{{ $jl->id }}"
                                id="jl_{{ $jl->id }}"
                                class="jl-cb"
                                onclick="event.stopPropagation()"
                                onchange="updateTotals()">
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-gray-600">
                            {{ \Carbon\Carbon::parse($jl->entry_date)->format('d M') }}
                        </td>
                        <td class="px-3 py-2 text-gray-600" style="max-width:130px">
                            <div class="truncate font-medium" title="{{ $jl->entry_desc }}">
                                {{ $jl->entry_number }}
                            </div>
                            <div class="truncate text-gray-400" title="{{ $jl->entry_desc }}">
                                {{ Str::limit($jl->entry_desc ?? '', 28) }}
                            </div>
                        </td>
                        <td class="px-3 py-2 text-right {{ $jl->debit_amount > 0 ? 'text-blue-600 font-medium' : 'text-gray-300' }}">
                            {{ $jl->debit_amount > 0 ? number_format($jl->debit_amount, 2) : '—' }}
                        </td>
                        <td class="px-3 py-2 text-right {{ $jl->credit_amount > 0 ? 'text-orange-600 font-medium' : 'text-gray-300' }}">
                            {{ $jl->credit_amount > 0 ? number_format($jl->credit_amount, 2) : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100 bg-gray-50 flex justify-end">
            <div class="text-xs text-gray-500">
                Selected: <span id="jl-total" class="font-semibold text-gray-700">AED 0.00</span>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Matched Lines (collapsible) -->
@if($matchedStmt->isNotEmpty())
<div class="mt-5">
    <button onclick="document.getElementById('matched-section').classList.toggle('hidden')"
        class="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800 mb-2">
        <span>✅ Matched Lines ({{ $matchedStmt->count() }})</span>
        <span class="text-xs text-gray-400">click to toggle</span>
    </button>
    <div id="matched-section" class="hidden bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="w-full text-xs">
            <thead>
                <tr class="text-gray-400 uppercase border-b border-gray-100 bg-gray-50">
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-left">Statement Description</th>
                    <th class="px-4 py-2 text-right">Out (Dr)</th>
                    <th class="px-4 py-2 text-right">In (Cr)</th>
                    <th class="px-4 py-2 text-left">Matched Journal Entry</th>
                    @if(!$isLocked)<th class="px-4 py-2"></th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach($matchedStmt as $sl)
                @php
                    $jlInfo = null;
                    if ($sl->journal_line_id) {
                        $jlInfo = \Illuminate\Support\Facades\DB::table('journal_lines as jl')
                            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
                            ->where('jl.id', $sl->journal_line_id)
                            ->select('je.entry_number', 'je.description as entry_desc', 'je.entry_date')
                            ->first();
                    }
                @endphp
                <tr class="border-b border-gray-50 bg-green-50">
                    <td class="px-4 py-2 text-gray-600">{{ \Carbon\Carbon::parse($sl->transaction_date)->format('d M Y') }}</td>
                    <td class="px-4 py-2 text-gray-600">
                        {{ $sl->description ?: '—' }}
                        @if($sl->reference)<span class="text-gray-400"> · {{ $sl->reference }}</span>@endif
                    </td>
                    <td class="px-4 py-2 text-right {{ $sl->debit_amount > 0 ? 'text-red-600' : 'text-gray-300' }}">
                        {{ $sl->debit_amount > 0 ? number_format($sl->debit_amount, 2) : '—' }}
                    </td>
                    <td class="px-4 py-2 text-right {{ $sl->credit_amount > 0 ? 'text-green-600' : 'text-gray-300' }}">
                        {{ $sl->credit_amount > 0 ? number_format($sl->credit_amount, 2) : '—' }}
                    </td>
                    <td class="px-4 py-2 text-gray-600">
                        @if($jlInfo)
                        <span class="font-medium">{{ $jlInfo->entry_number }}</span>
                        <span class="text-gray-400 ml-1">{{ Str::limit($jlInfo->entry_desc ?? '', 25) }}</span>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    @if(!$isLocked)
                    <td class="px-4 py-2 text-right">
                        <form method="POST" action="/reconciliation/{{ $recon->id }}/unmatch/{{ $sl->id }}">
                            @csrf
                            <button type="submit" class="text-xs text-red-400 hover:text-red-600">unmatch</button>
                        </form>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@push('scripts')
<script>
function toggleStmt(id, row) {
    const cb = document.getElementById('stmt_' + id);
    cb.checked = !cb.checked;
    row.classList.toggle('bg-blue-100', cb.checked);
    updateTotals();
}
function toggleJl(id, row) {
    const cb = document.getElementById('jl_' + id);
    cb.checked = !cb.checked;
    row.classList.toggle('bg-purple-100', cb.checked);
    updateTotals();
}

// Store net amounts as data attributes
document.querySelectorAll('.stmt-row').forEach(row => {
    const cb = row.querySelector('.stmt-cb');
    if (!cb) return;
    // read from cells: col 3=debit(out), col 4=credit(in)
    const cells = row.querySelectorAll('td');
    const debit  = parseFloat(cells[3]?.textContent?.replace(/,/g,'')) || 0;
    const credit = parseFloat(cells[4]?.textContent?.replace(/,/g,'')) || 0;
    cb.dataset.net = (credit - debit).toFixed(2);
});
document.querySelectorAll('.jl-row').forEach(row => {
    const cb = row.querySelector('.jl-cb');
    if (!cb) return;
    const cells = row.querySelectorAll('td');
    const debit  = parseFloat(cells[3]?.textContent?.replace(/,/g,'')) || 0;
    const credit = parseFloat(cells[4]?.textContent?.replace(/,/g,'')) || 0;
    cb.dataset.net = (debit - credit).toFixed(2);
});

function updateTotals() {
    let stmtNet = 0, jlNet = 0;
    document.querySelectorAll('.stmt-cb:checked').forEach(cb => stmtNet += parseFloat(cb.dataset.net || 0));
    document.querySelectorAll('.jl-cb:checked').forEach(cb => jlNet += parseFloat(cb.dataset.net || 0));

    const stmtAny = document.querySelectorAll('.stmt-cb:checked').length > 0;
    const jlAny   = document.querySelectorAll('.jl-cb:checked').length > 0;

    document.getElementById('stmt-total').textContent = 'AED ' + Math.abs(stmtNet).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('jl-total').textContent   = 'AED ' + Math.abs(jlNet).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    const balanced = stmtAny && jlAny && Math.abs(stmtNet - jlNet) < 0.03;
    const btn = document.getElementById('match-btn');
    const status = document.getElementById('match-status');

    if (btn) {
        btn.disabled = !balanced;
        if (stmtAny && jlAny && !balanced) {
            status.textContent = '⚠ Amounts differ by AED ' + Math.abs(stmtNet - jlNet).toFixed(2);
            status.className = 'text-xs text-red-500';
        } else if (balanced) {
            status.textContent = '✓ Amounts match';
            status.className = 'text-xs text-green-600';
        } else {
            status.textContent = '';
        }
    }
}
</script>
@endpush

@endsection
