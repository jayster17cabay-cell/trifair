{{--
    Reusable complaints list. Requires:
    - $routePrefix     string             'superadmin' | 'tfrb-officer'
    - $complaints      LengthAwarePaginator of App\Models\Rating
    - $filter          string             'pending' | 'reviewed' | 'all'
    - $pendingCount, $reviewedCount, $totalCount int
--}}

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-exclamation-triangle mr-2 text-amber-500"></i>Complaints</h1>
        <p class="tw-page-sub">Low ratings (1-2 stars) reported by passengers</p>
    </div>
    <a href="{{ route($routePrefix . '.complaints.export', ['filter' => $filter]) }}" class="tw-btn tw-btn-sm tw-btn-outline">
        <i class="bi bi-download"></i>Export CSV
    </a>
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
            <div class="tw-stat-num">{{ $complaints->sum(fn($r) => $r->proofs->count()) }}</div>
            <div class="tw-stat-label">Proofs</div>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-3">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route($routePrefix . '.complaints', ['filter' => 'all']) }}" class="{{ $filter === 'all' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
                <i class="bi bi-list-ul"></i> All <span class="tw-badge tw-badge-gray ml-1">{{ $totalCount }}</span>
            </a>
            <a href="{{ route($routePrefix . '.complaints', ['filter' => 'pending']) }}" class="{{ $filter === 'pending' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
                <i class="bi bi-clock-history"></i> Pending <span class="tw-badge tw-badge-amber ml-1">{{ $pendingCount }}</span>
            </a>
            <a href="{{ route($routePrefix . '.complaints', ['filter' => 'reviewed']) }}" class="{{ $filter === 'reviewed' ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
                <i class="bi bi-check-circle"></i> Reviewed <span class="tw-badge tw-badge-green ml-1">{{ $reviewedCount }}</span>
            </a>
        </div>

        <div class="ml-auto flex flex-wrap items-center gap-3">
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

@php
    $emptyTitle = 'No Complaints';
    $emptyMsg = 'All operators are doing great! No low ratings reported.';
    if ($filter === 'pending') { $emptyTitle = 'No Pending Complaints'; $emptyMsg = 'Nothing waiting for review. Keep it up!'; }
    elseif ($filter === 'reviewed') { $emptyTitle = 'No Reviewed Complaints'; $emptyMsg = 'Complaints you mark as reviewed will appear here.'; }
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
