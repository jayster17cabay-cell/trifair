@extends('layouts.superadmin')

@section('title', 'Dashboard')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white px-4 py-3 shadow-sm">
    <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
        <span class="relative flex h-2.5 w-2.5">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
        </span>
        <span id="admLiveState">Live</span>
    </div>
    <div class="text-sm font-medium text-slate-500" data-live-clock="datetime"></div>
</div>

@if (isset($unreadCount) && $unreadCount > 0)
<div id="unreadBanner" class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 to-sky-50 p-4">
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-navy-600 text-white">
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

<div class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-navy-700 via-navy-600 to-navy-500 p-6 text-white shadow-soft sm:p-8">
    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-[radial-gradient(circle,rgba(245,166,35,0.18)_0%,transparent_70%)]"></div>
    <div class="relative z-10 flex flex-wrap items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">
                Welcome back, <span class="text-gold">{{ explode(' ', Auth::user()->name)[0] }}</span>
            </h2>
            <p class="mt-1 text-sm text-slate-300">Here's what's happening across all TODAs</p>
        </div>
        <div class="flex flex-wrap items-center gap-5 sm:gap-6">
            <div class="text-center">
                <div class="text-3xl font-black" data-live="totalOperators">{{ $totalOperators }}</div>
                <div class="mt-1 text-[11px] font-medium uppercase tracking-wider text-slate-300">Operators</div>
            </div>
            <div class="hidden h-12 w-px bg-white/15 sm:block"></div>
            <div class="text-center">
                <div class="text-3xl font-black text-gold" data-live="averageRating">{{ number_format($averageRating ?? 0, 1) }}</div>
                <div class="mt-1 text-[11px] font-medium uppercase tracking-wider text-slate-300">Avg Rating</div>
            </div>
            @if ($totalComplaints > 0)
            <div class="hidden h-12 w-px bg-white/15 sm:block"></div>
            <div class="cursor-pointer text-center" onclick="showComplaintModal()" title="View complaints by type">
                <div class="relative mx-auto h-[58px] w-[58px]">
                    <canvas id="complaintDonut" width="58" height="58"></canvas>
                    <div class="absolute inset-0 flex items-center justify-center text-sm font-black" data-live="totalComplaints">{{ $totalComplaints }}</div>
                </div>
                <div class="mt-1 text-[11px] font-medium uppercase tracking-wider text-slate-300">Complaints</div>
            </div>
            @else
            <div class="hidden h-12 w-px bg-white/15 sm:block"></div>
            <div class="text-center">
                <div class="text-3xl font-black" data-live="totalComplaints">{{ $totalComplaints }}</div>
                <div class="mt-1 text-[11px] font-medium uppercase tracking-wider text-slate-300">Complaints</div>
            </div>
            @endif
            <div class="hidden h-12 w-px bg-white/15 sm:block"></div>
            <div class="text-center">
                <div class="text-3xl font-black" data-live="totalOfficers">{{ $totalOfficers }}</div>
                <div class="mt-1 text-[11px] font-medium uppercase tracking-wider text-slate-300">Officers</div>
            </div>
        </div>
    </div>
</div>

@include('partials.complaint-breakdown-modal')

