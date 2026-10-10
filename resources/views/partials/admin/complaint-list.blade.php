{{--
    Reusable complaints list. Requires:
    - $routePrefix     string             'superadmin' | 'tfrb-officer'
    - $complaints      LengthAwarePaginator of App\Models\Rating
    - $filter          string             'pending' | 'accepted' | 'rejected' | 'solved' | 'all'
    - $ratingFilter    int|null           star rating filter: 1 | 2 | null
    - $pendingCount, $acceptedCount, $rejectedCount, $solvedCount, $totalCount int
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

    $statItems = [
        ['num' => $totalCount, 'label' => 'Total', 'icon' => 'bi-exclamation-circle', 'chip' => 'bg-blue-50 text-navy-600'],
        ['num' => $pendingCount, 'label' => 'Pending', 'icon' => 'bi-clock-history', 'chip' => 'bg-amber-50 text-amber-600'],
        ['num' => $acceptedCount, 'label' => 'Accepted', 'icon' => 'bi-check-circle', 'chip' => 'bg-emerald-50 text-emerald-600'],
        ['num' => $rejectedCount, 'label' => 'Rejected', 'icon' => 'bi-x-circle', 'chip' => 'bg-red-50 text-red-600'],
        ['num' => $solvedCount, 'label' => 'Solved', 'icon' => 'bi-patch-check-fill', 'chip' => 'bg-violet-50 text-violet-600'],
        ['num' => $proofsTotal ?? $complaints->sum(fn($r) => $r->proofs->count()), 'label' => 'Proofs', 'icon' => 'bi-paperclip', 'chip' => 'bg-slate-100 text-slate-500'],
    ];
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
                '' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted', 'rejected' => 'Rejected', 'solved' => 'Solved',
            ], 'value' => $filter],
            ['type' => 'select', 'name' => 'rating', 'label' => 'Stars', 'options' => [
                '' => 'All stars', '1' => '1 Star', '2' => '2 Stars',
            ], 'value' => $ratingFilter ?? ''],
            ['type' => 'daterange', 'prefix' => 'date', 'from' => request('date_from'), 'to' => request('date_to')],
        ],
        'preservedParams' => array_filter(['search' => $search], fn ($v) => $v !== null && $v !== ''),
    ])
</div>

