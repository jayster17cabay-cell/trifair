{{--
    RatingBreakdownCard component — white card wrapping the rating bar chart.
    Requires: $ratingCounts, $totalRatings.
--}}
<div class="op-card">
    <div class="op-card-head">
        <h3 class="op-card-title"><i class="bi bi-bar-chart mr-1.5 text-navy-600"></i> Rating breakdown</h3>
    </div>

    <div data-live-list="breakdown">
        @include('partials.dashboard-rating-breakdown')
    </div>
</div>
