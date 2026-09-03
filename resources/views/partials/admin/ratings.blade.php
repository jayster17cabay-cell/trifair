{{-- Shared ratings page body. Requires: $routePrefix, $reviewRouteName, $showDelete, $ratings, $activeOperators --}}

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-star-half mr-2 text-gold"></i>Ratings & Feedback</h1>
        <p class="tw-page-sub">Review passenger ratings (1-5 stars)</p>
    </div>
        @include('partials.admin.export-dropdown', [
            'exportRoute' => route($routePrefix . '.ratings.export'),
            'activeOperators' => $activeOperators,
        ])
</div>

@php
    $totalR = $ratings->total();
    $goodR = $goodCount;
    $proofsR = $proofsCount;
@endphp

{{-- Sticky summary + bulk bar: stays pinned below the topbar while the list scrolls. --}}
<div class="sticky top-[70px] z-20 -mx-4 mb-5 bg-slate-50/95 px-4 pb-4 pt-2 shadow-[0_4px_10px_-8px_rgba(15,23,42,0.25)] backdrop-blur-sm sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
        <div class="tw-stat">
            <div class="tw-stat-icon tw-stat-icon-navy"><i class="bi bi-star"></i></div>
            <div class="tw-stat-num">{{ $totalR }}</div>
            <div class="tw-stat-label">Total</div>
        </div>
        <div class="tw-stat">
            <div class="tw-stat-icon tw-stat-icon-emerald"><i class="bi bi-hand-thumbs-up"></i></div>
            <div class="tw-stat-num">{{ $goodR }}</div>
            <div class="tw-stat-label">Good (4-5)</div>
        </div>
        <div class="tw-stat">
            <div class="tw-stat-icon tw-stat-icon-violet"><i class="bi bi-paperclip"></i></div>
            <div class="tw-stat-num">{{ $proofsR }}</div>
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
