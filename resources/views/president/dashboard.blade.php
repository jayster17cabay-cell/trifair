@extends('layouts.president')

@section('title', 'My Ratings')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-award mr-2 text-gold"></i>{{ $toda->name }}</h1>
        <p class="tw-page-sub">
            @if ($summary['todaArea'])
                {{ $summary['todaArea'] }} ·
            @endif
            Overseeing {{ $summary['totalMembers'] }} {{ $summary['totalMembers'] === 1 ? 'member' : 'members' }}
        </p>
    </div>
</div>

<div class="mb-4 grid grid-cols-2 gap-2.5 md:grid-cols-4">
    @include('partials.dashboard-kpi', ['kpi' => ['icon' => 'bi-award', 'value' => number_format($summary['ownAvg'], 1), 'label' => 'My Rating', 'live' => 'ownAvg']])
    @include('partials.dashboard-kpi', ['kpi' => ['icon' => 'bi-people', 'value' => $summary['totalMembers'], 'label' => 'Members']])
    @include('partials.dashboard-kpi', ['kpi' => ['icon' => 'bi-bar-chart', 'value' => number_format($summary['avgMemberRating'], 1), 'label' => 'TODA Avg']])
    @include('partials.dashboard-kpi', ['kpi' => ['icon' => 'bi-flag', 'value' => $summary['pendingComplaints'], 'label' => 'Complaints']])
</div>

{{-- Section 1 — the president's OWN ratings first --}}
@include('partials.president.own-ratings', ['ownOperator' => $ownOperator, 'ownRecentRatings' => $ownRecentRatings])

{{-- Section 2 — member rating breakdown --}}
<div class="mt-3">
    @include('partials.president.breakdown-chart', ['breakdown' => $breakdown])
</div>

{{-- Section 3 — members toggle button --}}
@php $membersActive = request()->has('search') || request()->has('status'); @endphp
<div class="mt-3">
    <button id="presidentMembersToggle" type="button" class="tw-btn tw-btn-gold w-full justify-center px-4 py-3 text-base" onclick="togglePresidentMembers()">
        <i class="bi bi-people-fill mr-2"></i><span id="presidentMembersToggleLabel">{{ $membersActive ? 'Hide Members' : 'View Members' }}</span>
        <i id="presidentMembersToggleChevron" class="bi {{ $membersActive ? 'bi-chevron-up' : 'bi-chevron-down' }} ml-auto"></i>
    </button>
</div>

{{-- Section 4 — members list (hidden until toggled), each member opens their ratings --}}
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
    }
</script>
@endsection
