{{-- Shared reports page body. Requires: $routePrefix, $operators --}}

<div class="tw-page-head">
    <div class="flex items-start gap-3">
        <div class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 to-navy-700 text-white shadow-soft sm:flex">
            <i class="bi bi-bar-chart-line text-lg"></i>
        </div>
        <div>
            <p class="tw-eyebrow">Analytics</p>
            <h1 class="tw-page-title">Operator Performance</h1>
            <p class="tw-page-sub">Compare ratings, trips and status across every TODA.</p>
        </div>
    </div>
    @php
        $exportTodaOptions = ['' => 'All TODAs'];
        foreach ($todas as $toda) {
            $exportTodaOptions[$toda->id] = $toda->name;
        }
        // Every operator carries its TODA so the Operator list can be narrowed
        // to the selected TODA on the fly (see initExportScopeFilters()).
        $exportOperatorOptions = ['' => 'All operators'];
        $exportOperatorData = [];
        foreach ($activeOperators as $op) {
            if (($op->user->role ?? null) !== 'operator') {
                continue;
            }
            $exportOperatorOptions[$op->id] = $op->user->name ?? 'Unknown';
            $exportOperatorData[$op->id] = (int) $op->toda_id;
        }
    @endphp
    @include('partials.admin.export-dropdown', [
        'exportRoute' => route($routePrefix . '.reports.export'),
        'exportLabel' => 'Report',
        'exportIcon' => 'bi-bar-chart-line',
        'exportSub' => 'Export every operator, one TODA, or a single operator',
        // The page's $activeOperators is inherited by @include; null it out so the
        // panel does not render a second (duplicate) operator selector below.
        'activeOperators' => null,
        'exportFilters' => [
            ['type' => 'select', 'name' => 'toda_id', 'label' => 'TODA', 'options' => $exportTodaOptions, 'value' => $todaId ?? '', 'help' => 'Leave as "All TODAs" to include every TODA.'],
            ['type' => 'select', 'name' => 'operator_id', 'label' => 'Operator', 'options' => $exportOperatorOptions, 'value' => '', 'option_data' => $exportOperatorData, 'help' => 'Pick one operator to export only their report. Members of the selected TODA appear when a TODA filter is active.'],
        ],
        'preservedParams' => array_filter([
            'min_rating' => $minRating ?? null,
            'period' => ($period ?? 'all') !== 'all' ? $period : null,
        ]),
    ])
</div>

