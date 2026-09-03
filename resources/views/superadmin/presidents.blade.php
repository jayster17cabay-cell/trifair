@extends('layouts.superadmin')

@section('title', 'Manage TODA Presidents')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-award mr-2 text-gold"></i>TODA President Management</h1>
        <p class="tw-page-sub">Assign a president to oversee the members of each TODA</p>
    </div>
    <a href="{{ route('superadmin.presidents.create') }}" class="tw-btn tw-btn-gold">
        <i class="bi bi-award"></i>Add President
    </a>
</div>

<div class="mb-6 grid max-w-lg grid-cols-2 gap-3">
    <div class="tw-stat">
        <div class="tw-stat-icon tw-stat-icon-navy"><i class="bi bi-award"></i></div>
        <div class="tw-stat-num">{{ $totalPresidents }}</div>
        <div class="tw-stat-label">Total Presidents</div>
    </div>
    <div class="tw-stat">
        <div class="tw-stat-icon tw-stat-icon-emerald"><i class="bi bi-diagram-3"></i></div>
        <div class="tw-stat-num">{{ $assignedPresidents }}</div>
        <div class="tw-stat-label">Assigned to TODA</div>
    </div>
</div>

<div class="mb-4 max-w-md">
    <div class="tw-input-group">
        <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
        <input type="text" id="presidentSearchInput" class="tw-input" placeholder="Search presidents by name or email..." value="{{ $search ?? '' }}" oninput="livePresidentSearch(this.value)" aria-label="Search presidents by name or email">
        <button type="button" class="shrink-0 bg-navy-600 px-4 text-white transition hover:bg-navy-700" onclick="livePresidentSearch(document.getElementById('presidentSearchInput').value)" aria-label="Search">
            <i class="bi bi-search"></i>
        </button>
        @if ($search)
            <a href="{{ route('superadmin.presidents') }}" class="inline-flex shrink-0 items-center bg-slate-100 px-3 text-slate-500 transition hover:text-slate-700" aria-label="Clear search">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </div>
</div>

<div id="presidentsTable" class="tw-table-scroll-wrap">
    <table class="tw-table min-w-[38rem]">
        <thead class="tw-thead-sticky">
            <tr>
                <th class="tw-th">President</th>
                <th class="tw-th">TODA</th>
                <th class="tw-th">Status</th>
                <th class="tw-th hidden md:table-cell">Joined</th>
                <th class="tw-th text-right">Actions</th>
            </tr>
        </thead>
        @include('partials.admin.presidents-table')
    </table>
</div>
<div id="paginationLinks">
    @if ($presidents->hasPages())
        <div class="mt-3">
            {{ $presidents->links('pagination::tailwind') }}
        </div>
    @endif
</div>

<script>
    let presidentSearchTimeout;
    function livePresidentSearch(val) {
        clearTimeout(presidentSearchTimeout);
        presidentSearchTimeout = setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('search', val);
            fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => {
                    document.querySelector('#presidentsTable tbody').outerHTML = d.html;
                    document.querySelector('#paginationLinks').innerHTML = d.pagination;
                });
        }, 350);
    }
    document.getElementById('presidentSearchInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') e.preventDefault();
    });
</script>

@include('partials.toda-members-modal', ['membersUrl' => url('/superadmin/toda'), 'addMemberUrl' => route('superadmin.operators.create')])
@endsection
