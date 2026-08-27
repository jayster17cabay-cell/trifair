{{-- Shared invalid-ratings page body. Requires: $routePrefix, $ratings --}}

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-x-circle mr-2 text-red-600"></i>Invalid Ratings</h1>
        <p class="tw-page-sub">Ratings marked as invalid — not counted in operator averages</p>
    </div>
</div>

<div class="mb-6 max-w-md">
    <div class="tw-stat">
        <div class="tw-stat-icon tw-stat-icon-red"><i class="bi bi-x-circle"></i></div>
        <div class="tw-stat-num text-red-600">{{ $ratings->total() }}</div>
        <div class="tw-stat-label">Total Invalid</div>
    </div>
</div>

<div class="tw-table-scroll-wrap">
    <table class="tw-table min-w-[52rem]">
        <thead class="tw-thead-sticky">
            <tr>
                <th class="tw-th">#</th>
                <th class="tw-th">Operator</th>
                <th class="tw-th">Rating</th>
                <th class="tw-th">Reason</th>
                <th class="tw-th">Invalid Reason</th>
                <th class="tw-th">Route</th>
                <th class="tw-th">Date</th>
                <th class="tw-th text-right">Action</th>
            </tr>
        </thead>
            <tbody>
                @forelse ($ratings as $rating)
                    <tr class="tw-tr-hover">
                        <td class="tw-td text-slate-500">{{ $loop->iteration + ($ratings->currentPage() - 1) * $ratings->perPage() }}</td>
                        <td class="tw-td">
                            <div class="flex items-center gap-2.5">
                                <strong class="text-sm">{{ $rating->operator->user->name }}</strong>
                            </div>
                        </td>
                        <td class="tw-td">
                            <div class="flex items-center gap-2">
                                <div class="flex gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="bi bi-star-fill text-xs {{ $i <= $rating->rating ? 'text-slate-300' : 'text-slate-200' }}"></i>
                                    @endfor
                                </div>
                                <span class="tw-badge tw-badge-red">{{ $rating->rating }}</span>
                            </div>
                        </td>
                        <td class="tw-td max-w-[200px] text-sm text-slate-500">{{ $rating->reason ?? '—' }}</td>
                        <td class="tw-td"><span class="text-xs font-semibold text-red-600">{{ $rating->invalid_reason }}</span></td>
                        <td class="tw-td">
                            @if ($rating->start_location && $rating->end_location)
                                <div class="text-xs">
                                    <span class="font-semibold text-emerald-600"><i class="bi bi-circle-fill mr-1 text-[0.35rem]"></i>{{ $rating->start_location }}</span>
                                    <br>
                                    <span class="font-semibold text-red-600"><i class="bi bi-record-fill mr-1 text-[0.35rem]"></i>{{ $rating->end_location }}</span>
                                </div>
                            @else
                                <span class="text-xs text-slate-500">No route data</span>
                            @endif
                        </td>
                        <td class="tw-td text-xs text-slate-500">{{ $rating->created_at->format('M d, Y h:i A') }}</td>
                        <td class="tw-td text-right">
                            <form action="{{ route($routePrefix . '.ratings.restore', $rating) }}" method="POST" onsubmit="return confirm('Restore this rating? It will count towards the operator\'s average again.')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold"><i class="bi bi-check-circle"></i>Restore</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center">
                            <div class="tw-empty">
                                <div class="tw-empty-icon"><i class="bi bi-check-circle text-emerald-500"></i></div>
                                <h3 class="tw-empty-title">No Invalid Ratings</h3>
                                <p class="text-sm text-slate-500">All ratings are valid.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
        </tbody>
    </table>
</div>

@if ($ratings->hasPages())
    <div class="mt-3">
        {{ $ratings->links('pagination::tailwind') }}
    </div>
@endif
