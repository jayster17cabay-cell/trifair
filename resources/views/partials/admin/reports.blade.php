{{-- Shared reports page body. Requires: $routePrefix, $operators --}}

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-bar-chart-line mr-2 text-violet-600"></i>Operator Performance</h1>
        <p class="tw-page-sub">Analytics and performance overview of all operators</p>
    </div>
    @include('partials.admin.export-dropdown', [
        'exportRoute' => route($routePrefix . '.reports.export'),
        'activeOperators' => $activeOperators,
    ])
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
