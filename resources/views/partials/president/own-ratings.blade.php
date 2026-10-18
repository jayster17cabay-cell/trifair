{{--
    Section A — the president's own performance/rating data (as an operator).
    Requires: $ownOperator (App\Models\Operator|null), $ownRecentRatings (collection).
--}}
@php
    $ownName = $ownOperator && $ownOperator->user ? $ownOperator->user->name : Auth::user()->name;
    $ownStats = $ownOperator
        ? $ownOperator->countableRatings()
            ->selectRaw('COUNT(*) as total, AVG(rating) as avg, SUM(CASE WHEN complaint_type IS NOT NULL THEN 1 ELSE 0 END) as complaints')
            ->first()
        : null;
    $avg = $ownStats && $ownStats->total > 0 ? round((float) $ownStats->avg, 1) : 0;
    $total = $ownStats ? (int) $ownStats->total : 0;
    $complaints = $ownStats ? (int) $ownStats->complaints : 0;

    // Ratings stay separated per TODA: a driver who belongs to several TODAs
    // sees each organisation's own average/count, plus the combined total
    // across every TODA they belong to.
    $byToda = collect();
    if ($ownOperator) {
        $byToda = $ownOperator->getRateableTodas()->map(function ($t) use ($ownOperator) {
            $s = $ownOperator->countableRatings()
                ->where('toda_id', $t->id)
                ->selectRaw('COUNT(*) as total, AVG(rating) as avg')
                ->first();

            return [
                'id' => (int) $t->id,
                'name' => $t->name,
                'total' => (int) ($s->total ?? 0),
                'avg' => $s && $s->total > 0 ? round((float) $s->avg, 1) : 0.0,
            ];
        })->values();
    }
@endphp
<div class="tw-card overflow-hidden transition-shadow duration-200 hover:shadow-md">
    {{-- Header band — navy blue with gold accent --}}
    <div class="flex items-center justify-between px-4 py-3" style="background-image: linear-gradient(135deg, #071e42 0%, #0A2A5E 100%);">
        <h3 class="tw-card-title text-sm !text-white"><i class="bi bi-star-fill mr-1 text-gold"></i> My Ratings</h3>
        <span class="tw-badge !bg-gold !text-navy-800">{{ $total }} {{ $total === 1 ? 'Rating' : 'Ratings' }}</span>
    </div>

    {{-- Average summary --}}
    <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 bg-slate-50/60 p-4">
        <div class="flex items-center gap-3">
            <span class="text-4xl font-extrabold text-navy-800">{{ number_format($avg, 1) }}</span>
            <div>
                <div class="flex gap-0.5">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="bi {{ $i <= round($avg) ? 'bi-star-fill text-gold' : 'bi-star text-slate-300' }}"></i>
                    @endfor
                </div>
                <div class="mt-0.5 text-xs font-semibold text-slate-500">{{ $ownName }}</div>
            </div>
        </div>
        <div class="ml-auto flex gap-4 text-center">
            <div>
                <div class="text-xl font-bold text-navy-800">{{ $total }}</div>
                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Ratings</div>
            </div>
            <div>
                <div class="text-xl font-bold text-red-500">{{ $complaints }}</div>
                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Complaints</div>
            </div>
        </div>
    </div>

    {{-- Per-TODA breakdown — each TODA kept separate, then the combined total --}}
    @if ($byToda->count() > 1)
        <div class="border-b border-slate-100 bg-slate-50/40 px-4 py-3">
            <div class="mb-2 flex items-center gap-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">
                <i class="bi bi-diagram-3"></i> Per TODA
            </div>
            <div class="space-y-2">
                @foreach ($byToda as $todaRow)
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0 flex items-center gap-2">
                            <span class="truncate text-sm font-semibold text-slate-700">{{ $todaRow['name'] }}</span>
                            <span class="shrink-0 text-xs text-slate-400">{{ $todaRow['total'] }} {{ $todaRow['total'] === 1 ? 'rating' : 'ratings' }}</span>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi {{ $i <= round($todaRow['avg']) ? 'bi-star-fill text-gold' : 'bi-star text-slate-300' }} text-xs"></i>
                            @endfor
                            <span class="w-8 text-right text-sm font-bold text-navy-800">{{ number_format($todaRow['avg'], 1) }}</span>
                        </div>
                    </div>
                @endforeach
                <div class="flex items-center justify-between gap-3 border-t border-slate-200 pt-2">
                    <span class="text-sm font-bold text-navy-800">Total ({{ $byToda->count() }} TODAs)</span>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-400">{{ $total }} {{ $total === 1 ? 'rating' : 'ratings' }}</span>
                        <span class="w-8 text-right text-sm font-extrabold text-navy-800">{{ number_format($avg, 1) }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Recent ratings list --}}
    <div class="divide-y divide-slate-100">
        @forelse ($ownRecentRatings as $rating)
            <div class="flex items-start justify-between gap-3 px-4 py-3 transition-colors hover:bg-slate-50">
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= $rating->rating ? 'bi-star-fill text-gold' : 'bi-star text-slate-300' }} text-sm"></i>
                        @endfor
                        @if (!empty($rating->complaint_type))
                            <span class="tw-badge tw-badge-red ml-1"><i class="bi bi-exclamation-triangle-fill"></i> {{ $rating->complaint_type }}</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-slate-600">{{ $rating->complaint_details ?? $rating->reason ?? '—' }}</p>
                    <p class="mt-0.5 text-xs text-slate-400"><i class="bi bi-calendar3 mr-1"></i>{{ $rating->created_at->format('M d, Y') }}</p>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center px-4 py-10 text-center">
                <div class="tw-empty-icon"><i class="bi bi-star"></i></div>
                <h4 class="text-sm font-bold text-slate-700">No ratings yet</h4>
                <p class="mt-1 text-sm text-slate-400">Passenger ratings for your own driving will appear here.</p>
            </div>
        @endforelse
    </div>
</div>
