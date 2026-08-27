@forelse ($recentRatings as $rating)
    @php
        $r = $rating->rating;
        $name = $rating->operator->user->name ?? 'Unknown';
    @endphp
    <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3">
        <div class="min-w-0 flex-1">
            <div class="text-sm font-semibold text-slate-800">{{ \Illuminate\Support\Str::title($name) }}</div>
            <div class="mt-0.5 flex flex-wrap items-center gap-1 text-xs text-slate-500">
                <span class="flex gap-0.5">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="bi bi-star-fill {{ $i <= $r ? 'text-amber-400' : 'text-slate-200' }}" style="font-size: 0.55rem;"></i>
                    @endfor
                </span>
                @if ($rating->reason)
                    <span>&middot; "{{ $rating->reason }}"</span>
                @endif
            </div>
        </div>
        <div class="hidden shrink-0 text-xs text-slate-400 sm:block">{{ $rating->created_at->diffForHumans() }}</div>
        <div class="shrink-0">
            <span class="tw-badge tw-badge-blue">{{ $r }}</span>
        </div>
    </div>
@empty
    <div class="tw-empty">
        <div class="tw-empty-icon"><i class="bi bi-inbox"></i></div>
        <p class="text-sm text-slate-500">No ratings yet.</p>
    </div>
@endforelse
