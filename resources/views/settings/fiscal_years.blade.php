@extends('layouts.app')

@section('title', 'Fiscal Years')

@section('content')

<div class="max-w-3xl">

    <!-- Create new fiscal year -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Create New Fiscal Year</h3>

        <form method="POST" action="/settings/fiscal-years" class="grid grid-cols-3 gap-4 items-end">
            @csrf
            <div>
                <label class="text-xs text-gray-500 block mb-1">Year</label>
                <input type="number" name="year" value="{{ date('Y') + 1 }}" min="2000" max="2100"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ date('Y') + 1 }}-01-01"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ date('Y') + 1 }}-12-31"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div class="col-span-3">
                <button type="submit" class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800">
                    ➕ Create Fiscal Year + Auto-generate Monthly Periods
                </button>
            </div>
        </form>
    </div>

    <!-- Existing fiscal years -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <span class="font-semibold text-gray-700 text-sm">Fiscal Years</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                    <th class="px-4 py-2 text-left">Name</th>
                    <th class="px-4 py-2 text-left">Start</th>
                    <th class="px-4 py-2 text-left">End</th>
                    <th class="px-4 py-2 text-center">Periods</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fiscalYears as $fy)
                @php $status = strtolower($fy->status ?? 'open'); @endphp
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-2 font-medium text-gray-800">{{ $fy->name }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $fy->start_date }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $fy->end_date }}</td>
                    <td class="px-4 py-2 text-center text-gray-600">{{ $fy->period_count }}</td>
                    <td class="px-4 py-2 text-center">
                        @if($status === 'open')
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Open</span>
                        @else
                            <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Closed</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-center">
                        @if($status === 'open')
                            <form method="POST" action="/settings/fiscal-years/{{ $fy->id }}/close" class="inline">
                                @csrf
                                <button onclick="return confirm('Close {{ $fy->name }}? This will lock all periods.')"
                                    class="text-xs text-red-500 hover:text-red-700">Close</button>
                            </form>
                        @else
                            <form method="POST" action="/settings/fiscal-years/{{ $fy->id }}/reopen" class="inline">
                                @csrf
                                <button class="text-xs text-green-600 hover:text-green-800">Reopen</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">No fiscal years found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <a href="/settings/company" class="text-sm text-gray-500 hover:text-gray-700">← Company Settings</a>
    </div>

</div>

@endsection