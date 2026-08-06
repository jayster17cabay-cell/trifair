@if ($totalRatings > 0)
    @foreach (range(5, 1) as $star)
        @php
            $count = $ratingCounts[$star] ?? 0;
            $percent = $totalRatings > 0 ? ($count / $totalRatings) * 100 : 0;
        @endphp
        <div class="mb-3 flex items-center gap-3">
            <span class="w-8 shrink-0 text-sm font-semibold text-slate-600">{{ $star }} <i class="bi bi-star-fill" style="font-size: 0.65rem; color: var(--secondary);"></i></span>
            <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-gradient-to-r from-gold to-gold-dark" role="progressbar" style="width: {{ $percent }}%;"></div>
            </div>
            <span class="w-6 shrink-0 text-right text-sm font-semibold text-slate-600">{{ $count }}</span>
        </div>
    @endforeach
    <div class="mt-4 border-t border-slate-100 pt-3">
        <a href="{{ route('operator.ratings') }}" class="tw-btn tw-btn-sm tw-btn-outline w-full">
            See All Ratings <i class="bi bi-arrow-right"></i>
        </a>
    </div>
@else
    <div class="tw-empty">
        <div class="tw-empty-icon"><i class="bi bi-inbox"></i></div>
        <p class="text-sm text-slate-500">No ratings yet. Share your QR code!</p>
    </div>
@endif
