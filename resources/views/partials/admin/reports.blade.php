{{-- Shared reports page body. Requires: $routePrefix, $operators --}}

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-bar-chart-line mr-2 text-violet-600"></i>Operator Performance</h1>
        <p class="tw-page-sub">Analytics and performance overview of all operators</p>
    </div>
    @php
        $exportTodaOptions = ['' => 'All TODAs'];
        foreach ($todas as $toda) {
            $exportTodaOptions[$toda->id] = $toda->name;
        }
        $exportOperatorOptions = ['' => 'All operators'];
        foreach ($activeOperators as $op) {
            if (($op->user->role ?? null) === 'operator') {
                $exportOperatorOptions[$op->id] = $op->user->name ?? 'Unknown';
            }
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
            ['type' => 'select', 'name' => 'operator_id', 'label' => 'Operator', 'options' => $exportOperatorOptions, 'value' => '', 'help' => 'Pick one operator to export only their report.'],
        ],
        'preservedParams' => array_filter([
            'min_rating' => $minRating ?? null,
            'period' => ($period ?? 'all') !== 'all' ? $period : null,
        ]),
    ])
</div>

{{-- Filter bar: TODA, reporting period, and minimum average rating. --}}
<div class="mb-4 rounded-xl border border-slate-100 bg-white p-3 shadow-sm sm:p-4">
    <form method="GET" action="{{ route($routePrefix . '.reports') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label for="report_toda" class="mb-1 block text-xs font-semibold text-slate-600">TODA</label>
            <select name="toda_id" id="report_toda" class="tw-select py-2 text-xs" onchange="this.form.submit()">
                <option value="">All TODAs</option>
                @foreach ($todas as $toda)
                    <option value="{{ $toda->id }}" {{ (int) ($todaId ?? 0) === (int) $toda->id ? 'selected' : '' }}>{{ $toda->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="report_period" class="mb-1 block text-xs font-semibold text-slate-600">Period</label>
            <select name="period" id="report_period" class="tw-select py-2 text-xs" onchange="this.form.submit()">
                <option value="all" {{ ($period ?? 'all') === 'all' ? 'selected' : '' }}>All Time</option>
                <option value="year" {{ ($period ?? 'all') === 'year' ? 'selected' : '' }}>This Year</option>
                <option value="month" {{ ($period ?? 'all') === 'month' ? 'selected' : '' }}>This Month</option>
                <option value="week" {{ ($period ?? 'all') === 'week' ? 'selected' : '' }}>This Week</option>
            </select>
        </div>
        <div>
            <label for="report_min" class="mb-1 block text-xs font-semibold text-slate-600">Min. Average Rating</label>
            <select name="min_rating" id="report_min" class="tw-select py-2 text-xs" onchange="this.form.submit()">
                <option value="">Any</option>
                @foreach ([1, 2, 3, 4, 5] as $v)
                    <option value="{{ $v }}" {{ (float) ($minRating ?? 0) === (float) $v ? 'selected' : '' }}>&ge; {{ $v }}.0</option>
                @endforeach
            </select>
            <p class="mt-1 text-[0.7rem] text-slate-400">Rating average is computed within the selected period.</p>
        </div>
        <div class="flex items-end gap-2 lg:col-span-3">
            @if (($period ?? 'all') !== 'all' || $todaId || $minRating)
                <a href="{{ route($routePrefix . '.reports') }}" class="tw-btn tw-btn-sm tw-btn-outline">
                    <i class="bi bi-x-lg"></i>Clear
                </a>
            @endif
            <p class="ml-auto hidden text-xs text-slate-400 lg:block">Results update automatically. Tip: use the Download button to generate a printable PDF with the current filters.</p>
        </div>
    </form>
</div>

<div class="tw-table-scroll-wrap">
    <table class="tw-table min-w-[40rem]">
        <thead class="tw-thead-sticky">
            <tr>
                <th class="tw-th">Operator</th>
                <th class="tw-th">Plate #</th>
                <th class="tw-th">Status</th>
                <th class="tw-th">Average Rating</th>
                <th class="tw-th text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($operators as $operator)
                @php $avg = (float) ($operator->valid_ratings_avg_rating ?? 0); @endphp
                @include('partials.admin.operator-performance-row', ['operator' => $operator, 'routePrefix' => $routePrefix, 'avg' => $avg, 'dateFrom' => $dateFrom ?? null, 'dateTo' => $dateTo ?? null])
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center">
                        <div class="tw-empty">
                            <div class="tw-empty-icon"><i class="bi bi-inbox"></i></div>
                            <h3 class="tw-empty-title">No Operators Found</h3>
                            <p class="text-sm text-slate-500">No operators match the selected route and period.</p>
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
