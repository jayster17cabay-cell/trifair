@php
    $distTotal = array_sum($ratingDistribution);
    $distMax = $distTotal > 0 ? max(array_values($ratingDistribution)) : 0;
@endphp
<div class="tw-card flex flex-col transition-shadow duration-200 hover:shadow-md">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h3 class="tw-card-title text-sm"><i class="bi bi-star-half mr-1 text-gold"></i> Ratings Distribution</h3>
        @if ($distTotal > 0)
            <span class="text-[11px] font-semibold text-slate-400">{{ $distTotal }} {{ $distTotal == 1 ? 'rating' : 'ratings' }}</span>
        @endif
    </div>
    @if ($distTotal > 0)
        <div id="ratingChartBody" class="flex-1 space-y-3 p-4">
            @for ($star = 5; $star >= 1; $star--)
                @php
                    $count = $ratingDistribution[$star] ?? 0;
                    $pct = $distMax > 0 ? round(($count / $distMax) * 100) : 0;
                    $width = $count > 0 ? max($pct, 3) : 0;
                @endphp
                <div class="flex items-center gap-3">
                    <span class="flex w-11 shrink-0 items-center gap-1 text-xs font-bold text-slate-600">
                        {{ $star }}<i class="bi bi-star-fill text-[11px] text-gold"></i>
                    </span>
                    <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-gold to-gold-dark" style="width: {{ $width }}%;"></div>
                    </div>
                    <span class="w-8 shrink-0 text-right text-xs font-bold text-slate-900">{{ $count }}</span>
                </div>
            @endfor
        </div>
    @else
        <div class="flex flex-1 flex-col items-center justify-center py-8 text-center">
            <div class="tw-empty-icon"><i class="bi bi-star"></i></div>
            <h4 class="text-sm font-bold text-slate-700">No ratings yet</h4>
            <p class="mt-1 text-sm text-slate-400">Passenger ratings will appear here.</p>
        </div>
    @endif
</div>
