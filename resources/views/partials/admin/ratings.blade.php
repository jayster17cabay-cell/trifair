{{-- Shared ratings page body. Requires: $routePrefix, $reviewRouteName, $showDelete, $ratings, $activeOperators --}}

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-star-half mr-2 text-gold"></i>Ratings & Feedback</h1>
        <p class="tw-page-sub">Review passenger ratings (1-5 stars)</p>
    </div>
        @include('partials.admin.export-dropdown', [
            'exportRoute' => route($routePrefix . '.ratings.export'),
            'exportLabel' => 'Ratings',
            'exportIcon' => 'bi-star',
            'activeOperators' => $activeOperators,
            'preservedParams' => array_filter([
                'date_from' => $dateFrom ?? null,
                'date_to' => $dateTo ?? null,
                'operator_id' => $operatorId ?? null,
                'rating' => $ratingFilter ?? null,
            ]),
        ])
</div>

{{-- Filter bar: date range, operator and star rating (1-5). --}}
<div class="mb-4 rounded-xl border border-slate-100 bg-white p-3 shadow-sm sm:p-4">
    <form method="GET" action="{{ route($routePrefix . '.ratings') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" id="ratingsFilterForm">
        <div>
            <label for="date_from" class="mb-1 block text-xs font-semibold text-slate-600">From Date</label>
            <input type="date" name="date_from" id="date_from" value="{{ $dateFrom ?? '' }}" class="tw-input py-2 text-xs" onchange="this.form.submit()">
        </div>
        <div>
            <label for="date_to" class="mb-1 block text-xs font-semibold text-slate-600">To Date</label>
            <input type="date" name="date_to" id="date_to" value="{{ $dateTo ?? '' }}" class="tw-input py-2 text-xs" onchange="this.form.submit()">
        </div>
        <div>
            <label for="rating_operator" class="mb-1 block text-xs font-semibold text-slate-600">Operator</label>
            <select name="operator_id" id="rating_operator" class="tw-select py-2 text-xs" onchange="this.form.submit()">
                <option value="">All Operators</option>
                @foreach ($activeOperators as $op)
                    <option value="{{ $op->id }}" {{ (int) ($operatorId ?? 0) === (int) $op->id ? 'selected' : '' }}>{{ $op->user->name ?? 'Unknown' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="rating_stars" class="mb-1 block text-xs font-semibold text-slate-600">Stars</label>
            <select name="rating" id="rating_stars" class="tw-select py-2 text-xs" onchange="this.form.submit()">
                <option value="" {{ $ratingFilter === null ? 'selected' : '' }}>All stars ({{ $totalCount }})</option>
                @foreach ([5, 4, 3, 2, 1] as $star)
                    <option value="{{ $star }}" {{ $ratingFilter === $star ? 'selected' : '' }}>{{ $star }} Star{{ $star > 1 ? 's' : '' }} ({{ $starCounts[$star] ?? 0 }})</option>
                @endforeach
            </select>
        </div>
        @if ($dateFrom || $dateTo || $operatorId || $ratingFilter)
            <div class="flex items-center sm:col-span-2 lg:col-span-4">
                <a href="{{ route($routePrefix . '.ratings') }}" class="tw-btn tw-btn-sm tw-btn-outline" title="Clear filters">
                    <i class="bi bi-x-lg"></i>Clear filters
                </a>
            </div>
        @endif
    </form>
</div>

{{-- Sticky summary + bulk bar: stays pinned below the topbar while the list scrolls. --}}
<div class="sticky top-[70px] z-20 -mx-4 mb-5 bg-slate-50/95 px-4 pb-4 pt-2 shadow-[0_4px_10px_-8px_rgba(15,23,42,0.25)] backdrop-blur-sm sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
        <div class="tw-stat">
            <div class="tw-stat-icon tw-stat-icon-navy"><i class="bi bi-star"></i></div>
            <div class="tw-stat-num">{{ $totalCount }}</div>
            <div class="tw-stat-label">Total</div>
        </div>
        <div class="tw-stat">
            <div class="tw-stat-icon tw-stat-icon-emerald"><i class="bi bi-hand-thumbs-up"></i></div>
            <div class="tw-stat-num">{{ $goodCount }}</div>
            <div class="tw-stat-label">Good (4-5)</div>
        </div>
        <div class="tw-stat">
            <div class="tw-stat-icon tw-stat-icon-violet"><i class="bi bi-paperclip"></i></div>
            <div class="tw-stat-num">{{ $proofsCount }}</div>
            <div class="tw-stat-label">Proofs</div>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center justify-end gap-3">
        <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-600">
            <input type="checkbox" class="tw-check" data-rating-select-all>
            Select all
        </label>
        <form id="ratingBulkReviewForm" action="{{ route($routePrefix . '.ratings.bulkReview') }}" method="POST">
            @csrf
            <input type="hidden" name="ids" id="ratingBulkReviewIds">
            <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold" data-rating-bulk-review disabled>
                <i class="bi bi-check2-all"></i>Mark Reviewed <span data-rating-bulk-count>0</span>
            </button>
        </form>
    </div>
</div>

@forelse ($ratings as $rating)
    @include('partials.admin.rating-card', ['rating' => $rating, 'routePrefix' => $routePrefix, 'reviewRouteName' => $reviewRouteName, 'showDelete' => $showDelete])
@empty
    <div class="tw-empty py-16">
        <div class="tw-empty-icon"><i class="bi bi-star"></i></div>
        <h3 class="tw-card-title mb-1">No Ratings Yet</h3>
        <p class="text-sm text-slate-500">No passenger feedback has been submitted yet.</p>
    </div>
@endforelse

@if ($ratings->hasPages())
    <div class="mt-4">
        {{ $ratings->links('pagination::tailwind') }}
    </div>
@endif
