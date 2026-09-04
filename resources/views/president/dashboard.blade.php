@extends('layouts.president')

@section('title', 'TODA President Dashboard')

@section('content')
@include('partials.dashboard-hero', [
    'eyebrow' => 'TriFair TODA President',
    'subtitle' => $summary['todaArea'] ? ($summary['todaArea'] . ' · ' . $summary['totalMembers'] . ' members in ' . $toda->name) : ($summary['totalMembers'] . ($summary['totalMembers'] === 1 ? ' member' : ' members') . ' in ' . $toda->name),
])

{{-- KPI cards --}}
<div class="mb-4 grid grid-cols-2 gap-2.5 md:grid-cols-4">
    @include('partials.dashboard-kpi', ['kpi' => ['href' => '#ownRatings', 'icon' => 'bi-award', 'value' => number_format($summary['ownAvg'], 1), 'label' => 'My Rating', 'live' => 'ownAvg']])
    @include('partials.dashboard-kpi', ['kpi' => ['href' => '#membersSection', 'icon' => 'bi-people', 'value' => $summary['totalMembers'], 'label' => 'Members']])
    @include('partials.dashboard-kpi', ['kpi' => ['icon' => 'bi-bar-chart', 'value' => number_format($summary['avgMemberRating'], 1), 'label' => 'TODA Avg']])
    @include('partials.dashboard-kpi', ['kpi' => ['href' => '#membersSection', 'icon' => 'bi-flag', 'value' => $summary['pendingComplaints'], 'label' => 'Complaints']])
</div>

{{-- Section 1 — My Ratings (the president's own), first and highlighted --}}
<div id="ownRatings" class="mb-4 scroll-mt-24">
    @include('partials.president.own-ratings', ['ownOperator' => $ownOperator, 'ownRecentRatings' => $ownRecentRatings])
</div>

{{-- Section 2 — Member rating breakdown --}}
<div class="mb-4">
    @include('partials.president.breakdown-chart', ['breakdown' => $breakdown])
</div>

{{-- Section 3 — Members toggle button --}}
@php $membersActive = ($membersActive ?? false) || request()->has('search') || request()->has('status'); @endphp
{{-- Section 3 — Members search + toggle (search is always visible) --}}
<div id="membersSection" class="scroll-mt-24 tw-card mb-3 overflow-hidden">
    <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between" style="background-image: linear-gradient(135deg, #0a1d33 0%, #0f2a4a 100%);">
        <div class="flex items-center gap-2">
            <button id="presidentMembersToggle" type="button" class="tw-btn tw-btn-gold px-4 py-2 text-sm" onclick="togglePresidentMembers()">
                <i class="bi bi-people-fill mr-1"></i><span id="presidentMembersToggleLabel">{{ $membersActive ? 'Hide Members' : 'View Members' }}</span>
                <i id="presidentMembersToggleChevron" class="bi {{ $membersActive ? 'bi-chevron-up' : 'bi-chevron-down' }} ml-1"></i>
            </button>
        </div>
        <form id="presidentMemberFilter" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center" method="GET" action="{{ route('president.dashboard') }}">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, body #, plate…"
                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 placeholder-slate-400 focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold sm:w-56">
            <select name="status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold">
                <option value="">All statuses</option>
                @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'rejected' => 'Rejected'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="tw-btn tw-btn-gold px-4 py-2 text-sm"><i class="bi bi-search mr-1"></i> Search</button>
        </form>
    </div>
</div>

{{-- Section 4 — members list (hidden until toggled / search active), each member opens their ratings --}}
<div id="presidentMembersWrap" class="mt-3 {{ $membersActive ? '' : 'hidden' }}">
    @include('partials.president.members-section', ['members' => $members, 'memberDetailUrl' => route('president.members')])
</div>

<div id="presidentMemberModal" class="tw-modal-backdrop hidden">
    <div class="tw-modal" role="dialog" aria-modal="true" aria-labelledby="presidentMemberModalBody">
        <div id="presidentMemberModalBody"><!-- populated by AJAX --></div>
    </div>
</div>

<script>
    function togglePresidentMembers() {
        var wrap = document.getElementById('presidentMembersWrap');
        var label = document.getElementById('presidentMembersToggleLabel');
        var chevron = document.getElementById('presidentMembersToggleChevron');
        var hidden = wrap.classList.contains('hidden');
        wrap.classList.toggle('hidden');
        label.textContent = hidden ? 'Hide Members' : 'View Members';
        chevron.classList.toggle('bi-chevron-down', !hidden);
        chevron.classList.toggle('bi-chevron-up', hidden);
        if (hidden) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    @if ($membersActive)
        document.addEventListener('DOMContentLoaded', function () {
            var wrap = document.getElementById('presidentMembersWrap');
            if (wrap) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    @endif
</script>
@endsection