{{-- Filter bar: TODA, reporting period, and minimum average rating. --}}
<div class="mb-5 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
    <form method="GET" action="{{ route($routePrefix . '.reports') }}">
        <div class="flex flex-col gap-2 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-navy-50 text-navy-600">
                    <i class="bi bi-funnel-fill text-xs"></i>
                </span>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Filters</p>
                    <p class="text-[11px] text-slate-400">Narrow results by TODA, period and rating.</p>
                </div>
            </div>
            @if (($period ?? 'all') !== 'all' || $todaId || $minRating)
                <a href="{{ route($routePrefix . '.reports') }}" class="tw-btn tw-btn-sm tw-btn-ghost self-start sm:self-auto">
                    <i class="bi bi-x-lg"></i>Clear filters
                </a>
            @endif
        </div>

        <div class="grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="report_toda" class="mb-1.5 block text-xs font-semibold text-slate-600">TODA</label>
                <div class="relative">
                    <i class="bi bi-diagram-3 pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                    <select name="toda_id" id="report_toda" class="tw-select py-2.5 pl-9 text-sm" onchange="this.form.submit()">
                        <option value="">All TODAs</option>
                        @foreach ($todas as $toda)
                            <option value="{{ $toda->id }}" {{ (int) ($todaId ?? 0) === (int) $toda->id ? 'selected' : '' }}>{{ $toda->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label for="report_period" class="mb-1.5 block text-xs font-semibold text-slate-600">Period</label>
                <div class="relative">
                    <i class="bi bi-calendar3 pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                    <select name="period" id="report_period" class="tw-select py-2.5 pl-9 text-sm" onchange="this.form.submit()">
                        <option value="all" {{ ($period ?? 'all') === 'all' ? 'selected' : '' }}>All Time</option>
                        <option value="year" {{ ($period ?? 'all') === 'year' ? 'selected' : '' }}>This Year</option>
                        <option value="month" {{ ($period ?? 'all') === 'month' ? 'selected' : '' }}>This Month</option>
                        <option value="week" {{ ($period ?? 'all') === 'week' ? 'selected' : '' }}>This Week</option>
                    </select>
                </div>
            </div>
            <div>
                <label for="report_min" class="mb-1.5 block text-xs font-semibold text-slate-600">Minimum Average Rating</label>
                <div class="relative">
                    <i class="bi bi-star-half pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                    <select name="min_rating" id="report_min" class="tw-select py-2.5 pl-9 text-sm" onchange="this.form.submit()">
                        <option value="">Any rating</option>
                        @foreach ([1, 2, 3, 4, 5] as $v)
                            <option value="{{ $v }}" {{ (float) ($minRating ?? 0) === (float) $v ? 'selected' : '' }}>&ge; {{ $v }}.0</option>
                        @endforeach
                    </select>
                </div>
                <p class="mt-1.5 text-[0.7rem] leading-relaxed text-slate-400">Average is computed within the selected period.</p>
            </div>
        </div>

        <div class="flex items-start gap-2 rounded-none border-t border-slate-100 bg-slate-50/60 px-4 py-2.5 text-[11px] leading-relaxed text-slate-500 sm:items-center">
            <i class="bi bi-lightning-charge-fill mt-px text-gold-600 sm:mt-0"></i>
            <span>Results update automatically. Use <strong class="font-semibold text-slate-600">Export Report</strong> to download a printable copy with the current filters.</span>
        </div>
    </form>
</div>

<div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-xs text-slate-500">
        Showing
        <span class="font-bold text-slate-700">{{ $operators->firstItem() ?? 0 }}–{{ $operators->lastItem() ?? 0 }}</span>
        of <span class="font-bold text-slate-700">{{ $operators->total() }}</span>
        operator{{ $operators->total() === 1 ? '' : 's' }}
    </p>
    <span class="inline-flex items-center gap-1.5 text-[11px] text-slate-400">
        <i class="bi bi-sort-down"></i>Sorted by most trips
    </span>
</div>

<div class="tw-table-scroll-wrap max-h-none">
    <table class="tw-table min-w-[44rem]">
        <thead class="tw-thead-sticky">
            <tr>
                <th scope="col" class="tw-th w-[38%]">Operator</th>
                <th scope="col" class="tw-th">Plate #</th>
                <th scope="col" class="tw-th">Status</th>
                <th scope="col" class="tw-th">Average Rating</th>
                <th scope="col" class="tw-th text-right">Trips</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($operators as $operator)
                @php $avg = (float) ($operator->valid_ratings_avg_rating ?? 0); @endphp
                @include('partials.admin.operator-performance-row', ['operator' => $operator, 'routePrefix' => $routePrefix, 'avg' => $avg, 'dateFrom' => $dateFrom ?? null, 'dateTo' => $dateTo ?? null, 'todaBreakdown' => ($todaBreakdown ?? [])[$operator->id] ?? collect()])
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center">
                        <div class="tw-empty border-0 shadow-none">
                            <div class="tw-empty-icon"><i class="bi bi-inbox"></i></div>
                            <h3 class="tw-empty-title">No Operators Found</h3>
                            <p class="text-sm text-slate-500">No operators match the selected TODA, period or rating.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 flex justify-center">
    {{ $operators->links('pagination::tailwind') }}
</div>

@include('partials.admin.trip-history-drawer')
