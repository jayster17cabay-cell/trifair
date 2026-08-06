@extends('layouts.superadmin')

@section('title', 'Dashboard')

@section('content')
@if (isset($unreadCount) && $unreadCount > 0)
<div id="unreadBanner" class="mb-3 flex flex-wrap items-center gap-3 rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-sky-50 p-3">
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-navy-600 text-white">
        <i class="bi bi-bell-fill"></i>
    </div>
    <div class="min-w-0 flex-1">
        <strong id="unreadCountText" class="text-sm text-navy-600">{{ $unreadCount }} unread notification{{ $unreadCount > 1 ? 's' : '' }}</strong>
        <div class="text-sm text-slate-500">You have updates waiting for your attention</div>
    </div>
    <a href="{{ route('notifications.index') }}" class="tw-btn tw-btn-sm tw-btn-navy">
        <i class="bi bi-eye"></i> View
    </a>
</div>
@endif

<div class="relative mb-3 overflow-hidden rounded-xl bg-gradient-to-br from-navy-700 via-navy-600 to-navy-500 px-5 py-3 text-white shadow-sm">
    <div class="pointer-events-none absolute -right-10 -top-16 h-40 w-40 rounded-full bg-[radial-gradient(circle,rgba(245,184,0,0.20)_0%,transparent_70%)]"></div>
    <div class="pointer-events-none absolute -bottom-16 -left-10 h-40 w-40 rounded-full bg-[radial-gradient(circle,rgba(46,125,209,0.18)_0%,transparent_70%)]"></div>
    <div class="relative z-10 flex flex-wrap items-center justify-between gap-2">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-gold">TriFair Superadmin</p>
            <h2 class="text-base font-extrabold tracking-tight">
                Welcome back, <span class="text-gold">{{ explode(' ', Auth::user()->name)[0] }}</span>
            </h2>
        </div>
        <p class="text-xs text-slate-300">Here's what's happening across all TODAs</p>
    </div>
</div>

<div class="mb-3 grid grid-cols-2 gap-2.5 md:grid-cols-4 xl:grid-cols-7">
    <a href="{{ route('superadmin.operators') }}" class="flex items-center gap-2.5 rounded-xl border border-slate-100 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-base text-blue-600"><i class="bi bi-people"></i></div>
        <div class="min-w-0">
            <div class="text-lg font-extrabold leading-tight text-slate-900" data-live="totalOperators">{{ $totalOperators }}</div>
            <div class="truncate text-[10px] font-semibold uppercase tracking-wider text-slate-400">Operators</div>
        </div>
    </a>
    <div class="flex items-center gap-2.5 rounded-xl border border-slate-100 bg-white p-3 shadow-sm">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-base text-blue-600"><i class="bi bi-person-check"></i></div>
        <div class="min-w-0">
            <div class="text-lg font-extrabold leading-tight text-slate-900" data-live="activeOperators">{{ $activeOperators }}</div>
            <div class="truncate text-[10px] font-semibold uppercase tracking-wider text-slate-400">Active</div>
        </div>
    </div>
    <a href="{{ route('superadmin.ratings') }}" class="flex items-center gap-2.5 rounded-xl border border-slate-100 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-base text-blue-600"><i class="bi bi-star"></i></div>
        <div class="min-w-0">
            <div class="text-lg font-extrabold leading-tight text-slate-900" data-live="totalRatings">{{ $totalRatings }}</div>
            <div class="truncate text-[10px] font-semibold uppercase tracking-wider text-slate-400">Ratings</div>
        </div>
    </a>
    <div class="flex items-center gap-2.5 rounded-xl border border-slate-100 bg-white p-3 shadow-sm">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-base text-blue-600"><i class="bi bi-award"></i></div>
        <div class="min-w-0">
            <div class="text-lg font-extrabold leading-tight text-slate-900" data-live="averageRating">{{ number_format($averageRating ?? 0, 1) }}</div>
            <div class="truncate text-[10px] font-semibold uppercase tracking-wider text-slate-400">Avg Rating</div>
        </div>
    </div>
    <a href="{{ route('superadmin.complaints') }}" class="flex items-center gap-2.5 rounded-xl border border-slate-100 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gold-50 text-base text-gold-800"><i class="bi bi-flag"></i></div>
        <div class="min-w-0">
            <div class="text-lg font-extrabold leading-tight text-slate-900" data-live="totalComplaints">{{ $totalComplaints }}</div>
            <div class="truncate text-[10px] font-semibold uppercase tracking-wider text-slate-400">Complaints</div>
        </div>
    </a>
    <a href="{{ route('superadmin.todas') }}" class="flex items-center gap-2.5 rounded-xl border border-slate-100 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-base text-blue-600"><i class="bi bi-diagram-3"></i></div>
        <div class="min-w-0">
            <div class="text-lg font-extrabold leading-tight text-slate-900" data-live="totalTodas">{{ $totalTodas }}</div>
            <div class="truncate text-[10px] font-semibold uppercase tracking-wider text-slate-400">TODA</div>
        </div>
    </a>
    <a href="{{ route('superadmin.officers') }}" class="flex items-center gap-2.5 rounded-xl border border-slate-100 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-base text-blue-600"><i class="bi bi-shield"></i></div>
        <div class="min-w-0">
            <div class="text-lg font-extrabold leading-tight text-slate-900" data-live="totalOfficers">{{ $totalOfficers }}</div>
            <div class="truncate text-[10px] font-semibold uppercase tracking-wider text-slate-400">Officers</div>
        </div>
    </a>
