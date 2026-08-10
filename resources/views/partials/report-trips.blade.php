{{-- Trip history list rendered inside the reports drawer. Requires: $operator, $totalTrips --}}

@php
    $shown = $operator->validRatings->count();
@endphp
@if ($shown > 0)
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-500">
            <i class="bi bi-clock-history mr-1"></i> Trip History
        </div>
        @if ($totalTrips > $shown)
            <span class="text-[0.65rem] text-slate-400">Showing latest {{ $shown }} of {{ $totalTrips }}</span>
        @else
            <span class="tw-badge tw-badge-gray">{{ $totalTrips }} trip{{ $totalTrips === 1 ? '' : 's' }}</span>
        @endif
    </div>

    <div class="space-y-2">
        @foreach ($operator->validRatings as $rating)
            @php
                $rr = (int) $rating->rating;
                $isLow = $rr <= 2;
                $isMore = $loop->index >= 5;
            @endphp
            <div class="rounded-xl border p-3 {{ $isLow ? 'border-red-100 bg-red-50/60' : 'border-slate-100 bg-white' }} {{ $isMore ? 'hidden' : '' }}" {{ $isMore ? 'data-trip-more' : '' }}>
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        <span class="flex gap-0.5">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi text-[0.7rem] {{ $i <= $rr ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-200' }}"></i>
                            @endfor
                        </span>
                        <span class="text-xs font-bold text-slate-700">{{ $rr }}</span>
                    </div>
                    <span class="shrink-0 text-[0.65rem] text-slate-400">{{ $rating->created_at->diffForHumans() }}</span>
                </div>
                <div class="mt-2.5 space-y-1.5 text-xs">
                    <div class="flex items-center gap-1.5 text-slate-600">
                        <i class="bi bi-circle-fill shrink-0 text-[0.4rem] text-emerald-600"></i>
                        <span class="truncate">{{ $rating->start_location ?: 'No pickup recorded' }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-slate-600">
                        <i class="bi bi-circle-fill shrink-0 text-[0.4rem] text-red-600"></i>
                        <span class="truncate">{{ $rating->end_location ?: 'No dropoff recorded' }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($shown > 5)
        <button type="button" data-trip-show-all class="tw-btn tw-btn-sm tw-btn-ghost mt-3 w-full">
            <i class="bi bi-chevron-expand"></i> Show all {{ $shown }} trips
        </button>
    @endif
@else
    <div class="flex flex-col items-center gap-2 py-12 text-center">
        <div class="tw-empty-icon tw-empty-icon-navy"><i class="bi bi-inbox"></i></div>
        <p class="text-sm text-slate-500">No trips recorded yet.</p>
    </div>
@endif
