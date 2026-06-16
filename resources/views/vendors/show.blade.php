@extends('layouts.app')

@section('title', $vendor->name)

@section('content')

<div class="max-w-4xl">

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $vendor->name }}</h2>
                @if($vendor->name_arabic)
                    <p class="text-gray-400 text-sm mt-1" dir="rtl">{{ $vendor->name_arabic }}</p>
                @endif
                <p class="text-gray-500 text-sm mt-1">{{ $vendor->code }}</p>
            </div>
            <div class="flex gap-2">
                <a href="/vendors/{{ $vendor->id }}/edit"
                    class="text-sm border border-gray-200 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50">
                    ✏️ Edit
                </a>
                <a href="/bills/create?vendor_id={{ $vendor->id }}"
                    class="text-sm bg-green-700 text-white px-3 py-1.5 rounded hover:bg-green-800">
                    ➕ New Bill
                </a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Total Bills</p>
            <p class="text-xl font-bold text-gray-800">AED {{ number_format($totalBilled, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Amount Due</p>
            <p class="text-xl font-bold {{ $totalDue > 0 ? 'text-red-600' : 'text-green-600' }}">
                AED {{ number_format($totalDue, 2) }}
            </p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Bills</p>
            <p class="text-xl font-bold text-gray-800">{{ count($bills) }}</p>
        </div>
    </div>

    <!-- Details -->
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-3">Contact Details</h3>
            <div class="space-y-2 text-sm">
                @if($vendor->contact_person)
                    <p><span class="text-gray-400">Contact:</span> {{ $vendor->contact_person }}</p>
                @endif
                @if($vendor->email)
                    <p><span class="text-gray-400">Email:</span> {{ $vendor->email }}</p>
                @endif
                @if($vendor->phone)
                    <p><span class="text-gray-400">Phone:</span> {{ $vendor->phone }}</p>
                @endif
                @if($vendor->trn)
                    <p><span class="text-gray-400">TRN:</span> {{ $vendor->trn }}</p>
                @endif
                @if($vendor->iban)
                    <p><span class="text-gray-400">IBAN:</span> {{ $vendor->iban }}</p>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-3">Address</h3>
            <div class="text-sm text-gray-600">
                @if($vendor->address_line1)
                    <p>{{ $vendor->address_line1 }}</p>
                @endif
                <p>{{ implode(', ', array_filter([$vendor->city, $vendor->emirate, $vendor->country])) }}</p>
            </div>
            @if($vendor->bank_name)
            <h3 class="font-semibold text-gray-700 text-sm mt-4 mb-2">Bank Details</h3>
            <div class="text-sm text-gray-600">
                <p>{{ $vendor->bank_name }}</p>
                @if($vendor->bank_account_no)<p>A/C: {{ $vendor->bank_account_no }}</p>@endif
                @if($vendor->swift_code)<p>SWIFT: {{ $vendor->swift_code }}</p>@endif
            </div>
            @endif
        </div>
    </div>

    <!-- Bills -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">Bills</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                    <th class="px-4 py-2 text-left">Number</th>
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-left">Due Date</th>
                    <th class="px-4 py-2 text-right">Amount</th>
                    <th class="px-4 py-2 text-right">Due</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($bills as $bill)
                @php
                    $status = strtolower($bill->status ?? 'draft');
                    $statusColor = match($status) {
                        'approved' => 'bg-green-100 text-green-700',
                        'draft'    => 'bg-yellow-100 text-yellow-700',
                        'void'     => 'bg-gray-100 text-gray-500',
                        default    => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs">{{ $bill->bill_number }}</td>
                    <td class="px-4 py-2">{{ $bill->bill_date }}</td>
                    <td class="px-4 py-2">{{ $bill->due_date ?? '-' }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($bill->total_amount, 2) }}</td>
                    <td class="px-4 py-2 text-right {{ $bill->amount_due > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ number_format($bill->amount_due, 2) }}
                    </td>
                    <td class="px-4 py-2 text-center">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $statusColor }}">{{ strtoupper($status) }}</span>
                    </td>
                    <td class="px-4 py-2 text-right">
                        <a href="/bills/{{ $bill->id }}" class="text-xs text-blue-500 hover:text-blue-700">View →</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-400">No bills yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

@endsection