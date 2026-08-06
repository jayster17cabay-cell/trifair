{{-- Shared reports page body. Requires: $routePrefix, $operators --}}

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-bar-chart-line mr-2 text-violet-600"></i>Operator Performance</h1>
        <p class="tw-page-sub">Analytics and performance overview of all operators</p>
    </div>
</div>

<div class="tw-table-wrap">
    <div class="overflow-x-auto">
        <table class="tw-table">
            <thead>
                <tr>
                    <th class="tw-th w-9"></th>
                    <th class="tw-th">#</th>
                    <th class="tw-th">Operator</th>
                    <th class="tw-th">Plate #</th>
                    <th class="tw-th">Body #</th>
                    <th class="tw-th">License</th>
                    <th class="tw-th">Status</th>
                    <th class="tw-th">Average Rating</th>
                    <th class="tw-th text-center">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($operators as $operator)
                    @php $avg = $operator->valid_ratings_avg_rating ?? 0; @endphp
                    <tr class="tw-tr-hover" style="cursor: pointer;" onclick="toggleTrips({{ $operator->id }})">
                        <td class="tw-td text-center">
                            <i class="bi bi-chevron-right" id="icon-{{ $operator->id }}" style="transition: transform 0.2s; color: #94a3b8; font-size: 0.7rem;"></i>
                        </td>
                        <td class="tw-td text-slate-500">{{ ($operators->firstItem() ?? 0) + $loop->index }}</td>
                        <td class="tw-td">
                            <div class="flex items-center gap-2.5">
                                <div class="tw-avatar tw-avatar-sm bg-gradient-to-br from-navy-600 to-navy-500 text-white">{{ strtoupper(substr($operator->user->name, 0, 1)) }}</div>
                                <strong class="cursor-pointer text-sm text-blue-600 underline underline-offset-3 hover:text-blue-700">{{ $operator->user->name }}</strong>
                            </div>
                        </td>
                        <td class="tw-td text-sm font-semibold">{{ $operator->plate_number ?? '—' }}</td>
                        <td class="tw-td text-sm">{{ $operator->body_number ?? '—' }}</td>
                        <td class="tw-td text-sm text-slate-500">{{ $operator->license_number ?? '—' }}</td>
                        <td class="tw-td">
                            @if ($operator->status === 'active')
                                <span class="tw-badge tw-badge-green"><i class="bi bi-check-circle-fill"></i>Active</span>
                            @else
                                <span class="tw-badge tw-badge-amber"><i class="bi bi-pause-circle-fill"></i>Inactive</span>
                            @endif
                        </td>
                        <td class="tw-td">
                            <div class="flex items-center gap-2">
                                <div class="flex gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="bi bi-star-fill text-xs {{ $i <= round($avg) ? 'text-amber-500' : 'text-slate-200' }}"></i>
                                    @endfor
                                </div>
                                <span class="tw-badge tw-badge-amber">{{ number_format($avg, 1) }}</span>
                            </div>
                        </td>
                        <td class="tw-td text-center">
                            <span class="tw-badge tw-badge-navy"><i class="bi bi-star"></i>{{ $operator->valid_ratings_count }}</span>
                        </td>
                    </tr>
                    <tr id="trips-{{ $operator->id }}" style="display:none;" data-trips-url="{{ route($routePrefix . '.reports.trips', $operator->id) }}">
                        <td colspan="9" class="bg-slate-50 p-0">
                            <div class="report-trips-body p-4" data-loaded="0"></div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center">
                            <div class="tw-empty">
                                <div class="tw-empty-icon"><i class="bi bi-inbox"></i></div>
                                <p class="text-sm text-slate-500">No operators found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 flex justify-center">
    {{ $operators->links('pagination::tailwind') }}
</div>

<script>
    function toggleTrips(id) {
        var row = document.getElementById('trips-' + id);
        var icon = document.getElementById('icon-' + id);
        if (row.style.display !== 'none') {
            row.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
            return;
        }
        row.style.display = 'table-row';
        icon.style.transform = 'rotate(90deg)';
        var body = row.querySelector('.report-trips-body');
        if (!body || body.dataset.loaded === '1') return;
        body.dataset.loaded = '1';
        body.innerHTML = '<div class="flex items-center justify-center gap-2 py-3 text-xs text-slate-500"><div class="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-navy-600"></div>Loading trips...</div>';
        fetch(row.dataset.tripsUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error('load failed'); return r.json(); })
            .then(function (data) { body.innerHTML = data.html || '<div class="py-3 text-center text-xs text-slate-500">No trips recorded yet.</div>'; })
            .catch(function () { body.innerHTML = '<div class="py-3 text-center text-xs text-slate-500">Failed to load trips.</div>'; });
    }
</script>
