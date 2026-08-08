{{--
    Shared "recent activity" lists grid. Params:
    - $complaintsRoute  route name for the complaints view-all link
    - $ratingsRoute     route name for the ratings view-all link
    - $totalComplaints  used to conditionally show the complaints view-all link
--}}
<div class="mb-3 grid gap-3 lg:grid-cols-3">
    <div class="tw-card transition-shadow duration-200 hover:shadow-md">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="tw-card-title text-sm"><i class="bi bi-exclamation-triangle mr-1 text-gold"></i> Recent Complaints</h3>
            @if ($totalComplaints > 5)
                <a href="{{ route($complaintsRoute) }}" class="tw-btn tw-btn-sm tw-btn-ghost">View all <i class="bi bi-arrow-right"></i></a>
            @endif
        </div>
        <div class="max-h-[300px] divide-y divide-slate-100 overflow-y-auto" data-live-list="complaints">
            @include('partials.dashboard-list-complaints')
        </div>
    </div>

    <div class="tw-card transition-shadow duration-200 hover:shadow-md">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="tw-card-title text-sm"><i class="bi bi-trophy mr-1 text-gold"></i> Top Rated Operators</h3>
        </div>
        <div class="max-h-[300px] divide-y divide-slate-100 overflow-y-auto" data-live-list="top">
            @include('partials.dashboard-list-top')
        </div>
    </div>

    <div class="tw-card transition-shadow duration-200 hover:shadow-md">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="tw-card-title text-sm"><i class="bi bi-clock-history mr-1 text-navy-600"></i> Recent Ratings</h3>
            <a href="{{ route($ratingsRoute) }}" class="tw-btn tw-btn-sm tw-btn-ghost">View all <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="max-h-[300px] divide-y divide-slate-100 overflow-y-auto" data-live-list="ratings">
            @include('partials.dashboard-list-ratings')
        </div>
    </div>
</div>
