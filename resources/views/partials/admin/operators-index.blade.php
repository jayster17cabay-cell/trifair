{{-- Shared operators index page body. Requires: $routePrefix, $operators, $search, $status, $account, $activeStatusCount, $inactiveStatusCount, $activeOperators --}}

@php $currentStatus = request('status'); @endphp

@if (!empty($membershipRequests) && $membershipRequests->isNotEmpty())
    @include('partials.admin.membership-requests', ['membershipRequests' => $membershipRequests, 'routePrefix' => $routePrefix, 'membershipStage' => $membershipStage ?? ($routePrefix === 'tfrb-officer' ? 'tfrb' : 'superadmin')])
@endif

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
        @php
            $exportTodaOptions = ['' => 'All TODAs'];
            foreach ($todas as $t) {
                $exportTodaOptions[$t->id] = $t->name;
            }
            $exportOperatorOptions = ['' => 'All operators'];
            $exportOperatorData = [];
            foreach ($activeOperators as $op) {
                $exportOperatorOptions[$op->id] = $op->user->name ?? 'Unknown';
                $exportOperatorData[$op->id] = (int) $op->toda_id;
            }
        @endphp
        @include('partials.admin.export-dropdown', [
            'exportRoute' => route($routePrefix . '.operators.export'),
            'exportLabel' => 'Operators',
            'exportIcon' => 'bi-people',
            'activeOperators' => null,
            'exportFilters' => [
                ['type' => 'select', 'name' => 'toda_id', 'label' => 'TODA', 'options' => $exportTodaOptions, 'value' => '', 'help' => 'Leave as "All TODAs" to include every TODA.'],
                ['type' => 'select', 'name' => 'operator_id', 'label' => 'Operator', 'options' => $exportOperatorOptions, 'value' => '', 'option_data' => $exportOperatorData, 'help' => 'Pick one operator to export only them. Members of the selected TODA appear when a TODA filter is active.'],
            ],
            'preservedParams' => array_filter(['search' => $search ?? null, 'status' => $currentStatus ?? null, 'account' => $account ?? null]),
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
        <div class="tw-stat-num">{{ $activeStatusCount }}</div>
        <div class="tw-stat-label">Active</div>
    </div>
</div>

<div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route($routePrefix . '.operators') }}" class="{{ !$currentStatus && $account === 'all' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
        <i class="bi bi-people"></i> All
    </a>
    <a href="{{ route($routePrefix . '.operators', ['status' => 'active']) }}" class="{{ $currentStatus === 'active' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
        <i class="bi bi-person-check"></i> Active <span class="tw-badge tw-badge-green ml-1">{{ $activeStatusCount }}</span>
    </a>
    <a href="{{ route($routePrefix . '.operators', ['status' => 'inactive']) }}" class="{{ $currentStatus === 'inactive' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
        <i class="bi bi-person-dash"></i> Inactive <span class="tw-badge tw-badge-gray ml-1">{{ $inactiveStatusCount }}</span>
    </a>
    @if (!empty($archivedCount))
        <a href="{{ route($routePrefix . '.operators', ['status' => 'archived']) }}" class="{{ $currentStatus === 'archived' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
            <i class="bi bi-archive"></i> Archived <span class="tw-badge tw-badge-gray ml-1">{{ $archivedCount }}</span>
        </a>
    @endif
    @if (!empty($pendingCount))
        <a href="{{ route($routePrefix . '.operators', ['status' => 'pending']) }}" class="{{ $currentStatus === 'pending' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
            <i class="bi bi-hourglass-split"></i> Pending Approvals <span class="tw-badge tw-badge-amber ml-1">{{ $pendingCount }}</span>
        </a>
    @endif
    @if (!empty($suspendedCount))
        <a href="{{ route($routePrefix . '.operators', ['status' => 'suspended']) }}" class="{{ $currentStatus === 'suspended' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
            <i class="bi bi-slash-circle"></i> Suspended <span class="tw-badge tw-badge-amber ml-1">{{ $suspendedCount }}</span>
        </a>
    @endif
</div>

<div class="mb-4 max-w-md">
    <div class="tw-input-group">
        <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
        <input type="text" id="searchInput" class="tw-input" placeholder="Search operators..." value="{{ $search ?? '' }}" oninput="liveSearch(this.value)" aria-label="Search operators">
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
                <th scope="col" class="tw-th">Operator</th>
                <th scope="col" class="tw-th">TODA</th>
                <th scope="col" class="tw-th hidden md:table-cell">Contact</th>
                <th scope="col" class="tw-th hidden lg:table-cell">Franchise</th>
                <th scope="col" class="tw-th">Status</th>
                <th scope="col" class="tw-th text-right">Actions</th>
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

@include('partials.admin.operator-franchise-modal')

@include('partials.admin.operator-suspend-modal')

<script>
    let searchTimeout;
    function liveSearch(val) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('search', val);
            url.searchParams.delete('page');
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
