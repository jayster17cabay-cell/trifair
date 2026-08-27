{{--
    Reusable Operator Performance table row. Clicking anywhere on the row
    opens the trip-history drawer (partials/admin/trip-history-drawer).
    Requires:
    - $operator    App\Models\Operator  (with user eager-loaded; expects the
                   valid_ratings_avg_rating / valid_ratings_count columns from
                   the reportsData join, or pass $avg explicitly)
    - $routePrefix string               'superadmin' | 'tfrb-officer'
    - $avg         float|null           optional override for the average rating
    The row exposes data-* attributes consumed by initTripHistoryDrawer().
--}}
@php
    $avg = isset($avg) ? (float) $avg : (float) ($operator->valid_ratings_avg_rating ?? 0);
    $count = (int) ($operator->valid_ratings_count ?? 0);
    $name = $operator->user->name ?? 'Unknown';
    $totalBadge = $count === 0 ? 'tw-badge-gray' : 'tw-badge-navy';
@endphp
<tr class="tw-tr-hover cursor-pointer" data-open-trips
    data-trips-url="{{ route($routePrefix . '.reports.trips', $operator->id) }}"
    data-name="{{ $name }}"
    data-plate="{{ $operator->plate_number ?? '' }}"
    data-count="{{ $count }}"
    data-avg="{{ number_format($avg, 1) }}">
    <td class="tw-td">
        <div class="flex items-center gap-3">
            <div class="min-w-0">
                <div class="truncate text-sm font-bold text-slate-800">{{ $name }}</div>
            </div>
        </div>
    </td>
    <td class="tw-td text-sm font-semibold text-slate-700">{{ $operator->plate_number ?: '—' }}</td>
    <td class="tw-td">
        @if ($operator->status === 'active')
            <span class="tw-badge tw-badge-green"><i class="bi bi-check-circle-fill"></i>Active</span>
        @else
            <span class="tw-badge tw-badge-amber"><i class="bi bi-pause-circle-fill"></i>Inactive</span>
        @endif
    </td>
    <td class="tw-td">
        <div class="flex items-center gap-2">
            <span class="flex gap-0.5">
                @for ($i = 1; $i <= 5; $i++)
                    <i class="bi text-sm {{ $i <= round($avg) ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-200' }}"></i>
                @endfor
            </span>
            <span class="text-sm font-bold text-slate-700">{{ number_format($avg, 1) }}</span>
        </div>
    </td>
    <td class="tw-td text-right">
        <div class="flex items-center justify-end gap-2">
            <span class="tw-badge {{ $totalBadge }}">{{ $count }} trip{{ $count === 1 ? '' : 's' }}</span>
            <i class="bi bi-chevron-right text-xs text-slate-300 transition-transform duration-200" data-row-chevron></i>
        </div>
    </td>
</tr>
