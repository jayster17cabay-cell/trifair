@extends('layouts.superadmin')

@section('title', 'Operators')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title">
            @if (request('status') === 'pending')
                <i class="bi bi-hourglass-split mr-2 text-amber-500"></i>Pending Approvals
            @else
                <i class="bi bi-people mr-2 text-navy-600"></i>Operators
            @endif
        </h1>
        <p class="tw-page-sub">
            @if (request('status') === 'pending')
                Review and approve new operator registrations
            @else
                Manage all registered tricycle operators
            @endif
        </p>
    </div>
    <a href="{{ route('superadmin.operators.create') }}" class="tw-btn tw-btn-gold">
        <i class="bi bi-person-plus"></i>Add Operator
    </a>
</div>

@if (request('status') === 'pending')
    <div class="mb-4">
        <a href="{{ route('superadmin.operators') }}" class="tw-btn tw-btn-sm tw-btn-outline">
            <i class="bi bi-arrow-left"></i>Back to All Operators
        </a>
    </div>
@endif

<div class="mb-4 max-w-md">
    <div class="tw-input-group">
        <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
        <input type="text" id="searchInput" class="tw-input" placeholder="Search operators..." value="{{ $search ?? '' }}" oninput="liveSearch(this.value)">
        <button type="button" class="shrink-0 bg-navy-600 px-4 text-white transition hover:bg-navy-700" onclick="liveSearch(document.getElementById('searchInput').value)">
            <i class="bi bi-search"></i>
        </button>
        @if ($search)
            <a href="{{ route('superadmin.operators') }}" class="inline-flex shrink-0 items-center bg-slate-100 px-3 text-slate-500 transition hover:text-slate-700">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </div>
</div>

<div id="operatorsTable" class="tw-table-wrap">
    <div class="overflow-x-auto">
        <table class="tw-table">
            <thead>
                <tr>
                    <th class="tw-th">#</th>
                    <th class="tw-th">Name</th>
                    <th class="tw-th">TODA</th>
                    <th class="tw-th">Email</th>
                    <th class="tw-th">Plate #</th>
                    <th class="tw-th">Body #</th>
                    <th class="tw-th">Contact</th>
                    <th class="tw-th">Status</th>
                    <th class="tw-th text-center">QR Code</th>
                    <th class="tw-th text-right">Actions</th>
                </tr>
            </thead>
            @include('superadmin.operators._table')
        </table>
    </div>
    <div id="paginationLinks">
        @if ($operators->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">
                {{ $operators->links('pagination::tailwind') }}
            </div>
        @endif
    </div>
</div>

<script>
    let searchTimeout;
    function liveSearch(val) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('search', val);
            fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => {
                    document.querySelector('#operatorsTable tbody').outerHTML = d.html;
                    document.querySelector('#paginationLinks').innerHTML = d.pagination;
                });
        }, 350);
    }
    document.getElementById('searchInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') e.preventDefault();
    });
</script>
@endsection
