@extends('layouts.app')
@section('title', 'Small Business Relief')
@section('content')

<div class="max-w-4xl">

<div class="flex gap-1 mb-5 border-b border-gray-200">
    <a href="/ct"
       class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
        CT Returns
    </a>
    <a href="/ct/sbr"
       class="px-4 py-2 text-sm font-medium border-b-2 border-green-700 text-green-700">
        Small Business Relief
    </a>
</div>

{{-- Explainer --}}
<div class="bg-blue-50 border border-blue-100 rounded-lg p-5 mb-5">
    <h3 class="text-sm font-bold text-blue-800 mb-2">Small Business Relief (SBR)</h3>
    <div class="text-xs text-blue-700 space-y-1.5">
        <p>SBR allows eligible businesses to treat their taxable income as <strong>nil</strong> for a tax period, meaning no Corporate Tax is due.</p>
        <p><strong>Eligibility conditions (all must be met):</strong></p>
        <ul class="list-disc ml-4 space-y-1">
            <li>Revenue ≤ AED 3,000,000 for the tax period</li>
            <li>Tax period ending on or before <strong>31 December 2026</strong></li>
            <li>Not a member of a Qualifying Multinational Enterprise (MNE) Group</li>
            <li>Not a Qualifying Free Zone Person</li>
            <li>Must be elected — it is not automatic</li>
        </ul>
        <p class="mt-2">SBR must be elected in the CT Return for each qualifying period separately.</p>
    </div>
</div>

{{-- SBR eligibility table --}}
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
        <h3 class="text-sm font-bold text-gray-700">SBR Eligibility by Year</h3>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-gray-400 uppercase border-b border-gray-200">
                <th class="px-4 py-2 text-left">Fiscal Year</th>
                <th class="px-4 py-2 text-right">Revenue</th>
                <th class="px-4 py-2 text-center">Period ≤ Dec 2026</th>
                <th class="px-4 py-2 text-center">Eligibility</th>
                <th class="px-4 py-2 text-center">SBR Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($years as $y)
            @php
                $withinWindow = $y['year'] <= 2026;
                $canElect     = $y['eligible'] && $withinWindow;
            @endphp
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="px-4 py-3 font-semibold text-gray-700">{{ $y['year'] }}</td>
                <td class="px-4 py-3 text-right text-xs text-gray-600">
                    @if($y['revenue'] > 0)
                        AED {{ number_format($y['revenue'], 2) }}
                        @if($y['revenue'] > 3000000)
                            <br><span class="text-red-500">exceeds AED 3M</span>
                        @else
                            <br><span class="text-green-600">within AED 3M</span>
                        @endif
                    @else
                        <span class="text-gray-400">No data</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center text-xs">
                    @if($withinWindow)
                        <span class="text-green-600">✓ Yes</span>
                    @else
                        <span class="text-gray-400">✗ No</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    @if($canElect)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">Eligible</span>
                    @elseif($y['revenue'] <= 0)
                        <span class="text-xs text-gray-400">Unknown</span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Not eligible</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    @if($y['elected'])
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">Elected ✓</span>
                    @elseif($y['ctId'])
                        <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">Not elected</span>
                    @else
                        <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    @if($y['ctId'])
                        <a href="/ct/{{ $y['ctId'] }}" class="text-xs text-blue-500 hover:text-blue-700">View Return →</a>
                    @elseif($canElect)
                        <a href="/ct/create?year={{ $y['year'] }}"
                           class="text-xs bg-green-700 text-white px-2 py-0.5 rounded hover:bg-green-800">
                            Elect SBR
                        </a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<p class="text-xs text-gray-400 mt-3">
    * SBR must be elected in your CT Return. Click "Elect SBR" to prepare a return and check the SBR checkbox.
</p>

</div>
@endsection
