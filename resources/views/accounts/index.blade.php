@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('content')
@php
$typeLabels = [
    'ASSET'     => ['label' => 'Assets',      'color' => 'blue',   'icon' => '🟦'],
    'LIABILITY' => ['label' => 'Liabilities', 'color' => 'red',    'icon' => '🟥'],
    'EQUITY'    => ['label' => 'Equity',      'color' => 'green',  'icon' => '🟩'],
    'REVENUE'   => ['label' => 'Revenue',     'color' => 'yellow', 'icon' => '🟨'],
    'EXPENSE'   => ['label' => 'Expenses',    'color' => 'orange', 'icon' => '🟧'],
];
@endphp

<div class="mb-4 flex items-center justify-between">
    <p class="text-sm text-gray-500">{{ $grouped->flatten()->count() }} accounts across {{ count($grouped) }} types</p>
    <a href="/accounts/create" class="bg-blue-600 text-white text-sm px-4 py-2 rounded hover:bg-blue-700">➕ New Account</a>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded text-sm">
    {{ session('success') }}
</div>
@endif

@foreach($typeLabels as $type => $meta)
    @if(isset($grouped[$type]))
    @php $accounts = $grouped[$type]; @endphp
    <div class="mb-5 bg-white rounded-lg border border-gray-200 overflow-hidden">
        <!-- Section header -->
        <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between cursor-pointer"
             onclick="toggleSection('{{ $type }}')">
            <div class="flex items-center gap-2">
                <span>{{ $meta['icon'] }}</span>
                <span class="font-semibold text-gray-700 text-sm">{{ $meta['label'] }}</span>
                <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">{{ $accounts->count() }}</span>
            </div>
            <div class="flex items-center gap-3">
                <a href="/accounts/create?account_type={{ $type }}"
                   class="text-xs text-blue-600 hover:text-blue-800"
                   onclick="event.stopPropagation()">+ Add {{ $meta['label'] }} Account</a>
                <span class="text-gray-400 text-xs" id="arrow-{{ $type }}">▼</span>
            </div>
        </div>
        <!-- Accounts table -->
        <div id="section-{{ $type }}">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                        <th class="px-4 py-2 text-left w-24">Code</th>
                        <th class="px-4 py-2 text-left">Name</th>
                        <th class="px-4 py-2 text-left w-32">Normal Balance</th>
                        <th class="px-4 py-2 text-left w-20">Status</th>
                        <th class="px-4 py-2 text-center w-20">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts as $account)
                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $account->code }}</td>
<td class="px-4 py-2 font-medium text-gray-800"
    style="padding-left: {{ 16 + ($account->depth * 24) }}px">
    @if($account->depth > 0)
        <span class="text-gray-300 mr-1">{{ str_repeat('—', $account->depth) }}</span>
    @endif
    {{ $account->name }}
    @if($account->name_arabic)
        <span class="text-xs text-gray-400 ml-2" dir="rtl">{{ $account->name_arabic }}</span>
    @endif
</td>
                        <td class="px-4 py-2 text-gray-500">{{ $account->normal_balance ?? '—' }}</td>
                        <td class="px-4 py-2">
                            @if($account->is_active)
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Active</span>
                            @else
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Inactive</span>
                            @endif
                        </td>
                        <<td class="px-4 py-2 text-center">
    						<div class="flex gap-3 justify-center">
       						 <a href="/accounts/{{ $account->id }}/edit"
           						class="text-xs text-blue-600 hover:text-blue-800">Edit</a>
        						<form method="POST" action="/accounts/{{ $account->id }}"
              						style="display:inline"
              						onsubmit="return confirm('Delete {{ $account->name }}? This cannot be undone.')">
            						@csrf
            						@method('DELETE')
            						<button class="text-xs text-red-500 hover:text-red-700">Delete</button>
        						</form>
    						</div>
						</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
@endforeach

<script>
function toggleSection(type) {
    const section = document.getElementById('section-' + type);
    const arrow   = document.getElementById('arrow-' + type);
    if (section.style.display === 'none') {
        section.style.display = '';
        arrow.textContent = '▼';
    } else {
        section.style.display = 'none';
        arrow.textContent = '▶';
    }
}
</script>
@endsection