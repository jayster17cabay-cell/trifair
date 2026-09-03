{{--
    Section C — rating breakdown across the president's TODA members.
    Requires: $breakdown (collection of objects: id, name, body_number, status,
    average, total_ratings, complaints).
--}}
@php
    $btotal = $breakdown->count();
    $top = $breakdown->first();
    $lowest = $breakdown->last();
    $maxAvg = $breakdown->max('average') ?: 0;
@endphp
<div class="tw-card transition-shadow duration-200 hover:shadow-md">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h3 class="tw-card-title text-sm"><i class="bi bi-bar-chart-fill mr-1 text-gold"></i> Member Ratings</h3>
        <p class="text-[11px] text-slate-400">Avg rating per member</p>
    </div>

    @if ($btotal > 0)
        <div class="space-y-3 p-4">
            @foreach ($breakdown as $member)
                @php
                    $barPct = $maxAvg > 0 ? round(($member->average / 5) * 100) : 0;
                    $isTop = $top && $member->id === $top->id && $member->average > 0;
                    $isLow = $lowest && $lowest->average > 0 && $member->average === $lowest->average && $member->average < $top->average;
                @endphp
                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="truncate text-sm font-semibold text-slate-700">{{ $member->name }}</span>
                            <span class="hidden text-[11px] text-slate-400 sm:inline">{{ $member->body_number }}</span>
                            @if ($isTop)
                                <span class="tw-badge tw-badge-green"><i class="bi bi-trophy-fill"></i> Top</span>
                            @elseif ($isLow)
                                <span class="tw-badge tw-badge-red"><i class="bi bi-arrow-down"></i> Lowest</span>
                            @endif
                        </div>
                        <span class="shrink-0 text-sm font-bold text-slate-800">{{ number_format($member->average, 1) }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-gold to-gold-dark transition-all" style="width: {{ max($barPct, 2) }}%;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center px-4 py-8 text-center">
            <div class="tw-empty-icon"><i class="bi bi-bar-chart"></i></div>
            <h4 class="text-sm font-bold text-slate-700">No member data</h4>
            <p class="mt-1 text-sm text-slate-400">Member ratings will appear here once available.</p>
        </div>
    @endif
</div>
