@extends('layouts.superadmin')

@section('title', 'Dashboard')

@section('content')
@include('partials.dashboard-hero', [
    'eyebrow' => 'TriFair Superadmin',
    'subtitle' => "Here's what's happening across all TODAs",
    'actionHref' => route('superadmin.reports'),
    'actionLabel' => 'View Reports',
    'actionIcon' => 'bi-bar-chart',
])

<div class="space-y-6">
    <section>
        <h2 class="tw-section-title">Overview</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @include('partials.dashboard-kpi', ['kpi' => ['href' => route('superadmin.operators'), 'icon' => 'bi-people', 'value' => $totalOperators, 'label' => 'Operators', 'live' => 'totalOperators']])
            @include('partials.dashboard-kpi', ['kpi' => ['href' => route('superadmin.operators', ['status' => 'active']), 'icon' => 'bi-person-check', 'value' => $activeOperators, 'label' => 'Active', 'live' => 'activeOperators']])
            @include('partials.dashboard-kpi', ['kpi' => ['href' => route('superadmin.operators', ['status' => 'inactive']), 'icon' => 'bi-person-dash', 'value' => $inactiveOperators, 'label' => 'Inactive', 'live' => 'inactiveOperators']])
            @include('partials.dashboard-kpi', ['kpi' => ['href' => route('superadmin.operators', ['status' => 'suspended']), 'icon' => 'bi-slash-circle', 'value' => $suspendedOperators, 'label' => 'Suspended', 'live' => 'suspendedOperators']])
            @include('partials.dashboard-kpi', ['kpi' => ['href' => route('superadmin.ratings'), 'icon' => 'bi-star', 'value' => $totalRatings, 'label' => 'Ratings', 'live' => 'totalRatings']])
            @include('partials.dashboard-kpi', ['kpi' => ['href' => route('superadmin.ratings'), 'icon' => 'bi-award', 'value' => number_format($averageRating ?? 0, 1), 'label' => 'Avg Rating', 'live' => 'averageRating']])
            @include('partials.dashboard-kpi', ['kpi' => ['href' => route('superadmin.complaints'), 'icon' => 'bi-flag', 'value' => $totalComplaints, 'label' => 'Complaints', 'live' => 'totalComplaints']])
            @include('partials.dashboard-kpi', ['kpi' => ['href' => route('superadmin.todas'), 'icon' => 'bi-diagram-3', 'value' => $totalTodas, 'label' => 'TODA', 'live' => 'totalTodas']])
            @include('partials.dashboard-kpi', ['kpi' => ['href' => route('superadmin.officers'), 'icon' => 'bi-shield', 'value' => $totalOfficers, 'label' => 'Officers', 'live' => 'totalOfficers']])
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
        @include('partials.dashboard-todas', ['membersUrl' => url('/superadmin/toda'), 'addMemberUrl' => route('superadmin.operators.create')])
    </section>

    <section>
        @include('partials.dashboard-recent', [
            'complaintsRoute' => 'superadmin.complaints',
            'ratingsRoute' => 'superadmin.ratings',
        ])
    </section>
</div>

@include('partials.complaint-breakdown-modal')

@include('partials.dashboard-live')
@endsection
