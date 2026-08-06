<div class="tw-card">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h3 class="tw-card-title text-sm"><i class="bi bi-bar-chart-fill mr-1 text-gold"></i> Ratings Distribution</h3>
        <p class="text-[11px] text-slate-400">Per star level</p>
    </div>
    @php $distTotal = array_sum($ratingDistribution); @endphp
    @if ($distTotal > 0)
        @php $distMax = max(array_values($ratingDistribution)); @endphp
        <div id="ratingChartBody" class="flex h-44 items-end gap-3 px-4 pt-4">
            @for ($star = 1; $star <= 5; $star++)
                @php
                    $count = $ratingDistribution[$star] ?? 0;
                    $pct = $distMax > 0 ? round(($count / $distMax) * 100) : 0;
                @endphp
                <div class="flex h-full flex-1 flex-col items-center justify-end">
                    <span class="mb-1 text-[11px] font-bold text-slate-700">{{ $count }}</span>
                    <div class="w-full rounded-t-md" style="height: {{ max($pct, 2) }}%; background: linear-gradient(180deg, #2e7dd1, #0f2a4a);"></div>
                </div>
            @endfor
        </div>
        <div class="flex gap-3 px-4 pb-4">
            @for ($star = 1; $star <= 5; $star++)
                <div class="flex-1 text-center text-[10px] font-semibold text-slate-500">{{ $star }} Star</div>
            @endfor
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-8 text-center">
            <div class="tw-empty-icon"><i class="bi bi-star"></i></div>
            <h4 class="text-sm font-bold text-slate-700">No ratings yet</h4>
            <p class="mt-1 text-sm text-slate-400">Passenger ratings will appear here.</p>
        </div>
    @endif
</div>
