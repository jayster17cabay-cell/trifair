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
            if ($rr >= 4) { $rbg = 'bg-blue-50'; $rcg = 'text-navy-600'; }
            elseif ($rr <= 2) { $rbg = 'bg-amber-50'; $rcg = 'text-amber-700'; }
            else { $rbg = 'bg-amber-50'; $rcg = 'text-amber-700'; }
        @endphp
        <div class="mb-2 flex items-start gap-2 pb-2 {{ !$loop->last ? 'border-b border-slate-200' : '' }}">
            <div class="tw-avatar tw-avatar-sm {{ $rbg }} {{ $rcg }}">{{ $rating->rating }}</div>
            <div class="min-w-0 flex-1">
                @if ($rating->start_location && $rating->end_location)
                    <div class="flex items-start gap-1.5">
                        <div class="flex w-3.5 shrink-0 flex-col items-center pt-0.5">
                            <div class="h-2 w-2 shrink-0 rounded-full bg-emerald-600"></div>
                            <div class="w-0.5 flex-1 rounded bg-gradient-to-b from-emerald-600 to-red-600" style="min-height: 20px;"></div>
                            <div class="h-2 w-2 shrink-0 rounded-full bg-red-600"></div>
                        </div>
                        <div class="min-w-0 text-xs">
                            <div class="font-semibold text-emerald-600">
                                <i class="bi bi-geo-alt-fill mr-1 text-[0.6rem]"></i>{{ $rating->start_location }}
                            </div>
                            <div class="font-semibold text-red-600">
                                <i class="bi bi-geo-alt-fill mr-1 text-[0.6rem]"></i>{{ $rating->end_location }}
                            </div>
                        </div>
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
        <i class="bi bi-inbox text-2xl text-slate-300"></i>
        <p class="mt-1 text-xs text-slate-500">No trips recorded yet.</p>
    </div>
@endif
