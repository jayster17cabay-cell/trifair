@forelse ($recentComplaints as $rating)
    @php
        $initial = strtoupper(substr($rating->operator->user->name ?? 'U', 0, 1));
        $colors = ['#dc2626','#ea580c','#d97706','#b45309','#9333ea'];
        $bg = $colors[$loop->index % count($colors)];
    @endphp
    <div class="flex items-start gap-3 border-b border-slate-100 px-4 py-3">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-extrabold text-white" style="background: {{ $bg }};">{{ $initial }}</div>
        <div class="min-w-0 flex-1">
            <div class="text-sm font-semibold text-slate-800">{{ $rating->operator->user->name ?? 'Unknown' }}</div>
            @if ($rating->complaint_type)
                <div class="mt-0.5 flex items-center gap-1 text-xs text-red-600"><i class="bi bi-exclamation-triangle"></i> {{ $rating->complaint_type }}</div>
            @endif
            @if ($rating->start_location && $rating->end_location)
                <div class="mt-0.5 flex flex-wrap items-center gap-1 text-xs text-slate-500">
                    <i class="bi bi-geo-alt text-emerald-600"></i> {{ $rating->start_location }}
                    <i class="bi bi-arrow-right mx-1 text-slate-300"></i>
                    <i class="bi bi-geo-alt text-red-600"></i> {{ $rating->end_location }}
                </div>
            @endif
            @if ($rating->reason)
                <div class="mt-0.5 text-xs italic text-slate-500">"{{ $rating->reason }}"</div>
            @endif
        </div>
        <div class="shrink-0">
            <span class="tw-badge tw-badge-red">{{ $rating->rating }}</span>
        </div>
    </div>
@empty
    <div class="tw-empty">
        <div class="tw-empty-icon"><i class="bi bi-check-circle-fill text-emerald-500"></i></div>
        <p class="text-sm text-slate-500">No complaints. All good!</p>
    </div>
@endforelse
