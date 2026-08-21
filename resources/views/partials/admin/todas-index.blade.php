{{-- Shared TODA list page body. Requires: $routePrefix, $showManage, $todas, $search --}}

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-diagram-3 mr-2 text-cyan-600"></i>TODA</h1>
        <p class="tw-page-sub">View all Motorcycle Operators and Drivers Associations</p>
    </div>
    @if ($showManage)
        <a href="{{ route($routePrefix . '.todas.create') }}" class="tw-btn tw-btn-gold">
            <i class="bi bi-plus-circle"></i>Add TODA
        </a>
    @endif
</div>

<div class="mb-4 max-w-md">
    <div class="tw-input-group">
        <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
        <input type="text" id="todaSearchInput" class="tw-input" placeholder="Search TODA by name or area..." value="{{ $search ?? '' }}" oninput="liveTodaSearch(this.value)" aria-label="Search TODA by name or area">
        <button type="button" class="shrink-0 bg-navy-600 px-4 text-white transition hover:bg-navy-700" onclick="liveTodaSearch(document.getElementById('todaSearchInput').value)" aria-label="Search">
            <i class="bi bi-search"></i>
        </button>
        @if ($search ?? null)
            <a href="{{ route($routePrefix . '.todas') }}" class="inline-flex shrink-0 items-center bg-slate-100 px-3 text-slate-500 transition hover:text-slate-700" aria-label="Clear search">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </div>
</div>

<div id="todasTable" class="tw-table-scroll-wrap">
    <table class="tw-table min-w-[38rem]">
        <thead class="tw-thead-sticky">
            <tr>
                <th class="tw-th">#</th>
                <th class="tw-th">TODA Name</th>
                <th class="tw-th">Area</th>
                <th class="tw-th">Members</th>
                <th class="tw-th">Status</th>
                @if ($showManage)
                    <th class="tw-th text-right">Actions</th>
                @endif
            </tr>
        </thead>
        @include('partials.admin.toda-members-table', ['todas' => $todas, 'routePrefix' => $routePrefix, 'showManage' => $showManage])
    </table>
</div>
<div id="paginationLinks">
    @if ($todas->hasPages())
        <div class="mt-3">
            {{ $todas->links('pagination::tailwind') }}
        </div>
    @endif
</div>

@include('partials.toda-members-modal', [
    'membersUrl' => url('/' . $routePrefix . '/toda'),
    'addMemberUrl' => route($routePrefix . '.operators.create'),
])

<script>
    let todaSearchTimeout;
    function liveTodaSearch(val) {
        clearTimeout(todaSearchTimeout);
        todaSearchTimeout = setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('search', val);
            fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => {
                    document.querySelector('#todasTable tbody').outerHTML = d.html;
                    document.querySelector('#paginationLinks').innerHTML = d.pagination;
                });
        }, 350);
    }
    document.getElementById('todaSearchInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') e.preventDefault();
    });
</script>
