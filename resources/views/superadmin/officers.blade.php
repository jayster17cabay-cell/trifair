@extends('layouts.superadmin')

@section('title', 'Manage TFRB Officers')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-shield mr-2 text-gold"></i>TFRB Officer Management</h1>
        <p class="tw-page-sub">Manage TFRB Officers</p>
    </div>
    <a href="{{ route('superadmin.officers.create') }}" class="tw-btn tw-btn-gold">
        <i class="bi bi-shield-plus"></i>Add Officer
    </a>
</div>

<div class="mb-6 grid max-w-lg grid-cols-3 gap-3">
    <div class="tw-stat">
        <div class="tw-stat-icon tw-stat-icon-navy"><i class="bi bi-shield"></i></div>
        <div class="tw-stat-num">{{ $totalOfficers }}</div>
        <div class="tw-stat-label">Total</div>
    </div>
    <div class="tw-stat">
        <div class="tw-stat-icon tw-stat-icon-emerald"><i class="bi bi-check-circle"></i></div>
        <div class="tw-stat-num">{{ $activeOfficers }}</div>
        <div class="tw-stat-label">Active</div>
    </div>
    <div class="tw-stat">
        <div class="tw-stat-icon tw-stat-icon-violet"><i class="bi bi-patch-check"></i></div>
        <div class="tw-stat-num">{{ $verifiedOfficers }}</div>
        <div class="tw-stat-label">Verified</div>
    </div>
</div>

<div class="mb-4 max-w-md">
    <div class="tw-input-group">
        <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
        <input type="text" id="officerSearchInput" class="tw-input" placeholder="Search officers by name or email..." value="{{ $search ?? '' }}" oninput="liveOfficerSearch(this.value)" aria-label="Search officers by name or email">
        <button type="button" class="shrink-0 bg-navy-600 px-4 text-white transition hover:bg-navy-700" onclick="liveOfficerSearch(document.getElementById('officerSearchInput').value)" aria-label="Search">
            <i class="bi bi-search"></i>
        </button>
        @if ($search)
            <a href="{{ route('superadmin.officers') }}" class="inline-flex shrink-0 items-center bg-slate-100 px-3 text-slate-500 transition hover:text-slate-700" aria-label="Clear search">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </div>
</div>

<div id="officersTable" class="tw-table-scroll-wrap">
    <table class="tw-table min-w-[38rem]">
        <thead class="tw-thead-sticky">
            <tr>
                <th class="tw-th">Officer</th>
                <th class="tw-th hidden md:table-cell">Phone</th>
                <th class="tw-th">Status</th>
                <th class="tw-th">Joined</th>
                <th class="tw-th text-right">Actions</th>
            </tr>
        </thead>
        @include('partials.admin.officers-table')
    </table>
</div>
<div id="paginationLinks">
    @if ($officers->hasPages())
        <div class="mt-3">
            {{ $officers->links('pagination::tailwind') }}
        </div>
    @endif
</div>

@include('partials.admin.officer-details-modal')

<script>
    let officerSearchTimeout;
    function liveOfficerSearch(val) {
        clearTimeout(officerSearchTimeout);
        officerSearchTimeout = setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('search', val);
            fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => {
                    document.querySelector('#officersTable tbody').outerHTML = d.html;
                    document.querySelector('#paginationLinks').innerHTML = d.pagination;
                });
        }, 350);
    }
    document.getElementById('officerSearchInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') e.preventDefault();
    });
</script>
@endsection
