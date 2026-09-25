{{--
    Reusable complaints list. Requires:
    - $routePrefix     string             'superadmin' | 'tfrb-officer'
    - $complaints      LengthAwarePaginator of App\Models\Rating
    - $filter          string             'pending' | 'reviewed' | 'solved' | 'all'
    - $ratingFilter    int|null           star rating filter: 1 | 2 | null
    - $pendingCount, $reviewedCount, $solvedCount, $totalCount int
    - $star1Count, $star2Count           int  (badges on the star chips)
    - $activeOperators Collection          active operators for export filter
    - $proofsTotal    int|null         (optional) global proof count across all complaints
--}}

@php
    $search = trim((string) ($search ?? request('search', '')));
    $statusUrl = function ($f) use ($routePrefix, $ratingFilter, $search) {
        return route($routePrefix . '.complaints', array_filter(['filter' => $f, 'rating' => $ratingFilter, 'search' => $search], fn ($v) => $v !== null && $v !== ''));
    };
    $starUrl = function ($r) use ($routePrefix, $filter, $search) {
        return route($routePrefix . '.complaints', array_filter(['filter' => $filter, 'rating' => $r, 'search' => $search], fn ($v) => $v !== null && $v !== ''));
    };
@endphp

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-exclamation-triangle mr-2 text-amber-500"></i>Complaints</h1>
        <p class="tw-page-sub">Passenger complaints filed against operators</p>
    </div>
    @include('partials.admin.export-dropdown', [
        'exportRoute' => route($routePrefix . '.complaints.export'),
        'exportLabel' => 'Complaints',
        'exportIcon' => 'bi-exclamation-triangle',
        'activeOperators' => $activeOperators,
        'exportFilters' => [
            ['type' => 'select', 'name' => 'filter', 'label' => 'Status', 'options' => [
                '' => 'All', 'pending' => 'Pending', 'reviewed' => 'Reviewed', 'solved' => 'Solved',
            ], 'value' => $filter],
            ['type' => 'select', 'name' => 'rating', 'label' => 'Stars', 'options' => [
                '' => 'All stars', '1' => '1 Star', '2' => '2 Stars',
            ], 'value' => $ratingFilter ?? ''],
            ['type' => 'daterange', 'prefix' => 'date', 'from' => request('date_from'), 'to' => request('date_to')],
        ],
        'preservedParams' => array_filter(['search' => $search], fn ($v) => $v !== null && $v !== ''),
    ])
</div>

{{-- Sticky summary + filter bar: stays pinned below the topbar while the list scrolls. --}}
<div class="sticky top-[70px] z-20 -mx-4 mb-5 bg-slate-50/95 px-4 pb-4 pt-2 shadow-[0_4px_10px_-8px_rgba(15,23,42,0.25)] backdrop-blur-sm sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="tw-stat">
            <div class="tw-stat-icon tw-stat-icon-amber"><i class="bi bi-exclamation-circle"></i></div>
            <div class="tw-stat-num">{{ $totalCount }}</div>
            <div class="tw-stat-label">Total</div>
        </div>
        <div class="tw-stat">
            <div class="tw-stat-icon tw-stat-icon-red"><i class="bi bi-clock-history"></i></div>
            <div class="tw-stat-num">{{ $pendingCount }}</div>
            <div class="tw-stat-label">Pending</div>
        </div>
        <div class="tw-stat">
            <div class="tw-stat-icon tw-stat-icon-emerald"><i class="bi bi-check-circle"></i></div>
            <div class="tw-stat-num">{{ $reviewedCount }}</div>
            <div class="tw-stat-label">Reviewed</div>
        </div>
        <div class="tw-stat">
            <div class="tw-stat-icon tw-stat-icon-violet"><i class="bi bi-paperclip"></i></div>
            <div class="tw-stat-num">{{ $proofsTotal ?? $complaints->sum(fn($r) => $r->proofs->count()) }}</div>
            <div class="tw-stat-label">Proofs</div>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-3">
        <div class="flex flex-wrap gap-2">
            <a href="{{ $statusUrl('all') }}" class="{{ $filter === 'all' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
                <i class="bi bi-list-ul"></i> All <span class="tw-badge tw-badge-gray ml-1">{{ $totalCount }}</span>
            </a>
            <a href="{{ $statusUrl('pending') }}" class="{{ $filter === 'pending' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
                <i class="bi bi-clock-history"></i> Pending <span class="tw-badge tw-badge-amber ml-1">{{ $pendingCount }}</span>
            </a>
            <a href="{{ $statusUrl('reviewed') }}" class="{{ $filter === 'reviewed' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
                <i class="bi bi-check-circle"></i> Reviewed <span class="tw-badge tw-badge-green ml-1">{{ $reviewedCount }}</span>
            </a>
            <a href="{{ $statusUrl('solved') }}" class="{{ $filter === 'solved' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
                <i class="bi bi-patch-check-fill"></i> Solved <span class="tw-badge tw-badge-navy ml-1">{{ $solvedCount }}</span>
            </a>
        </div>

        <div class="ml-auto flex flex-wrap items-center gap-3">
            <form action="{{ route($routePrefix . '.complaints') }}" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="filter" value="{{ $filter }}">
                @if ($ratingFilter !== null)
                    <input type="hidden" name="rating" value="{{ $ratingFilter }}">
                @endif
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by reference number"
                       class="tw-input py-2" style="max-width: 16rem;" aria-label="Search complaint by reference number">
                <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline" title="Search">
                    <i class="bi bi-search"></i><span class="hidden sm:inline">Search</span>
                </button>
                @if ($search !== '')
                    <a href="{{ route($routePrefix . '.complaints', array_filter(['filter' => $filter, 'rating' => $ratingFilter], fn ($v) => $v !== null)) }}"
                       class="tw-btn tw-btn-sm tw-btn-ghost" title="Clear search">
                        <i class="bi bi-x-lg"></i><span class="hidden sm:inline">Clear</span>
                    </a>
                @endif
            </form>
            <span class="h-5 w-px bg-slate-200" aria-hidden="true"></span>
            <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-600">
                <input type="checkbox" class="tw-check" data-complaint-select-all>
                Select all
            </label>
            <form id="complaintBulkReviewForm" action="{{ route($routePrefix . '.complaints.bulkReview') }}" method="POST">
                @csrf
                <input type="hidden" name="ids" id="complaintBulkReviewIds">
                <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold" data-complaint-bulk-review disabled>
                    <i class="bi bi-check2-all"></i>Mark Reviewed <span data-complaint-bulk-count>0</span>
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Star rating filter: a compact segmented control in its own row (not part of
     the sticky bar) so the toolbar stays clean and the severity filter is easy
     to scan and toggle. --}}
