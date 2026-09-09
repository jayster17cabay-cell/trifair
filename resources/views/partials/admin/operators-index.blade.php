{{-- Shared operators index page body. Requires: $routePrefix, $operators, $search, $status, $account, $accountsActiveCount, $accountsInactiveCount, $activeOperators --}}

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
        @include('partials.admin.export-dropdown', [
            'exportRoute' => route($routePrefix . '.operators.export'),
            'exportLabel' => 'Operators',
            'exportIcon' => 'bi-people',
            'activeOperators' => $activeOperators ?? collect(),
            'preservedParams' => array_filter(['search' => $search ?? null, 'status' => $currentStatus ?? null]),
        ])
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
        <div class="tw-stat-icon tw-stat-icon-emerald"><i class="bi bi-person-check"></i></div>
        <div class="tw-stat-num">{{ $accountsActiveCount }}</div>
        <div class="tw-stat-label">Account Active</div>
    </div>
</div>

<div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route($routePrefix . '.operators', array_filter(['account' => 'all'])) }}" class="{{ $account === 'all' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
        <i class="bi bi-people"></i> All
    </a>
    <a href="{{ route($routePrefix . '.operators', ['account' => 'active']) }}" class="{{ $account === 'active' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
        <i class="bi bi-person-check"></i> Active <span class="tw-badge tw-badge-green ml-1">{{ $accountsActiveCount }}</span>
    </a>
    <a href="{{ route($routePrefix . '.operators', ['account' => 'inactive']) }}" class="{{ $account === 'inactive' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
        <i class="bi bi-person-dash"></i> Inactive <span class="tw-badge tw-badge-gray ml-1">{{ $accountsInactiveCount }}</span>
    </a>
</div>

<div class="mb-4 max-w-md">
    <div class="tw-input-group">
        <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
        <input type="text" id="searchInput" class="tw-input" placeholder="Search operators..." value="{{ $search ?? '' }}" oninput="liveSearch(this.value)" aria-label="Search operators">
        <button type="button" class="tw-btn tw-btn-gold shrink-0 px-4" onclick="liveSearch(document.getElementById('searchInput').value)" aria-label="Search">
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
