@extends('layouts.tfrb-officer')

@section('title', 'Dashboard')

@section('content')
@include('partials.dashboard-hero', [
    'eyebrow' => 'TFRB Officer',
    'subtitle' => "Here's what's happening in your TODA today",
    'actionHref' => route('tfrb-officer.reports'),
    'actionLabel' => 'View Reports',
    'actionIcon' => 'bi-bar-chart-line',
])

<div class="grid gap-4 xl:grid-cols-12 xl:items-start">
    <div class="space-y-4 xl:col-span-8">
        <section>
            <h2 class="tw-section-title">Overview</h2>
            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4">
                @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.operators'), 'icon' => 'bi-people', 'value' => $totalOperators, 'label' => 'Operators', 'live' => 'totalOperators']])
                @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.operators', ['status' => 'active']), 'icon' => 'bi-person-check', 'value' => $activeOperators, 'label' => 'Active', 'live' => 'activeOperators']])
                @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.operators', ['status' => 'inactive']), 'icon' => 'bi-person-dash', 'value' => $inactiveOperators, 'label' => 'Inactive', 'live' => 'inactiveOperators']])
                @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.operators', ['status' => 'suspended']), 'icon' => 'bi-slash-circle', 'value' => $suspendedOperators, 'label' => 'Suspended', 'live' => 'suspendedOperators']])
                @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.ratings'), 'icon' => 'bi-star', 'value' => $totalRatings, 'label' => 'Ratings', 'live' => 'totalRatings']])
                @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.ratings'), 'icon' => 'bi-award', 'value' => number_format($averageRating ?? 0, 1), 'label' => 'Avg Rating', 'live' => 'averageRating']])
                @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.complaints'), 'icon' => 'bi-flag', 'value' => $totalComplaints, 'label' => 'Complaints', 'live' => 'totalComplaints']])
                @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.todas'), 'icon' => 'bi-diagram-3', 'value' => $totalTodas, 'label' => 'TODA', 'live' => 'totalTodas']])
            </div>
        </section>

        <section>
            <h2 class="tw-section-title">Analytics</h2>
            <div class="grid gap-3 lg:grid-cols-2">
                @include('partials.complaint-bar-chart')
                @include('partials.rating-distribution-chart')
            </div>
        </section>

        <section>
            @include('partials.dashboard-todas', ['membersUrl' => url('/tfrb-officer/toda'), 'addMemberUrl' => route('tfrb-officer.operators.create')])
        </section>
    </div>

    <div class="xl:col-span-4">
        @include('partials.dashboard-recent', [
            'complaintsRoute' => 'tfrb-officer.complaints',
            'ratingsRoute' => 'tfrb-officer.ratings',
        ])
    </div>
</div>

@include('partials.complaint-breakdown-modal')

@include('partials.dashboard-live')
@endsection