</div>

@include('partials.complaint-breakdown-modal')

<div class="mb-3 grid gap-3 lg:grid-cols-2">
    @include('partials.complaint-bar-chart')
    @include('partials.rating-distribution-chart')
</div>

@if ($totalTodas > 0)
<div class="mb-3 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($todaStats as $toda)
        @php
            $todaTotal = $toda->operators_count ?? 0;
            $todaActive = $toda->active_operators_count ?? 0;
            $todaPct = $todaTotal > 0 ? round(($todaActive / $todaTotal) * 100) : 0;
        @endphp
        <button type="button" onclick="showTodaMembers({{ $toda->id }}, @js($toda->name))" class="tw-card flex cursor-pointer items-center gap-2.5 p-3 text-left transition hover:-translate-y-0.5 hover:shadow-soft">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-base text-blue-600">
                <i class="bi bi-diagram-3"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-bold text-slate-800">{{ $toda->name }}</div>
                @if ($toda->area)
                    <div class="truncate text-[11px] text-slate-500"><i class="bi bi-geo-alt"></i> {{ $toda->area }}</div>
                @endif
                <div class="mt-1.5 flex h-1.5 items-center overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-[#22a559]" style="width: {{ $todaPct }}%;"></div>
                </div>
            </div>
            <div class="flex shrink-0 flex-col items-end gap-1">
                @if ($toda->avg_rating !== null)
                    <span class="tw-badge tw-badge-blue"><i class="bi bi-star-fill text-[10px]"></i>{{ number_format($toda->avg_rating, 1) }}</span>
                @else
                    <span class="tw-badge tw-badge-gray">No ratings</span>
                @endif
                <span class="text-xs font-bold text-slate-600">{{ $todaActive }}<span class="font-medium text-slate-400">/{{ $todaTotal }} active</span></span>
            </div>
        </button>
    @endforeach
</div>
@endif

<div class="mb-3 grid gap-3 lg:grid-cols-3">
    <div class="tw-card">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="tw-card-title text-sm"><i class="bi bi-exclamation-triangle mr-1 text-gold"></i> Recent Complaints</h3>
            @if ($totalComplaints > 5)
                <a href="{{ route('superadmin.complaints') }}" class="tw-btn tw-btn-sm tw-btn-ghost">View all <i class="bi bi-arrow-right"></i></a>
            @endif
        </div>
        <div class="max-h-[300px] divide-y divide-slate-100 overflow-y-auto" data-live-list="complaints">
            @include('partials.dashboard-list-complaints')
        </div>
    </div>

    <div class="tw-card">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="tw-card-title text-sm"><i class="bi bi-trophy mr-1 text-gold"></i> Top Rated Operators</h3>
        </div>
        <div class="max-h-[300px] divide-y divide-slate-100 overflow-y-auto" data-live-list="top">
            @include('partials.dashboard-list-top')
        </div>
    </div>

    <div class="tw-card">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="tw-card-title text-sm"><i class="bi bi-clock-history mr-1 text-navy-600"></i> Recent Ratings</h3>
            <a href="{{ route('superadmin.ratings') }}" class="tw-btn tw-btn-sm tw-btn-ghost">View all <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="max-h-[300px] divide-y divide-slate-100 overflow-y-auto" data-live-list="ratings">
            @include('partials.dashboard-list-ratings')
        </div>
    </div>
</div>

@include('partials.toda-members-modal', ['membersUrl' => url('/superadmin/toda'), 'badgeVariant' => 'bootstrap'])
@include('partials.dashboard-live')
@endsection
