@extends('layouts.tfrb-officer')

@section('title', 'Manage TODA Presidents')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-award mr-2 text-gold"></i>TODA President Management</h1>
        <p class="tw-page-sub">Assign a president to oversee the members of each TODA</p>
    </div>
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

<div class="mb-5 flex w-full max-w-xl flex-wrap items-center gap-2">
    <div class="tw-input-group flex-1 rounded-xl border border-slate-200 bg-white shadow-sm transition focus-within:border-navy-500 focus-within:ring-2 focus-within:ring-navy-600/10">
        <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
        <input type="text" id="presidentSearchInput" class="tw-input w-full py-2.5" placeholder="Search presidents by name or email..." value="{{ $search ?? '' }}" aria-label="Search presidents by name or email">
        @if ($search)
            <a href="{{ route('tfrb-officer.presidents') }}" class="inline-flex shrink-0 items-center bg-slate-100 px-3 text-slate-500 transition hover:text-slate-700" aria-label="Clear search">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </div>
    <button type="button" class="tw-btn tw-btn-sm tw-btn-navy" onclick="livePresidentSearch(document.getElementById('presidentSearchInput').value.trim())" aria-label="Search presidents">
        <i class="bi bi-search"></i><span class="hidden sm:inline">Search</span>
    </button>
</div>

<div id="presidentsTable" class="tw-table-scroll-wrap">
    <table class="tw-table min-w-[38rem]">
        <thead class="tw-thead-sticky">
            <tr>
                <th scope="col" class="tw-th">President</th>
                <th scope="col" class="tw-th">TODA</th>
                <th scope="col" class="tw-th">Status</th>
                <th scope="col" class="tw-th hidden md:table-cell">Joined</th>
                <th scope="col" class="tw-th text-right">Actions</th>
            </tr>
        </thead>
        @php $routePrefix = 'tfrb-officer'; @endphp
        @include('partials.admin.presidents-table', ['routePrefix' => $routePrefix])
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
    function livePresidentSearch(val) {
        const url = new URL(window.location.href);
        if (val) { url.searchParams.set('search', val); } else { url.searchParams.delete('search'); }
        url.searchParams.delete('page');
        fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => {
                document.querySelector('#presidentsTable tbody').outerHTML = d.html;
                document.querySelector('#paginationLinks').innerHTML = d.pagination;
            });
    }
    // Search on Enter only: typing must never fire a request on its own.
    document.getElementById('presidentSearchInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            livePresidentSearch(e.target.value.trim());
        }
    });
</script>

@include('partials.toda-members-modal', ['membersUrl' => url('/tfrb-officer/toda'), 'addMemberUrl' => route('tfrb-officer.operators.create')])
@include('partials.admin.operator-suspend-modal')
@endsection