<div class="mb-4 flex flex-wrap items-center gap-3">
    <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-slate-400">
        <i class="bi bi-stars text-gold"></i>Severity
    </span>
    <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <a href="{{ $starUrl(null) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold transition {{ $ratingFilter === null ? 'bg-navy-600 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
            All stars
        </a>
        <a href="{{ $starUrl(1) }}" class="inline-flex items-center gap-1.5 border-l border-slate-200 px-3.5 py-1.5 text-xs font-semibold transition {{ $ratingFilter === 1 ? 'bg-navy-600 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
            <i class="bi bi-star-fill {{ $ratingFilter === 1 ? '' : 'text-amber-400' }}"></i>1 Star
            <span class="{{ $ratingFilter === 1 ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }} rounded px-1 text-[0.65rem] font-bold">{{ $star1Count }}</span>
        </a>
        <a href="{{ $starUrl(2) }}" class="inline-flex items-center gap-1.5 border-l border-slate-200 px-3.5 py-1.5 text-xs font-semibold transition {{ $ratingFilter === 2 ? 'bg-navy-600 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
            <i class="bi bi-star-fill {{ $ratingFilter === 2 ? '' : 'text-amber-400' }}"></i>2 Stars
            <span class="{{ $ratingFilter === 2 ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }} rounded px-1 text-[0.65rem] font-bold">{{ $star2Count }}</span>
        </a>
    </div>
    @if ($ratingFilter !== null)
        <a href="{{ $starUrl(null) }}" class="tw-btn tw-btn-sm tw-btn-outline" title="Clear star filter">
            <i class="bi bi-x-lg"></i>Clear
        </a>
    @endif
</div>

@php
    if ($search !== '') {
        $emptyTitle = 'No complaints found';
        $emptyMsg = 'Nothing matches "' . $search . '". Try the full reference number, e.g. TFR-2026-0001.';
        if ($filter !== 'all') {
            $emptyMsg = 'No ' . $filter . ' complaint matches "' . $search . '". Try the full reference number, e.g. TFR-2026-0001.';
        }
    } else {
        $emptyTitle = 'No Complaints';
        $emptyMsg = 'All operators are doing great! No complaints filed.';
        if ($filter === 'pending') { $emptyTitle = 'No Pending Complaints'; $emptyMsg = 'Nothing waiting for review. Keep it up!'; }
        elseif ($filter === 'reviewed') { $emptyTitle = 'No Reviewed Complaints'; $emptyMsg = 'Complaints you mark as reviewed will appear here.'; }
        elseif ($filter === 'solved') { $emptyTitle = 'No Solved Complaints'; $emptyMsg = 'Complaints you mark as solved will appear here.'; }
    }
@endphp

@forelse ($complaints as $rating)
    @include('partials.admin.complaint-card', ['rating' => $rating, 'routePrefix' => $routePrefix])
@empty
    <div class="tw-empty py-16">
        <div class="tw-empty-icon"><i class="bi bi-check-circle text-emerald-500"></i></div>
        <h3 class="tw-card-title mb-1">{{ $emptyTitle }}</h3>
        <p class="text-sm text-slate-500">{{ $emptyMsg }}</p>
    </div>
@endforelse

@if ($complaints->hasPages())
    <div class="mt-4">
        {{ $complaints->links('pagination::tailwind') }}
    </div>
@endif
