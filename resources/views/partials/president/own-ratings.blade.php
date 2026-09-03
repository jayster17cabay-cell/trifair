{{--
    Section A — the president's own performance/rating data (as an operator).
    Requires: $ownOperator (App\Models\Operator|null), $ownRecentRatings (collection).
--}}
@php
    $ownName = $ownOperator && $ownOperator->user ? $ownOperator->user->name : Auth::user()->name;
    $avg = $ownOperator ? round((float) $ownOperator->ratings()->isValid()->avg('rating'), 1) : 0;
    $total = $ownOperator ? $ownOperator->ratings()->isValid()->count() : 0;
    $complaints = $ownOperator ? $ownOperator->ratings()->isValid()->isComplaint()->count() : 0;
@endphp
<div class="tw-card overflow-hidden transition-shadow duration-200 hover:shadow-md">
    {{-- Header band — navy blue with gold accent --}}
    <div class="flex items-center justify-between px-4 py-3" style="background-image: linear-gradient(135deg, #0a1d33 0%, #0f2a4a 100%);">
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