<div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
    <a href="{{ route('superadmin.operators.create') }}" class="tw-card group flex flex-col items-center gap-2.5 p-4 text-center transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-lg text-emerald-600 transition group-hover:scale-105"><i class="bi bi-person-plus"></i></div>
        <span class="text-sm font-semibold text-slate-700">Add Operator</span>
    </a>
    <a href="{{ route('superadmin.officers') }}" class="tw-card group flex flex-col items-center gap-2.5 p-4 text-center transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-navy-600/10 text-lg text-navy-600 transition group-hover:scale-105"><i class="bi bi-shield"></i></div>
        <span class="text-sm font-semibold text-slate-700">TFRB Officers</span>
    </a>
    <a href="{{ route('superadmin.todas') }}" class="tw-card group flex flex-col items-center gap-2.5 p-4 text-center transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-50 text-lg text-cyan-600 transition group-hover:scale-105"><i class="bi bi-diagram-3"></i></div>
        <span class="text-sm font-semibold text-slate-700">TODA</span>
    </a>
    <a href="{{ route('superadmin.ratings') }}" class="tw-card group flex flex-col items-center gap-2.5 p-4 text-center transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gold/10 text-lg text-gold-dark transition group-hover:scale-105"><i class="bi bi-star"></i></div>
        <span class="text-sm font-semibold text-slate-700">Ratings</span>
    </a>
    <a href="{{ route('superadmin.reports') }}" class="tw-card group flex flex-col items-center gap-2.5 p-4 text-center transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-lg text-violet-600 transition group-hover:scale-105"><i class="bi bi-bar-chart"></i></div>
        <span class="text-sm font-semibold text-slate-700">Reports</span>
    </a>
    <a href="{{ route('superadmin.complaints') }}" class="tw-card group flex flex-col items-center gap-2.5 p-4 text-center transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 text-lg text-red-600 transition group-hover:scale-105"><i class="bi bi-flag"></i></div>
        <span class="text-sm font-semibold text-slate-700">Complaints</span>
    </a>
    <a href="{{ route('superadmin.activity-logs') }}" class="tw-card group flex flex-col items-center gap-2.5 p-4 text-center transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-lg text-slate-500 transition group-hover:scale-105"><i class="bi bi-clock-history"></i></div>
        <span class="text-sm font-semibold text-slate-700">Logs</span>
    </a>
</div>

@if ($totalTodas > 0)
<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($todaStats as $toda)
        <button type="button" onclick="showTodaMembers({{ $toda->id }}, @js($toda->name))" class="tw-card flex cursor-pointer items-center gap-3 p-4 text-left transition hover:-translate-y-0.5 hover:shadow-soft">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-navy-600/10 text-lg text-navy-600">
                <i class="bi bi-diagram-3"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-bold text-slate-800">{{ $toda->name }}</div>
                @if ($toda->area)
                    <div class="mt-0.5 text-xs text-slate-500"><i class="bi bi-geo-alt"></i> {{ $toda->area }}</div>
                @endif
            </div>
            <div class="flex shrink-0 gap-3 text-center">
                <div>
                    <div class="text-sm font-black text-navy-600">{{ $toda->operators_count }}</div>
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total</div>
                </div>
                <div>
                    <div class="text-sm font-black text-emerald-600">{{ $toda->active_operators_count }}</div>
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Active</div>
                </div>
            </div>
        </button>
    @endforeach
</div>
@endif

<div class="grid gap-5 lg:grid-cols-2">
    <div class="tw-card">
        <div class="tw-card-pad flex items-center justify-between border-b border-slate-100">
            <h3 class="tw-card-title"><i class="bi bi-exclamation-triangle mr-1 text-red-600"></i> Recent Complaints</h3>
            @if ($totalComplaints > 5)
                <a href="{{ route('superadmin.complaints') }}" class="tw-btn tw-btn-sm tw-btn-ghost">View all <i class="bi bi-arrow-right"></i></a>
            @endif
        </div>
        <div class="max-h-[360px] divide-y divide-slate-100 overflow-y-auto" data-live-list="complaints">
            @include('partials.dashboard-list-complaints')
        </div>
    </div>

    <div class="tw-card">
        <div class="tw-card-pad border-b border-slate-100">
            <h3 class="tw-card-title"><i class="bi bi-trophy mr-1 text-gold"></i> Top Rated Operators</h3>
        </div>
        <div class="max-h-[360px] divide-y divide-slate-100 overflow-y-auto" data-live-list="top">
            @include('partials.dashboard-list-top')
        </div>
    </div>
</div>

<div class="mt-5 tw-card">
    <div class="tw-card-pad flex items-center justify-between border-b border-slate-100">
        <h3 class="tw-card-title"><i class="bi bi-clock-history mr-1 text-navy-600"></i> Recent Ratings</h3>
        <a href="{{ route('superadmin.ratings') }}" class="tw-btn tw-btn-sm tw-btn-ghost">View all <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="max-h-[480px] divide-y divide-slate-100 overflow-y-auto" data-live-list="ratings">
        @include('partials.dashboard-list-ratings')
    </div>
</div>

@include('partials.toda-members-modal', ['membersUrl' => url('/superadmin/toda'), 'badgeVariant' => 'bootstrap'])
@include('partials.dashboard-live')
@endsection
