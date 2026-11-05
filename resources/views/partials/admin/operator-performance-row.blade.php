{{--
    Reusable Operator Performance table row. The operator name is plain text;
    only the explicit "View trips" button opens the trip-history drawer
    (partials/admin/trip-history-drawer), so the row cannot be opened by
    accident.
    Requires:
    - $operator    App\Models\Operator  (with user eager-loaded; expects the
                   valid_ratings_avg_rating / valid_ratings_count columns from
                   the reportsData join, or pass $avg explicitly)
    - $routePrefix string               'superadmin' | 'tfrb-officer'
    - $avg         float|null           optional override for the average rating
    - $dateFrom    string|null          optional period filter (mirrors the row)
    - $dateTo      string|null          optional period filter (mirrors the row)
    The trip button exposes data-* attributes consumed by
    initTripHistoryDrawer().
--}}
@php
    $todaBreakdown = $todaBreakdown ?? collect();
    $avg = isset($avg) ? (float) $avg : (float) ($operator->valid_ratings_avg_rating ?? 0);
    $count = (int) ($operator->valid_ratings_count ?? 0);
    $name = $operator->user->name ?? 'Unknown';
    $totalBadge = $count === 0 ? 'tw-badge-gray' : 'tw-badge-navy';
    $initials = collect(preg_split('/\s+/', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp
<tr class="tw-tr-hover">
    <td class="tw-td">
        <div class="flex items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-navy-50 text-[11px] font-extrabold text-navy-700 ring-1 ring-navy-100">
                {{ $initials ?: '—' }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-slate-800">{{ $name }}</p>
                @if ($todaBreakdown->count() === 1)
                    <p class="mt-0.5 flex items-center gap-1 text-[11px] text-slate-400">
                        <i class="bi bi-diagram-3"></i>
                        <span class="truncate">{{ $todaBreakdown->first()['name'] }}</span>
                    </p>
                @elseif ($todaBreakdown->count() > 1)
                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                        @foreach ($todaBreakdown as $toda)
                            <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[0.68rem] font-semibold text-slate-600"
                                  title="{{ $toda['name'] }} — {{ $toda['count'] }} trip{{ $toda['count'] === 1 ? '' : 's' }} in the selected period">
                                <span class="inline-block h-1.5 w-1.5 shrink-0 rounded-full bg-navy-600"></span>
                                <span class="truncate" style="max-width: 8rem;">{{ $toda['name'] }}</span>
                                <i class="bi bi-star-fill text-[0.65rem] text-amber-400"></i>
                                <span class="text-slate-700">{{ $toda['avg'] !== null ? number_format($toda['avg'], 1) : '—' }}</span>
                                <span class="font-normal text-slate-400">({{ $toda['count'] }})</span>
                            </span>
                        @endforeach
                    </div>
                @elseif ($operator->toda?->name)
                    <p class="mt-0.5 flex items-center gap-1 text-[11px] text-slate-400">
                        <i class="bi bi-diagram-3"></i>
                        <span class="truncate">{{ $operator->toda->name }}</span>
                    </p>
                @endif
            </div>
        </div>
    </td>
    <td class="tw-td">
        <span class="text-sm font-semibold tabular-nums text-slate-700">{{ $operator->plate_number ?: '—' }}</span>
    </td>
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
            <span class="text-sm font-bold tabular-nums text-slate-700">{{ number_format($avg, 1) }}</span>
        </div>
    </td>
    <td class="tw-td text-right">
        <div class="flex items-center justify-end gap-2">
            <span class="tw-badge {{ $totalBadge }}">{{ $count }} trip{{ $count === 1 ? '' : 's' }}</span>
            <button type="button"
                    class="tw-btn tw-btn-sm tw-btn-outline"
                    data-open-trips
                    data-trips-url="{{ route($routePrefix . '.reports.trips', array_filter(['operator' => $operator->id, 'date_from' => $dateFrom ?? null, 'date_to' => $dateTo ?? null])) }}"
                    data-name="{{ $name }}"
                    data-plate="{{ $operator->plate_number ?? '' }}"
                    data-count="{{ $count }}"
                    data-avg="{{ number_format($avg, 1) }}"
                    title="View trip history for {{ $name }}"
                    aria-label="View trip history for {{ $name }}">
                <i class="bi bi-clock-history"></i>
                <span class="hidden sm:inline">View trips</span>
                <i class="bi bi-chevron-right text-[10px] text-slate-400 transition-transform duration-200" data-row-chevron></i>
            </button>
        </div>
    </td>
</tr>
