@extends('layouts.app')

@section('title', $asset->name)

@section('content')

<div class="max-w-4xl">

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $asset->name }}</h2>
                <p class="text-gray-500 text-sm mt-1">{{ $asset->asset_number }}</p>
                @if($asset->category_name)
                    <p class="text-xs text-gray-400 mt-1">{{ $asset->category_name }}</p>
                @endif
            </div>
            <div class="flex flex-col items-end gap-2">
                @php
                    $statusColor = match($asset->status) {
                        'active'            => 'bg-green-100 text-green-700',
                        'fully_depreciated' => 'bg-gray-100 text-gray-500',
                        'disposed'          => 'bg-red-100 text-red-600',
                        default             => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <span class="text-xs px-3 py-1 rounded-full {{ $statusColor }}">
                    {{ str_replace('_', ' ', strtoupper($asset->status)) }}
                </span>
                @if($asset->status === 'active')
                    <form method="POST" action="/assets/{{ $asset->id }}/dispose" class="inline">
                        @csrf
                        <input type="hidden" name="disposal_date" value="{{ date('Y-m-d') }}">
                        <button onclick="return confirm('Mark this asset as disposed?')"
                            class="text-xs border border-red-200 text-red-600 px-3 py-1.5 rounded hover:bg-red-50">
                            🗑️ Dispose
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-4 gap-4 mb-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Purchase Cost</p>
            <p class="text-lg font-bold text-gray-800">AED {{ number_format($asset->purchase_cost, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Accumulated Depr.</p>
            <p class="text-lg font-bold text-red-600">AED {{ number_format($asset->accumulated_depreciation, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Net Book Value</p>
            <p class="text-lg font-bold text-green-700">AED {{ number_format($asset->net_book_value, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Depreciation Rate</p>
            <p class="text-lg font-bold text-gray-800">{{ number_format($asset->depreciation_rate, 1) }}% / yr</p>
        </div>
    </div>

    <!-- Details -->
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-3">Asset Information</h3>
            <div class="space-y-2 text-sm">
                <p><span class="text-gray-400">Purchase Date:</span> {{ $asset->purchase_date }}</p>
                <p><span class="text-gray-400">In Service:</span> {{ $asset->in_service_date ?? '-' }}</p>
                @if($asset->serial_number)
                    <p><span class="text-gray-400">Serial No.:</span> {{ $asset->serial_number }}</p>
                @endif
                @if($asset->location)
                    <p><span class="text-gray-400">Location:</span> {{ $asset->location }}</p>
                @endif
                @if($asset->purchase_invoice_ref)
                    <p><span class="text-gray-400">Invoice Ref:</span> {{ $asset->purchase_invoice_ref }}</p>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-3">Depreciation Schedule</h3>
            <div class="space-y-2 text-sm">
                <p><span class="text-gray-400">Method:</span> {{ str_replace('_', ' ', ucfirst($asset->depreciation_method)) }}</p>
                <p><span class="text-gray-400">Useful Life:</span> {{ $asset->useful_life_years }} years</p>
                <p><span class="text-gray-400">Residual Value:</span> AED {{ number_format($asset->residual_value, 2) }}</p>
                <p><span class="text-gray-400">Depreciable Amount:</span> AED {{ number_format($asset->depreciable_amount, 2) }}</p>
                @php
                    $monthlyDepr = $asset->useful_life_years > 0
                        ? $asset->depreciable_amount / ($asset->useful_life_years * 12)
                        : 0;
                @endphp
                <p><span class="text-gray-400">Monthly Depreciation:</span> AED {{ number_format($monthlyDepr, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Run Depreciation -->
    @if($asset->status === 'active')
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <h3 class="font-semibold text-gray-700 text-sm mb-3">Run Depreciation</h3>
        <form method="POST" action="/assets/{{ $asset->id }}/depreciate" class="flex gap-3 items-end">
            @csrf
            <div>
                <label class="text-xs text-gray-500 block mb-1">Depreciation Date</label>
                <input type="date" name="depreciation_date" value="{{ date('Y-m-d') }}"
                    class="border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <button type="submit"
                class="bg-green-700 text-white text-sm px-4 py-1.5 rounded hover:bg-green-800">
                ▶ Post Monthly Depreciation (AED {{ number_format($monthlyDepr, 2) }})
            </button>
        </form>
    </div>
    @endif

    <!-- Depreciation History -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">Depreciation History</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-right">Amount</th>
                    <th class="px-4 py-2 text-right">Accumulated</th>
                    <th class="px-4 py-2 text-right">NBV After</th>
                </tr>
            </thead>
            <tbody>
                @forelse($depreciationHistory as $line)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 text-gray-600">{{ $line->depreciation_date }}</td>
                    <td class="px-4 py-2 text-right text-red-600">{{ number_format($line->amount, 2) }}</td>
                    <td class="px-4 py-2 text-right text-gray-600">{{ number_format($line->accumulated_after, 2) }}</td>
                    <td class="px-4 py-2 text-right font-medium">{{ number_format($line->nbv_after, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">No depreciation posted yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <a href="/assets" class="text-sm text-gray-500 hover:text-gray-700">← Back to Assets</a>

</div>

@endsection