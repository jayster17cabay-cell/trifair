@forelse ($topOperators as $operator)
    @php
        $avg = $operator->valid_ratings_avg_rating ?? 0;
        $name = $operator->user->name ?? 'Unknown';
        $rankStyles = [
            1 => ['linear-gradient(135deg, #F5B800, #E0A800)', '#fff'],
            2 => ['linear-gradient(135deg, #2E7DD1, #2563A8)', '#fff'],
            3 => ['linear-gradient(135deg, #B5D4F4, #E6F1FB)', '#0F2A4A'],
        ];
        $rank = $rankStyles[$loop->iteration] ?? ['#E6F1FB', '#64748B'];
    @endphp
    <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-black" style="background: {{ $rank[0] }}; color: {{ $rank[1] }};">
            {{ $loop->iteration }}
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-sm font-semibold text-slate-800">{{ \Illuminate\Support\Str::title($name) }}</div>
            @if ($operator->toda)
                <div class="mt-0.5 text-xs text-slate-500"><i class="bi bi-diagram-3"></i> {{ $operator->toda->name }}</div>
            @endif
        </div>
        <div class="shrink-0 text-right">
            <div class="text-sm font-black" style="color: var(--secondary-dark);">{{ number_format($avg, 1) }}</div>
            <div class="flex gap-0.5">
                @for ($i = 1; $i <= 5; $i++)
                    <i class="bi bi-star-fill text-xs" style="color: {{ $i <= round($avg) ? 'var(--secondary)' : 'var(--gray-200)' }};"></i>
                @endfor
            </div>
        </div>
    </div>
@empty
    <div class="tw-empty">
        <div class="tw-empty-icon"><i class="bi bi-inbox"></i></div>
        <p class="text-sm text-slate-500">No ratings data yet.</p>
    </div>
@endforelse
