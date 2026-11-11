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

<div class="mb-5 flex w-full max-w-xl flex-wrap items-center gap-2">
    <div class="tw-input-group flex-1 rounded-xl border border-slate-200 bg-white shadow-sm transition focus-within:border-navy-500 focus-within:ring-2 focus-within:ring-navy-600/10">
        <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
        <input type="text" id="officerSearchInput" class="tw-input w-full py-2.5" placeholder="Search officers by name or email..." value="{{ $search ?? '' }}" aria-label="Search officers by name or email">
        @if ($search)
            <a href="{{ route('superadmin.officers') }}" class="inline-flex shrink-0 items-center bg-slate-100 px-3 text-slate-500 transition hover:text-slate-700" aria-label="Clear search">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </div>
    <button type="button" class="tw-btn tw-btn-sm tw-btn-navy" onclick="liveOfficerSearch(document.getElementById('officerSearchInput').value.trim())" aria-label="Search officers">
        <i class="bi bi-search"></i><span class="hidden sm:inline">Search</span>
    </button>
</div>

<div id="officersTable" class="tw-table-scroll-wrap">
    <table class="tw-table min-w-[38rem]">
        <thead class="tw-thead-sticky">
            <tr>
                <th scope="col" class="tw-th">Officer</th>
                <th scope="col" class="tw-th hidden md:table-cell">Phone</th>
                <th scope="col" class="tw-th">Status</th>
                <th scope="col" class="tw-th">Joined</th>
                <th scope="col" class="tw-th text-right">Actions</th>
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
    function liveOfficerSearch(val) {
        const url = new URL(window.location.href);
        if (val) { url.searchParams.set('search', val); } else { url.searchParams.delete('search'); }
        url.searchParams.delete('page');
        fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => {
                document.querySelector('#officersTable tbody').outerHTML = d.html;
                document.querySelector('#paginationLinks').innerHTML = d.pagination;
            });
    }
    // Search on Enter only: typing must never fire a request on its own.
    document.getElementById('officerSearchInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            liveOfficerSearch(e.target.value.trim());
        }
    });
</script>
@endsection
