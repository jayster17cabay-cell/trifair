@forelse ($recentRatings as $rating)
    @php
        $r = $rating->rating;
        $initial = strtoupper(substr($rating->operator->user->name ?? 'U', 0, 1));
        if ($r >= 4) { $badgeBg = '#ecfdf5'; $badgeFg = '#059669'; }
        elseif ($r <= 2) { $badgeBg = '#fef2f2'; $badgeFg = '#dc2626'; }
        else { $badgeBg = '#fffbeb'; $badgeFg = '#d97706'; }
        $avatarColors = ['#1e3a5f','#2563eb','#7c3aed','#0891b2','#059669','#d97706','#dc2626'];
        $avBg = $avatarColors[$loop->index % count($avatarColors)];
    @endphp
    <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-extrabold text-white" style="background: {{ $avBg }};">{{ $initial }}</div>
        <div class="min-w-0 flex-1">
            <div class="text-sm font-semibold text-slate-800">{{ $rating->operator->user->name ?? 'Unknown' }}</div>
            <div class="mt-0.5 flex flex-wrap items-center gap-1 text-xs text-slate-500">
                <span class="flex gap-0.5">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="bi bi-star-fill" style="color: {{ $i <= $r ? 'var(--secondary)' : 'var(--gray-200)' }}; font-size: 0.55rem;"></i>
                    @endfor
                </span>
                @if ($rating->reason)
                    <span>&middot; "{{ $rating->reason }}"</span>
                @endif
            </div>
        </div>
        <div class="hidden shrink-0 text-xs text-slate-400 sm:block">{{ $rating->created_at->diffForHumans() }}</div>
        <div class="shrink-0">
            <span class="tw-badge" style="background: {{ $badgeBg }}; color: {{ $badgeFg }};">{{ $r }}</span>
        </div>
    </div>
@empty
    <div class="tw-empty">
        <div class="tw-empty-icon"><i class="bi bi-inbox"></i></div>
        <p class="text-sm text-slate-500">No ratings yet.</p>
    </div>
@endforelse
