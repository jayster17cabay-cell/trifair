@forelse ($topOperators as $operator)
    @php
        $avg = $operator->valid_ratings_avg_rating ?? 0;
        $name = $operator->user->name ?? 'Unknown';
        $rankClasses = [
            1 => 'bg-gradient-to-br from-gold to-gold-dark text-white',
            2 => 'bg-gradient-to-br from-navy-500 to-blue-600 text-white',
            3 => 'bg-gradient-to-br from-blue-200 to-blue-50 text-navy-800',
        ];
        $rank = $rankClasses[$loop->iteration] ?? 'bg-blue-50 text-slate-500';
    @endphp
    <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-black {{ $rank }}">
            {{ $loop->iteration }}
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-sm font-semibold text-slate-800">{{ \Illuminate\Support\Str::title($name) }}</div>
            @if ($operator->toda)
                <div class="mt-0.5 text-xs text-slate-500"><i class="bi bi-diagram-3"></i> {{ $operator->toda->name }}</div>
            @endif
        </div>
        <div class="shrink-0 text-right">
            <div class="text-sm font-black text-gold-700">{{ number_format($avg, 1) }}</div>
            <div class="flex gap-0.5">
                @for ($i = 1; $i <= 5; $i++)
                    <i class="bi bi-star-fill text-xs {{ $i <= round($avg) ? 'text-amber-400' : 'text-slate-200' }}"></i>
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
