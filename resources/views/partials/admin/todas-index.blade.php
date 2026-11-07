{{-- Shared TODA list page body. Requires: $routePrefix, $showManage, $todas, $search --}}

<div class="tw-page-head">
    <div class="flex items-start gap-3">
        <div class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-600 to-navy-700 text-white shadow-soft sm:flex">
            <i class="bi bi-diagram-3 text-lg"></i>
        </div>
        <div>
            <p class="tw-eyebrow">Directory</p>
            <h1 class="tw-page-title">TODA</h1>
            <p class="tw-page-sub">View all Motorcycle Operators and Drivers Associations.</p>
        </div>
    </div>
    @if ($showManage)
        <a href="{{ route($routePrefix . '.todas.create') }}" class="tw-btn tw-btn-gold">
            <i class="bi bi-plus-circle"></i>Add TODA
        </a>
    @endif
</div>

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="w-full max-w-md">
        <div class="tw-input-group">
            <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
            <input type="text" id="todaSearchInput" class="tw-input" placeholder="Search TODA by name or area..." value="{{ $search ?? '' }}" aria-label="Search TODA by name or area">
            <button type="button" class="tw-btn tw-btn-gold shrink-0 px-4" onclick="liveTodaSearch(document.getElementById('todaSearchInput').value)" aria-label="Search">
                <i class="bi bi-search"></i>
            </button>
            @if ($search ?? null)
                <a href="{{ route($routePrefix . '.todas') }}" class="inline-flex shrink-0 items-center bg-slate-100 px-3 text-slate-500 transition hover:text-slate-700" aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </a>
            @endif
        </div>
    </div>
    <p class="text-xs text-slate-500">
        <span id="todaCount" class="font-bold text-slate-700">{{ $todas->total() }}</span>
        <span id="todaCountLabel">TODA{{ $todas->total() === 1 ? '' : 's' }}</span>
        <span id="todaSearchLabel" class="text-slate-400">{{ ($search ?? null) ? 'matching “' . $search . '”' : '' }}</span>
    </p>
</div>

<div id="todasGrid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @include('partials.admin.toda-members-table', ['todas' => $todas, 'routePrefix' => $routePrefix, 'showManage' => $showManage])
</div>

<div id="paginationLinks">
    @if ($todas->hasPages())
        <div class="mt-5">
            {{ $todas->links('pagination::tailwind') }}
        </div>
    @endif
</div>

@include('partials.toda-members-modal', [
    'membersUrl' => url('/' . $routePrefix . '/toda'),
    'addMemberUrl' => route($routePrefix . '.operators.create'),
])

<script>
    function liveTodaSearch(val) {
        const url = new URL(window.location.href);
        if (val) { url.searchParams.set('search', val); } else { url.searchParams.delete('search'); }
        url.searchParams.delete('page');
        fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => {
                document.getElementById('todasGrid').innerHTML = d.html;
                document.getElementById('paginationLinks').innerHTML = d.pagination;

                if (d.total !== undefined) {
                    document.getElementById('todaCount').textContent = d.total;
                    document.getElementById('todaCountLabel').textContent = 'TODA' + (Number(d.total) === 1 ? '' : 's');
                    document.getElementById('todaSearchLabel').textContent = val ? 'matching “' + val + '”' : '';
                }
            });
    }
    // Search on Enter (or the Search button) only: typing must never fire a request on its own.
    document.getElementById('todaSearchInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            liveTodaSearch(e.target.value.trim());
        }
    });
</script>
