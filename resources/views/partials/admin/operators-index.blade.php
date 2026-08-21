{{-- Shared operators index page body. Requires: $routePrefix, $operators, $search, $status --}}

@php $currentStatus = request('status'); @endphp

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title">
            @if ($currentStatus === 'pending')
                <i class="bi bi-hourglass-split mr-2 text-amber-500"></i>Pending Approvals
            @elseif ($currentStatus === 'archived')
                <i class="bi bi-archive mr-2 text-slate-500"></i>Archived Operators
            @else
                <i class="bi bi-people mr-2 text-navy-600"></i>Operators
            @endif
        </h1>
        <p class="tw-page-sub">
            @if ($currentStatus === 'pending')
                Review and approve new operator registrations
            @elseif ($currentStatus === 'archived')
                Archived operators are hidden from active lists but keep their rating history
            @else
                Manage all registered motorcycle operators
            @endif
        </p>
    </div>
    <div class="flex items-center gap-2">
        <form method="GET" action="{{ route($routePrefix . '.operators.export') }}" class="flex items-center gap-1.5">
            @if ($search ?? null)<input type="hidden" name="search" value="{{ $search }}">@endif
            @if ($currentStatus ?? null)<input type="hidden" name="status" value="{{ $currentStatus }}">@endif
            <select name="format" class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs font-medium text-slate-700 focus:border-navy focus:ring-1 focus:ring-navy">
                <option value="csv">CSV</option>
                <option value="word">Word</option>
                <option value="pdf">PDF</option>
            </select>
            <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline"><i class="bi bi-download"></i>Export</button>
        </form>
        @if ($currentStatus === 'pending')
            <a href="{{ route($routePrefix . '.operators') }}" class="tw-btn tw-btn-sm tw-btn-outline">
                <i class="bi bi-arrow-left"></i>Back to All Operators
            </a>
        @elseif ($currentStatus === 'archived')
            <a href="{{ route($routePrefix . '.operators') }}" class="tw-btn tw-btn-sm tw-btn-outline">
                <i class="bi bi-arrow-left"></i>Back to Operators
            </a>
        @else
            <a href="{{ route($routePrefix . '.operators.create') }}" class="tw-btn tw-btn-gold">
                <i class="bi bi-person-plus"></i>Add Operator
            </a>
        @endif
    </div>
</div>

<div class="mb-6 grid max-w-md grid-cols-2 gap-3">
    <div class="tw-stat">
        <div class="tw-stat-icon tw-stat-icon-navy"><i class="bi bi-people"></i></div>
        <div class="tw-stat-num">{{ $operators->total() }}</div>
        <div class="tw-stat-label">Total</div>
    </div>
    <div class="tw-stat">
        <div class="tw-stat-icon tw-stat-icon-emerald"><i class="bi bi-check-circle"></i></div>
        <div class="tw-stat-num">{{ $activeOperatorsCount }}</div>
        <div class="tw-stat-label">Active</div>
    </div>
</div>

<div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route($routePrefix . '.operators') }}" class="{{ !$status ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
        <i class="bi bi-people"></i> All
    </a>
    @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $key => $label)
        <a href="{{ route($routePrefix . '.operators', ['status' => $key]) }}" class="{{ $status === $key ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="mb-4 max-w-md">
    <div class="tw-input-group">
        <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
        <input type="text" id="searchInput" class="tw-input" placeholder="Search operators..." value="{{ $search ?? '' }}" oninput="liveSearch(this.value)" aria-label="Search operators">
        <button type="button" class="shrink-0 bg-navy-600 px-4 text-white transition hover:bg-navy-700" onclick="liveSearch(document.getElementById('searchInput').value)" aria-label="Search">
            <i class="bi bi-search"></i>
        </button>
        @if ($search)
            <a href="{{ route($routePrefix . '.operators') }}" class="inline-flex shrink-0 items-center bg-slate-100 px-3 text-slate-500 transition hover:text-slate-700" aria-label="Clear search">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </div>
</div>

<div id="operatorsTable" class="tw-table-scroll-wrap">
    <table class="tw-table min-w-[38rem]">
        <thead class="tw-thead-sticky">
            <tr>
                <th class="tw-th">Operator</th>
                <th class="tw-th">TODA</th>
                <th class="tw-th hidden md:table-cell">Contact</th>
                <th class="tw-th">Status</th>
                <th class="tw-th text-right">Actions</th>
            </tr>
        </thead>
        @include('partials.admin.operators-table')
    </table>
</div>
<div id="paginationLinks">
    @if ($operators->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $operators->links('pagination::tailwind') }}
        </div>
    @endif
</div>

@include('partials.admin.operator-details-modal', ['routePrefix' => $routePrefix])

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
