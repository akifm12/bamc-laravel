@extends('layouts.app')

@section('title', $customer->name)

@section('content')

<div class="max-w-4xl">

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $customer->name }}</h2>
                @if($customer->name_arabic)
                    <p class="text-gray-400 text-sm mt-1" dir="rtl">{{ $customer->name_arabic }}</p>
                @endif
                <p class="text-gray-500 text-sm mt-1">{{ $customer->code }}</p>
            </div>
            <div class="flex gap-2">
                <a href="/customers/{{ $customer->id }}/edit"
                    class="text-sm border border-gray-200 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50">
                    ✏️ Edit
                </a>
                <a href="/invoices/create?customer_id={{ $customer->id }}"
                    class="text-sm bg-green-700 text-white px-3 py-1.5 rounded hover:bg-green-800">
                    ➕ New Invoice
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Total Billed</p>
            <p class="text-xl font-bold text-gray-800">AED {{ number_format($totalBilled, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Amount Due</p>
            <p class="text-xl font-bold {{ $totalDue > 0 ? 'text-red-600' : 'text-green-600' }}">
                AED {{ number_format($totalDue, 2) }}
            </p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-400">Invoices</p>
            <p class="text-xl font-bold text-gray-800">{{ count($invoices) }}</p>
        </div>
    </div>

    <!-- Details -->
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-3">Contact Details</h3>
            <div class="space-y-2 text-sm">
                @if($customer->contact_person)
                    <p><span class="text-gray-400">Contact:</span> {{ $customer->contact_person }}</p>
                @endif
                @if($customer->email)
                    <p><span class="text-gray-400">Email:</span> {{ $customer->email }}</p>
                @endif
                @if($customer->phone)
                    <p><span class="text-gray-400">Phone:</span> {{ $customer->phone }}</p>
                @endif
                @if($customer->mobile)
                    <p><span class="text-gray-400">Mobile:</span> {{ $customer->mobile }}</p>
                @endif
                @if($customer->trn)
                    <p><span class="text-gray-400">TRN:</span> {{ $customer->trn }}</p>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-3">Address</h3>
            <div class="text-sm text-gray-600">
                @if($customer->address_line1)
                    <p>{{ $customer->address_line1 }}</p>
                @endif
                @if($customer->address_line2)
                    <p>{{ $customer->address_line2 }}</p>
                @endif
                <p>{{ implode(', ', array_filter([$customer->city, $customer->emirate, $customer->country])) }}</p>
            </div>
        </div>
    </div>

    <!-- Invoices -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">Invoices</span>
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
                @forelse($invoices as $inv)
                @php
                    $status = strtolower($inv->status ?? 'draft');
                    $statusColor = match($status) {
                        'approved', 'posted' => 'bg-green-100 text-green-700',
                        'draft'              => 'bg-yellow-100 text-yellow-700',
                        'void', 'cancelled'  => 'bg-gray-100 text-gray-500',
                        'overdue'            => 'bg-red-100 text-red-700',
                        default              => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs">{{ $inv->invoice_number }}</td>
                    <td class="px-4 py-2">{{ $inv->invoice_date }}</td>
                    <td class="px-4 py-2">{{ $inv->due_date ?? '-' }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($inv->total_amount, 2) }}</td>
                    <td class="px-4 py-2 text-right {{ $inv->amount_due > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ number_format($inv->amount_due, 2) }}
                    </td>
                    <td class="px-4 py-2 text-center">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $statusColor }}">{{ strtoupper($status) }}</span>
                    </td>
                    <td class="px-4 py-2 text-right">
                        <a href="/invoices/{{ $inv->id }}" class="text-xs text-blue-500 hover:text-blue-700">View →</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-400">No invoices yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

@endsection