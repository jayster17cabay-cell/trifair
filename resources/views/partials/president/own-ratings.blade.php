{{--
    Section A — the president's own performance/rating data (as an operator).
    Requires: $ownOperator (App\Models\Operator|null), $ownRecentRatings (collection).
--}}
@php
    $ownName = $ownOperator && $ownOperator->user ? $ownOperator->user->name : Auth::user()->name;
    $avg = $ownOperator ? round((float) $ownOperator->ratings()->isValid()->avg('rating'), 1) : 0;
    $total = $ownOperator ? $ownOperator->ratings()->isValid()->count() : 0;
@endphp
<div class="tw-card transition-shadow duration-200 hover:shadow-md">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h3 class="tw-card-title text-sm"><i class="bi bi-star-fill mr-1 text-gold"></i> My Ratings</h3>
        <span class="tw-badge tw-badge-blue">{{ $total }} {{ $total === 1 ? 'Rating' : 'Ratings' }}</span>
    </div>

    <div class="border-b border-slate-100 p-4">
        <div class="flex items-center gap-3">
            <span class="text-3xl font-extrabold text-slate-900">{{ number_format($avg, 1) }}</span>
            <div>
                <div class="flex gap-0.5">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="bi {{ $i <= round($avg) ? 'bi-star-fill text-gold' : 'bi-star text-slate-300' }}"></i>
                    @endfor
                </div>
                <div class="mt-0.5 text-[11px] font-medium text-slate-400">{{ $ownName }}</div>
            </div>
        </div>
    </div>

    <div class="divide-y divide-slate-100">
        @forelse ($ownRecentRatings as $rating)
            <div class="flex items-start justify-between gap-3 px-4 py-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= $rating->rating ? 'bi-star-fill text-gold' : 'bi-star text-slate-200' }} text-sm"></i>
                        @endfor
                        @if (!empty($rating->complaint_type))
                            <span class="tw-badge tw-badge-red ml-1"><i class="bi bi-exclamation-triangle-fill"></i> Complaint</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-slate-600">{{ $rating->complaint_details ?? $rating->reason ?? '—' }}</p>
                    <p class="mt-0.5 text-[11px] text-slate-400">{{ $rating->created_at->format('M d, Y') }}</p>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center px-4 py-8 text-center">
                <div class="tw-empty-icon"><i class="bi bi-star"></i></div>
                <h4 class="text-sm font-bold text-slate-700">No ratings yet</h4>
                <p class="mt-1 text-sm text-slate-400">Passenger ratings for your own driving will appear here.</p>
            </div>
        @endforelse
    </div>
</div>
