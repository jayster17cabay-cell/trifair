@if ($operator->validRatings && $operator->validRatings->count() > 0)
    <div class="mb-3 flex flex-wrap items-center gap-2 text-[0.7rem] font-bold uppercase tracking-wider text-slate-500">
        <i class="bi bi-clock-history"></i> All Trips
        <span class="tw-badge tw-badge-gray">{{ $totalTrips }}</span>
        @if ($totalTrips > $operator->validRatings->count())
            <span class="font-medium normal-case tracking-normal text-slate-400">(showing latest {{ $operator->validRatings->count() }})</span>
        @endif
    </div>
    @foreach ($operator->validRatings as $rating)
        @php
            $rr = $rating->rating;
            if ($rr >= 4) { $bc = 'tw-badge-green'; }
            elseif ($rr <= 2) { $bc = 'tw-badge-red'; }
            else { $bc = 'tw-badge-amber'; }
        @endphp
        <div class="mb-2 flex items-start gap-2 {{ !$loop->last ? 'border-b border-slate-100 pb-2' : '' }}">
            <span class="tw-badge {{ $bc }} shrink-0">{{ $rr }}</span>
            <div class="min-w-0 flex-1">
                @if ($rating->start_location && $rating->end_location)
                    <div class="text-xs">
                        <span class="font-semibold text-emerald-600"><i class="bi bi-circle-fill mr-1 text-[0.35rem]"></i>{{ $rating->start_location }}</span>
                        <br>
                        <span class="font-semibold text-red-600"><i class="bi bi-record-fill mr-1 text-[0.35rem]"></i>{{ $rating->end_location }}</span>
                    </div>
                @else
                    <span class="text-xs text-slate-500">No route data</span>
                @endif
                <div class="mt-0.5 text-[0.65rem] text-slate-400">
                    <i class="bi bi-clock mr-1"></i>{{ $rating->created_at->diffForHumans() }}
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="py-3 text-center">
        <p class="text-xs text-slate-500">No trips recorded yet.</p>
    </div>
@endif
