@php
    $distTotal = array_sum($ratingDistribution);
    $distMax = $distTotal > 0 ? max(array_values($ratingDistribution)) : 0;
@endphp
<div class="tw-card flex flex-col transition-shadow duration-200 hover:shadow-md">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h3 class="tw-card-title text-sm"><i class="bi bi-bar-chart-fill mr-1 text-gold"></i> Ratings Distribution</h3>
        @if ($distTotal > 0)
            <span class="text-[11px] font-semibold text-slate-400">{{ $distTotal }} {{ $distTotal == 1 ? 'rating' : 'ratings' }}</span>
        @endif
    </div>
    @if ($distTotal > 0)
        <div id="ratingChartBody" class="flex h-64 flex-col px-5 pb-5 pt-6">
            <div class="flex flex-1 items-end gap-3">
                @for ($star = 1; $star <= 5; $star++)
                    @php
                        $count = $ratingDistribution[$star] ?? 0;
                        $pct = $distMax > 0 ? round(($count / $distMax) * 100) : 0;
                        $height = $count > 0 ? max($pct, 4) : 0;
                    @endphp
                    <div class="relative h-full flex-1">
                        <div class="absolute inset-x-0 bottom-0 rounded-t-md bg-gradient-to-t from-navy-700 to-navy-500 shadow-sm" style="height: {{ $height }}%;"></div>
                        <span class="absolute inset-x-0 text-center text-[11px] font-bold text-slate-700" style="bottom: calc({{ $height }}% + 4px);">{{ $count }}</span>
                    </div>
                @endfor
            </div>
            <div class="mt-2 flex gap-3">
                @for ($star = 1; $star <= 5; $star++)
                    <div class="flex-1 text-center text-[10px] font-semibold text-slate-500">{{ $star }} Star</div>
                @endfor
            </div>
        </div>
    @else
        <div class="flex h-64 flex-col items-center justify-center py-8 text-center">
            <div class="tw-empty-icon"><i class="bi bi-star"></i></div>
            <h4 class="text-sm font-bold text-slate-700">No ratings yet</h4>
            <p class="mt-1 text-sm text-slate-400">Passenger ratings will appear here.</p>
        </div>
    @endif
</div>
