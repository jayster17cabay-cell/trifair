@extends('layouts.president')

@section('title', 'TODA Overview')

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
    @include('partials.dashboard-kpi', ['kpi' => ['icon' => 'bi-people', 'value' => $summary['totalMembers'], 'label' => 'Members']])
    @include('partials.dashboard-kpi', ['kpi' => ['icon' => 'bi-award', 'value' => number_format($summary['ownAvg'], 1), 'label' => 'My Rating', 'live' => 'ownAvg']])
    @include('partials.dashboard-kpi', ['kpi' => ['icon' => 'bi-bar-chart', 'value' => number_format($summary['avgMemberRating'], 1), 'label' => 'TODA Avg']])
    @include('partials.dashboard-kpi', ['kpi' => ['icon' => 'bi-flag', 'value' => $summary['pendingComplaints'], 'label' => 'Complaints']])
</div>

<div class="mb-3 grid gap-3 lg:grid-cols-2">
    @include('partials.president.own-ratings', ['ownOperator' => $ownOperator, 'ownRecentRatings' => $ownRecentRatings])
    @include('partials.president.breakdown-chart', ['breakdown' => $breakdown])
</div>

@include('partials.president.members-section', ['members' => $members, 'memberDetailUrl' => route('president.members')])

<div id="presidentMemberModal" class="tw-modal-backdrop hidden">
    <div class="tw-modal" role="dialog" aria-modal="true" aria-labelledby="presidentMemberModalBody">
        <div id="presidentMemberModalBody"><!-- populated by AJAX --></div>
    </div>
</div>
@endsection