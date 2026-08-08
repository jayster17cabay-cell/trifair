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

<div class="mb-3 grid grid-cols-2 gap-2.5 md:grid-cols-3 xl:grid-cols-6">
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.operators'), 'icon' => 'bi-people', 'iconClass' => 'bg-blue-50 text-blue-600', 'value' => $totalOperators, 'label' => 'Operators', 'live' => 'totalOperators']])
    @include('partials.dashboard-kpi', ['kpi' => ['icon' => 'bi-person-check', 'iconClass' => 'bg-blue-50 text-blue-600', 'value' => $activeOperators, 'label' => 'Active', 'live' => 'activeOperators']])
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.ratings'), 'icon' => 'bi-star', 'iconClass' => 'bg-blue-50 text-blue-600', 'value' => $totalRatings, 'label' => 'Ratings', 'live' => 'totalRatings']])
    @include('partials.dashboard-kpi', ['kpi' => ['icon' => 'bi-award', 'iconClass' => 'bg-blue-50 text-blue-600', 'value' => number_format($averageRating ?? 0, 1), 'label' => 'Avg Rating', 'live' => 'averageRating']])
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.ratings'), 'icon' => 'bi-flag', 'iconClass' => 'bg-gold-50 text-gold-800', 'value' => $totalComplaints, 'label' => 'Complaints', 'live' => 'totalComplaints']])
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.todas'), 'icon' => 'bi-diagram-3', 'iconClass' => 'bg-blue-50 text-blue-600', 'value' => $totalTodas, 'label' => 'TODA', 'live' => 'totalTodas']])
</div>

@include('partials.complaint-breakdown-modal')

<div class="mb-3 grid gap-3 lg:grid-cols-2">
    @include('partials.complaint-bar-chart')
    @include('partials.rating-distribution-chart')
</div>

@include('partials.dashboard-todas', ['membersUrl' => url('/tfrb-officer/toda')])

@include('partials.dashboard-recent', [
    'complaintsRoute' => 'tfrb-officer.complaints',
    'ratingsRoute' => 'tfrb-officer.ratings',
])

@include('partials.dashboard-live')
@endsection
