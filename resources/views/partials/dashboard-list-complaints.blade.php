@forelse ($recentComplaints as $rating)
    @php
        $name = $rating->operator->user->name ?? 'Unknown';
        $initial = strtoupper(substr($name, 0, 1));
        $urgent = in_array($rating->complaint_type, ['Smoking While Driving', 'Passenger Harassment']);
    @endphp
    <div class="flex items-start gap-3 border-b border-slate-100 px-4 py-3">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-navy-500 to-blue-600 text-sm font-extrabold text-white">{{ $initial }}</div>
        <div class="min-w-0 flex-1">
            <div class="text-sm font-semibold text-slate-800">{{ \Illuminate\Support\Str::title($name) }}</div>
            @if ($rating->complaint_type)
                <div class="mt-0.5">
                    @if ($urgent)
                        <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600"><i class="bi bi-exclamation-triangle-fill"></i>{{ $rating->complaint_type }}</span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-gold-50 px-2 py-0.5 text-xs font-semibold text-gold-800"><i class="bi bi-flag-fill"></i>{{ $rating->complaint_type }}</span>
                    @endif
                </div>
            @endif
            @if ($rating->start_location && $rating->end_location)
                <div class="mt-0.5 flex flex-wrap items-center gap-1 text-xs text-slate-500">
                    <i class="bi bi-geo-alt text-blue-600"></i> {{ $rating->start_location }}
                    <i class="bi bi-arrow-right mx-1 text-slate-300"></i>
                    <i class="bi bi-geo-alt text-blue-600"></i> {{ $rating->end_location }}
                </div>
            @endif
            @if ($rating->reason)
                <div class="mt-0.5 text-xs italic text-slate-500">"{{ $rating->reason }}"</div>
            @endif
        </div>
        <div class="shrink-0">
            <span class="tw-badge tw-badge-gold">{{ $rating->rating }}</span>
        </div>
    </div>
@empty
    <div class="tw-empty">
        <div class="tw-empty-icon"><i class="bi bi-check-circle-fill text-emerald-500"></i></div>
        <p class="text-sm text-slate-500">No complaints. All good!</p>
    </div>
@endforelse
