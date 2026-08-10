{{--
    RatingPageHeader — compact single-row header for public passenger pages.

    Combines the "Verified" badge, a dot separator, and a clock + combined
    time/date into one centered row on the navy header. Vertical padding is
    kept tight so the header only needs a single line of content.

    Styles: `.rate-header`, `.rate-header-row`, `.rate-verified-pill`,
    `.rate-header-dot`, `.rate-header-datetime` (in resources/css/tailwind.css).
--}}
<div class="rate-header">
    <div class="rate-container">
        <div class="rate-header-row">
            <span class="rate-verified-pill">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                <span>Verified</span>
            </span>
            <span class="rate-header-dot" aria-hidden="true"></span>
            <span class="rate-header-datetime">
                <i class="bi bi-clock" aria-hidden="true"></i>
                <time datetime="{{ now('Asia/Manila')->toIso8601String() }}">{{ now('Asia/Manila')->format('g:i A, D M j') }}</time>
            </span>
        </div>
    </div>
</div>
