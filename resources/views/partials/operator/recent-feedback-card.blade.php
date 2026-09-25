{{--
    RecentFeedbackCard component — latest ratings for the operator dashboard.
    Requires: $recentRatings (collection of Rating with proofs, response).
--}}
<div class="op-card">
    <div class="op-card-head">
        <h3 class="op-card-title"><i class="bi bi-chat-square-text mr-1.5 text-navy-600"></i> Recent feedback</h3>
        <a href="{{ route('operator.ratings') }}" class="text-xs font-semibold text-navy-600 hover:underline">View all <i class="bi bi-arrow-right"></i></a>
    </div>

    @forelse ($recentRatings as $rating)
        @php
            $r = $rating->rating;
            if ($r >= 4) { $cClass = 'bg-blue-50 text-navy-700'; }
            elseif ($r <= 2) { $cClass = 'bg-amber-600 text-white'; }
            else { $cClass = 'bg-gold-50 text-gold-700'; }
        @endphp
        <div class="flex items-start gap-3 border-t border-slate-100 py-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-base font-extrabold {{ $cClass }}">
                {{ $rating->rating }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-1">
                    <div class="flex gap-px">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= $r ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-300' }} text-xs"></i>
                        @endfor
                    </div>
                    @if ($rating->complaint_type)
                        <span class="tw-badge tw-badge-red">
                            <i class="bi bi-exclamation-triangle"></i>{{ $rating->complaint_type }}
                        </span>
                    @endif
                    <span class="ml-auto text-[0.7rem] text-slate-400">{{ $rating->created_at->diffForHumans() }}</span>
                </div>
                @if ($rating->reason)
                    <p class="mt-1 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($rating->reason, 90) }}</p>
                @elseif ($rating->complaint_details)
                    <p class="mt-1 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($rating->complaint_details, 90) }}</p>
                @elseif ($rating->start_location || $rating->end_location)
                    <p class="mt-1 text-sm text-slate-500">{{ $rating->start_location }} → {{ $rating->end_location }}</p>
                @endif
                @if ($rating->response)
                    <p class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600">
                        <i class="bi bi-check-circle-fill"></i> Responded
                    </p>
                @elseif ($r <= 2)
                    <p class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-amber-600">
                        <i class="bi bi-exclamation-circle"></i> Needs your response
                    </p>
                @endif
            </div>
        </div>
    @empty
        <div class="tw-empty py-8 text-center">
            <div class="tw-empty-icon"><i class="bi bi-chat-square"></i></div>
            <p class="mt-2 text-sm text-slate-500">No ratings yet. Passengers will appear here after scanning your QR code.</p>
        </div>
    @endforelse
</div>