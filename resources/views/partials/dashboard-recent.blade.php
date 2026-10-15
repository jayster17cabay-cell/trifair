{{--
    Shared "recent activity" lists grid. Params:
    - $complaintsRoute  route name for the complaints view-all link
    - $ratingsRoute     route name for the ratings view-all link
    - $totalComplaints  used to conditionally show the complaints view-all link
--}}
<h2 class="tw-section-title">Recent Activity</h2>
<div class="grid gap-3 lg:grid-cols-3">
    <div class="tw-card transition-shadow duration-200 hover:shadow-md">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="tw-card-title flex items-center gap-2 text-sm">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-gold"><i class="bi bi-exclamation-triangle"></i></span>
                Recent Complaints
            </h3>
            @if ($totalComplaints > 5)
                <a href="{{ route($complaintsRoute) }}" class="tw-btn tw-btn-sm tw-btn-ghost">View all <i class="bi bi-arrow-right"></i></a>
            @endif
        </div>
        <div class="max-h-[180px] divide-y divide-slate-100 overflow-y-auto" data-live-list="complaints">
            @include('partials.dashboard-list-complaints')
        </div>
    </div>

    <div class="tw-card transition-shadow duration-200 hover:shadow-md">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="tw-card-title flex items-center gap-2 text-sm">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"><i class="bi bi-trophy"></i></span>
                Top Rated Operators
            </h3>
        </div>
        <div class="max-h-[180px] divide-y divide-slate-100 overflow-y-auto" data-live-list="top">
            @include('partials.dashboard-list-top')
        </div>
    </div>

    <div class="tw-card transition-shadow duration-200 hover:shadow-md">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="tw-card-title flex items-center gap-2 text-sm">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-navy-50 text-navy-600"><i class="bi bi-clock-history"></i></span>
                Recent Ratings
            </h3>
            <a href="{{ route($ratingsRoute) }}" class="tw-btn tw-btn-sm tw-btn-ghost">View all <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="max-h-[180px] divide-y divide-slate-100 overflow-y-auto" data-live-list="ratings">
            @include('partials.dashboard-list-ratings')
        </div>
    </div>
</div>
