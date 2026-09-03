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
<div class="tw-card overflow-hidden transition-shadow duration-200 hover:shadow-md">
    <div class="flex items-center justify-between px-4 py-3" style="background-image: linear-gradient(135deg, #0a1d33 0%, #0f2a4a 100%);">
        <h3 class="tw-card-title text-sm !text-white"><i class="bi bi-bar-chart-fill mr-1 text-gold"></i> Member Ratings</h3>
        <p class="text-xs text-slate-300">Avg rating per member</p>
    </div>

    @if ($btotal > 0)
        <div class="space-y-4 p-4">
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
                            <span class="hidden text-xs text-slate-400 sm:inline">{{ $member->body_number }}</span>
                            @if ($isTop)
                                <span class="tw-badge tw-badge-green"><i class="bi bi-trophy-fill"></i> Top</span>
                            @elseif ($isLow)
                                <span class="tw-badge tw-badge-red"><i class="bi bi-arrow-down"></i> Lowest</span>
                            @endif
                        </div>
                        <span class="shrink-0 text-sm font-bold text-navy-800">{{ number_format($member->average, 1) }}</span>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-gold to-gold-dark transition-all" style="width: {{ max($barPct, 2) }}%;"></div>
                    </div>
                    <div class="mt-0.5 flex justify-between text-[11px] text-slate-400">
                        <span><i class="bi bi-star mr-0.5"></i>{{ $member->total_ratings }} ratings</span>
                        @if ($member->complaints > 0)
                            <span class="font-semibold text-red-500"><i class="bi bi-flag mr-0.5"></i>{{ $member->complaints }} complaint{{ $member->complaints === 1 ? '' : 's' }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center px-4 py-10 text-center">
            <div class="tw-empty-icon"><i class="bi bi-bar-chart"></i></div>
            <h4 class="text-sm font-bold text-slate-700">No member data</h4>
            <p class="mt-1 text-sm text-slate-400">Member ratings will appear here once available.</p>
        </div>
    @endif
</div>