{{-- Sticky summary + filter panel: stays pinned below the topbar while the list scrolls. --}}
<div class="sticky top-[70px] z-20 -mx-4 mb-5 border-b border-slate-100 bg-white/95 px-4 pb-4 pt-3 shadow-[0_10px_30px_-20px_rgba(15,23,42,0.25)] backdrop-blur-sm sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    {{-- Summary stats --}}
    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ($statItems as $stat)
            <div class="flex items-center gap-2.5 rounded-lg bg-slate-50 px-3 py-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $stat['chip'] }}"><i class="bi {{ $stat['icon'] }}"></i></span>
                <div class="min-w-0">
                    <div class="text-base font-extrabold leading-none text-slate-800">{{ $stat['num'] }}</div>
                    <div class="mt-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $stat['label'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Status filters + search/bulk actions --}}
    <div class="mt-3.5 flex flex-wrap items-center gap-3">
        <div class="inline-flex flex-wrap items-center gap-1 rounded-xl bg-slate-100 p-1">
            <a href="{{ $statusUrl('all') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $filter === 'all' ? 'bg-white text-navy-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                <i class="bi bi-list-ul"></i> All <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $filter === 'all' ? 'bg-navy-600 text-white' : 'bg-white text-slate-400' }}">{{ $totalCount }}</span>
            </a>
            <a href="{{ $statusUrl('pending') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $filter === 'pending' ? 'bg-white text-navy-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                <i class="bi bi-clock-history"></i> Pending <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $filter === 'pending' ? 'bg-navy-600 text-white' : 'bg-white text-slate-400' }}">{{ $pendingCount }}</span>
            </a>
            <a href="{{ $statusUrl('accepted') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $filter === 'accepted' ? 'bg-white text-navy-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                <i class="bi bi-check-circle"></i> Accepted <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $filter === 'accepted' ? 'bg-navy-600 text-white' : 'bg-white text-slate-400' }}">{{ $acceptedCount }}</span>
            </a>
            <a href="{{ $statusUrl('rejected') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $filter === 'rejected' ? 'bg-white text-navy-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                <i class="bi bi-x-circle"></i> Rejected <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $filter === 'rejected' ? 'bg-navy-600 text-white' : 'bg-white text-slate-400' }}">{{ $rejectedCount }}</span>
            </a>
            <a href="{{ $statusUrl('solved') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $filter === 'solved' ? 'bg-white text-navy-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                <i class="bi bi-patch-check-fill"></i> Solved <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $filter === 'solved' ? 'bg-navy-600 text-white' : 'bg-white text-slate-400' }}">{{ $solvedCount }}</span>
            </a>
        </div>

        <div class="ml-auto flex flex-wrap items-center gap-3">
            <form action="{{ route($routePrefix . '.complaints') }}" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="filter" value="{{ $filter }}">
                @if ($ratingFilter !== null)
                    <input type="hidden" name="rating" value="{{ $ratingFilter }}">
                @endif
                <div class="tw-input-group">
                    <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by reference #"
                           class="tw-input py-2" style="width: min(14rem, 100%);" aria-label="Search complaint by reference number"
                           data-complaint-live-search>
                </div>
                @if ($search !== '')
                    <a href="{{ route($routePrefix . '.complaints', array_filter(['filter' => $filter, 'rating' => $ratingFilter], fn ($v) => $v !== null)) }}"
                       class="tw-btn tw-btn-sm tw-btn-ghost" title="Clear search">
                        <i class="bi bi-x-lg"></i><span class="hidden sm:inline">Clear</span>
                    </a>
                @endif
            </form>
            <span class="h-5 w-px bg-slate-100" aria-hidden="true"></span>
            <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-600">
                <input type="checkbox" class="tw-check" data-complaint-select-all>
                Select all
            </label>
            <form id="complaintBulkReviewForm" action="{{ route($routePrefix . '.complaints.bulkReview') }}" method="POST">
                @csrf
                <input type="hidden" name="ids" id="complaintBulkReviewIds">
                <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold" data-complaint-bulk-review disabled title="Accept all selected pending complaints">
                    <i class="bi bi-check2-all"></i>Accept <span data-complaint-bulk-count>0</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Star rating (severity) filter: compact segmented control in its own row. --}}
    <div class="mt-3 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-3">
        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-widest text-slate-400">
            <i class="bi bi-stars text-gold"></i>Severity
        </span>
        <div class="inline-flex items-center gap-0.5 rounded-lg border border-slate-200 bg-white p-0.5 shadow-sm">
            <a href="{{ $starUrl(null) }}" class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $ratingFilter === null ? 'bg-navy-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50' }}">
                All stars
            </a>
            <a href="{{ $starUrl(1) }}" class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $ratingFilter === 1 ? 'bg-navy-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50' }}">
                <i class="bi bi-star-fill {{ $ratingFilter === 1 ? '' : 'text-amber-400' }}"></i>1 Star
                <span class="{{ $ratingFilter === 1 ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }} rounded px-1 text-[10px] font-bold">{{ $star1Count }}</span>
            </a>
            <a href="{{ $starUrl(2) }}" class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $ratingFilter === 2 ? 'bg-navy-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50' }}">
                <i class="bi bi-star-fill {{ $ratingFilter === 2 ? '' : 'text-amber-400' }}"></i>2 Stars
                <span class="{{ $ratingFilter === 2 ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }} rounded px-1 text-[10px] font-bold">{{ $star2Count }}</span>
            </a>
        </div>
        @if ($ratingFilter !== null)
            <a href="{{ $starUrl(null) }}" class="tw-btn tw-btn-sm tw-btn-outline" title="Clear star filter">
                <i class="bi bi-x-lg"></i>Clear
            </a>
        @endif
    </div>
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
        elseif ($filter === 'accepted') { $emptyTitle = 'No Accepted Complaints'; $emptyMsg = 'Complaints accepted by an officer will appear here.'; }
        elseif ($filter === 'rejected') { $emptyTitle = 'No Rejected Complaints'; $emptyMsg = 'Complaints rejected after review will appear here.'; }
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

<script>
    (function () {
        var input = document.querySelector('input[data-complaint-live-search]');
        if (!input || !input.form) return;
        var timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { input.form.submit(); }, 400);
        });
        input.addEventListener('keydown', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                clearTimeout(timer);
                input.form.submit();
            }
        });
    })();
</script>