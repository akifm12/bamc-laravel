@extends('layouts.app')

@section('title', 'VAT Return - ' . $return->return_number)

@section('content')

<div class="max-w-3xl">

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $return->return_number }}</h2>
                <p class="text-gray-500 text-sm mt-1">Period: {{ $return->period_from }} - {{ $return->period_to }}</p>
                @if($return->due_date)
                    <p class="text-xs text-gray-400 mt-1">Due: {{ $return->due_date }}</p>
                @endif
            </div>
            <div class="flex flex-col items-end gap-2">
                @php
                    $statusColor = match(strtolower($return->status)) {
                        'submitted' => 'bg-green-100 text-green-700',
                        'draft'     => 'bg-yellow-100 text-yellow-700',
                        'paid'      => 'bg-blue-100 text-blue-700',
                        default     => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <span class="text-xs px-3 py-1 rounded-full {{ $statusColor }}">{{ strtoupper($return->status) }}</span>
                @if(strtolower($return->status) === 'draft')
                    <form method="POST" action="/vat/{{ $return->id }}/submit">
                        @csrf
                        <button class="text-xs bg-green-700 text-white px-3 py-1.5 rounded hover:bg-green-800">
                            ✅ Mark as Submitted
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Net VAT Summary -->
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Output VAT (Box 6)</p>
            <p class="text-xl font-bold text-gray-800">AED {{ number_format($return->box6_total_output_tax, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Input VAT (Box 9)</p>
            <p class="text-xl font-bold text-gray-800">AED {{ number_format($return->box9_total_input_tax, 2) }}</p>
        </div>
        <div class="rounded-lg border p-4 {{ $return->box13_net_payable > 0 ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200' }}">
            <p class="text-xs text-gray-400">Net Payable (Box 13)</p>
            <p class="text-xl font-bold {{ $return->box13_net_payable > 0 ? 'text-red-700' : 'text-green-700' }}">
                AED {{ number_format($return->box13_net_payable, 2) }}
            </p>
            <p class="text-xs mt-1 {{ $return->box13_net_payable > 0 ? 'text-red-500' : 'text-green-500' }}">
                {{ $return->box13_net_payable > 0 ? 'Payable to FTA' : 'Refund due' }}
            </p>
        </div>
    </div>

    <!-- Full VAT 201 Form -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">UAE VAT 201 - Full Return</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                    <th class="px-4 py-2 text-left w-16">Box</th>
                    <th class="px-4 py-2 text-left">Description</th>
                    <th class="px-4 py-2 text-right">Amount (AED)</th>
                    <th class="px-4 py-2 text-right">VAT (AED)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-blue-50 border-b border-gray-100">
                    <td colspan="4" class="px-4 py-2 text-xs font-semibold text-blue-700 uppercase">Part A - VAT on Sales</td>
                </tr>
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-500">1</td>
                    <td class="px-4 py-2">Standard Rated Sales</td>
                    <td class="px-4 py-2 text-right">{{ number_format($return->box1_standard_rated_sales, 2) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($return->box1_vat_amount, 2) }}</td>
                </tr>
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-500">2</td>
                    <td class="px-4 py-2">Zero Rated Sales</td>
                    <td class="px-4 py-2 text-right">{{ number_format($return->box2_zero_rated_sales, 2) }}</td>
                    <td class="px-4 py-2 text-right text-gray-400">0.00</td>
                </tr>
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-500">3</td>
                    <td class="px-4 py-2">Exempt Sales</td>
                    <td class="px-4 py-2 text-right">{{ number_format($return->box3_exempt_sales, 2) }}</td>
                    <td class="px-4 py-2 text-right text-gray-400">0.00</td>
                </tr>
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-500">4</td>
                    <td class="px-4 py-2">Goods Imported into UAE</td>
                    <td class="px-4 py-2 text-right">{{ number_format($return->box4_goods_imported, 2) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($return->box4_vat_amount, 2) }}</td>
                </tr>
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-500">5</td>
                    <td class="px-4 py-2">Reverse Charge</td>
                    <td class="px-4 py-2 text-right">{{ number_format($return->box5_reverse_charge, 2) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($return->box5_vat_amount, 2) }}</td>
                </tr>
                <tr class="bg-gray-50 border-b border-gray-200 font-semibold">
                    <td class="px-4 py-2 font-mono text-xs">6</td>
                    <td class="px-4 py-2">Total Output Tax</td>
                    <td class="px-4 py-2 text-right">-</td>
                    <td class="px-4 py-2 text-right text-green-700">{{ number_format($return->box6_total_output_tax, 2) }}</td>
                </tr>

                <tr class="bg-orange-50 border-b border-gray-100">
                    <td colspan="4" class="px-4 py-2 text-xs font-semibold text-orange-700 uppercase">Part B - VAT on Purchases</td>
                </tr>
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-500">7</td>
                    <td class="px-4 py-2">Standard Rated Purchases</td>
                    <td class="px-4 py-2 text-right">{{ number_format($return->box7_standard_purchases, 2) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($return->box7_recoverable_vat, 2) }}</td>
                </tr>
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-500">8</td>
                    <td class="px-4 py-2">Reverse Charge Purchases</td>
                    <td class="px-4 py-2 text-right">{{ number_format($return->box8_reverse_charge_purchases, 2) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($return->box8_recoverable_vat, 2) }}</td>
                </tr>
                <tr class="bg-gray-50 border-b border-gray-200 font-semibold">
                    <td class="px-4 py-2 font-mono text-xs">9</td>
                    <td class="px-4 py-2">Total Input Tax</td>
                    <td class="px-4 py-2 text-right">-</td>
                    <td class="px-4 py-2 text-right text-blue-700">{{ number_format($return->box9_total_input_tax, 2) }}</td>
                </tr>

                <tr class="bg-gray-50 border-b border-gray-100">
                    <td colspan="4" class="px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Part C - Net VAT</td>
                </tr>
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-500">10</td>
                    <td class="px-4 py-2">Adjustment</td>
                    <td class="px-4 py-2 text-right">-</td>
                    <td class="px-4 py-2 text-right">{{ number_format($return->box10_adjustment, 2) }}</td>
                </tr>
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-500">11</td>
                    <td class="px-4 py-2">Net VAT Due</td>
                    <td class="px-4 py-2 text-right">-</td>
                    <td class="px-4 py-2 text-right font-semibold">{{ number_format($return->box11_net_vat_due, 2) }}</td>
                </tr>
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-500">12</td>
                    <td class="px-4 py-2">VAT on Imports (paid to Customs)</td>
                    <td class="px-4 py-2 text-right">-</td>
                    <td class="px-4 py-2 text-right">{{ number_format($return->box12_vat_on_imports, 2) }}</td>
                </tr>
                <tr class="font-bold border-t-2 border-gray-300 {{ $return->box13_net_payable > 0 ? 'bg-red-50' : 'bg-green-50' }}">
                    <td class="px-4 py-3 font-mono text-sm">13</td>
                    <td class="px-4 py-3">Net VAT Payable / (Refundable)</td>
                    <td class="px-4 py-3 text-right">-</td>
                    <td class="px-4 py-3 text-right text-lg {{ $return->box13_net_payable > 0 ? 'text-red-700' : 'text-green-700' }}">
                        AED {{ number_format($return->box13_net_payable, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($return->notes)
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-4">
        <p class="text-xs text-gray-400 mb-1">Notes</p>
        <p class="text-sm text-gray-600">{{ $return->notes }}</p>
    </div>
    @endif

    <a href="/vat" class="text-sm text-gray-500 hover:text-gray-700">← Back to VAT Returns</a>

</div>

@endsection