{{-- Shared reports page body. Requires: $routePrefix, $operators --}}

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-bar-chart-line mr-2 text-violet-600"></i>Operator Performance</h1>
        <p class="tw-page-sub">Analytics and performance overview of all operators</p>
    </div>
    <form method="GET" action="{{ route($routePrefix . '.reports.export') }}" class="flex items-center gap-1.5">
        <select name="format" class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs font-medium text-slate-700 focus:border-navy focus:ring-1 focus:ring-navy">
            <option value="csv">CSV</option>
            <option value="word">Word</option>
            <option value="pdf">PDF</option>
        </select>
        <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline"><i class="bi bi-download"></i>Export</button>
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
