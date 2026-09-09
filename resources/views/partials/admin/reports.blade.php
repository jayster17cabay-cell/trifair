{{-- Shared reports page body. Requires: $routePrefix, $operators --}}

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-bar-chart-line mr-2 text-violet-600"></i>Operator Performance</h1>
        <p class="tw-page-sub">Analytics and performance overview of all operators</p>
    </div>
    @include('partials.admin.export-dropdown', [
        'exportRoute' => route($routePrefix . '.reports.export'),
        'exportLabel' => 'Report',
        'exportIcon' => 'bi-bar-chart-line',
        'exportSub' => 'Operator performance analytics',
        'activeOperators' => $activeOperators,
        'preservedParams' => array_filter([
            'toda_id' => $todaId ?? null,
            'min_rating' => $minRating ?? null,
        ]),
    ])
</div>

{{-- Filter bar: TODA, period, and minimum average rating. --}}
<div class="mb-4 rounded-xl border border-slate-100 bg-white p-3 shadow-sm sm:p-4">
    <form method="GET" action="{{ route($routePrefix . '.reports') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label for="report_toda" class="mb-1 block text-xs font-semibold text-slate-600">TODA</label>
            <select name="toda_id" id="report_toda" class="tw-select py-2 text-xs">
                <option value="">All TODAs</option>
                @foreach ($todas as $toda)
                    <option value="{{ $toda->id }}" @selected((int) ($todaId ?? 0) === (int) $toda->id)>{{ $toda->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="report_from" class="mb-1 block text-xs font-semibold text-slate-600">From Date</label>
            <input type="date" name="date_from" id="report_from" value="{{ $dateFrom ?? '' }}" class="tw-input py-2 text-xs">
        </div>
        <div>
            <label for="report_to" class="mb-1 block text-xs font-semibold text-slate-600">To Date</label>
            <input type="date" name="date_to" id="report_to" value="{{ $dateTo ?? '' }}" class="tw-input py-2 text-xs">
        </div>
        <div>
            <label for="report_min" class="mb-1 block text-xs font-semibold text-slate-600">Min. Average Rating</label>
            <select name="min_rating" id="report_min" class="tw-select py-2 text-xs">
                <option value="">Any</option>
                @foreach ([1, 2, 3, 4, 5] as $v)
                    <option value="{{ $v }}" @selected((float) ($minRating ?? 0) === (float) $v)>&ge; {{ $v }}.0</option>
                @endforeach
            </select>
            <p class="mt-1 text-[0.7rem] text-slate-400">Rating average is computed within the selected period.</p>
        </div>
        <div class="flex items-end gap-2 lg:col-span-4">
            <button type="submit" class="tw-btn tw-btn-sm tw-btn-navy">
                <i class="bi bi-funnel"></i>Apply Filters
            </button>
            @if ($dateFrom || $dateTo || $todaId || $minRating)
                <a href="{{ route($routePrefix . '.reports') }}" class="tw-btn tw-btn-sm tw-btn-outline">
                    <i class="bi bi-x-lg"></i>Clear
                </a>
            @endif
            <p class="ml-auto hidden text-xs text-slate-400 lg:block">Tip: use the Download button to generate a printable PDF with the current filters.</p>
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
                @include('partials.admin.operator-performance-row', ['operator' => $operator, 'routePrefix' => $routePrefix, 'avg' => $avg])
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
