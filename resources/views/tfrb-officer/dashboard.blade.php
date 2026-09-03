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
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.operators'), 'icon' => 'bi-people', 'value' => $totalOperators, 'label' => 'Operators', 'live' => 'totalOperators']])
    @include('partials.dashboard-kpi', ['kpi' => ['icon' => 'bi-person-check', 'value' => $activeOperators, 'label' => 'Active', 'live' => 'activeOperators']])
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.ratings'), 'icon' => 'bi-star', 'value' => $totalRatings, 'label' => 'Ratings', 'live' => 'totalRatings']])
    @include('partials.dashboard-kpi', ['kpi' => ['icon' => 'bi-award', 'value' => number_format($averageRating ?? 0, 1), 'label' => 'Avg Rating', 'live' => 'averageRating']])
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.complaints'), 'icon' => 'bi-flag', 'value' => $totalComplaints, 'label' => 'Complaints', 'live' => 'totalComplaints']])
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('tfrb-officer.todas'), 'icon' => 'bi-diagram-3', 'value' => $totalTodas, 'label' => 'TODA', 'live' => 'totalTodas']])
</div>

<div id="pendingReviewBanner" class="mb-3" style="{{ ($pendingReview ?? 0) > 0 ? '' : 'display:none' }}">
    <a href="{{ route('tfrb-officer.complaints') }}" class="flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800 shadow-sm transition hover:border-amber-300 hover:bg-amber-100">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600"><i class="bi bi-flag-fill"></i></span>
        <span class="text-sm font-semibold"><span id="pendingReviewText">{{ $pendingReview ?? 0 }} complaint(s) pending review</span></span>
        <span class="ml-auto text-xs font-bold uppercase tracking-wider text-amber-500">Review <i class="bi bi-chevron-right"></i></span>
    </a>
</div>

@include('partials.complaint-breakdown-modal')

<div class="mb-3 grid gap-3 lg:grid-cols-2">
    @include('partials.complaint-bar-chart')
    @include('partials.rating-distribution-chart')
</div>

@include('partials.dashboard-todas', ['membersUrl' => url('/tfrb-officer/toda'), 'addMemberUrl' => route('tfrb-officer.operators.create')])

@include('partials.dashboard-recent', [
    'complaintsRoute' => 'tfrb-officer.complaints',
    'ratingsRoute' => 'tfrb-officer.ratings',
])

@include('partials.dashboard-live')
@endsection
