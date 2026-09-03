{{--
    Member detail modal body — shows one member's ratings/complaints history.
    Requires: $member (Operator with user), $ratings (paginator).
--}}
<div class="tw-modal-head">
    <div class="flex items-center gap-3">
        <div class="tw-avatar tw-avatar-md bg-gold text-navy-800">{{ strtoupper(substr($member->user->name, 0, 1)) }}</div>
        <div>
            <h5 class="text-base font-bold text-slate-900">{{ $member->user->name }}</h5>
            <p class="text-xs text-slate-500">Body #{{ $member->body_number }} · {{ $member->plate_number }}</p>
        </div>
    </div>
    <button type="button" class="tw-modal-close" data-tw-modal-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
</div>

<div class="tw-modal-body">
    @php
        $mAvg = $member->ratings()->isValid()->avg('rating');
        $mTotal = $member->ratings()->isValid()->count();
        $mComplaints = $member->ratings()->isValid()->isComplaint()->count();
    @endphp
    <div class="mb-4 flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
        <div>
            <div class="text-2xl font-extrabold text-slate-900">{{ $mAvg ? number_format($mAvg, 1) : '0.0' }}</div>
            <div class="mt-0.5 flex gap-0.5">
                @for ($i = 1; $i <= 5; $i++)
                    <i class="bi {{ $i <= round((float) $mAvg) ? 'bi-star-fill text-gold' : 'bi-star text-slate-300' }}"></i>
                @endfor
            </div>
        </div>
        <div class="flex gap-4 text-center">
            <div>
                <div class="text-xl font-bold text-slate-800">{{ $mTotal }}</div>
                <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Ratings</div>
            </div>
            <div>
                <div class="text-xl font-bold text-red-500">{{ $mComplaints }}</div>
                <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Complaints</div>
            </div>
        </div>
    </div>

    <div class="space-y-2">
        @forelse ($ratings as $rating)
            <div class="rounded-xl border border-slate-100 p-3">
                <div class="flex items-center gap-1.5">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="bi {{ $i <= $rating->rating ? 'bi-star-fill text-gold' : 'bi-star text-slate-200' }} text-sm"></i>
                    @endfor
                    @if (!empty($rating->complaint_type))
                        <span class="tw-badge tw-badge-red ml-1"><i class="bi bi-exclamation-triangle-fill"></i> {{ $rating->complaint_type }}</span>
                    @endif
                    <span class="ml-auto text-[11px] text-slate-400">{{ $rating->created_at->format('M d, Y') }}</span>
                </div>
                <p class="mt-1.5 text-sm text-slate-600">{{ $rating->complaint_details ?? $rating->reason ?? $rating->start_location . ' → ' . $rating->end_location }}</p>
                @if ($rating->response)
                    <p class="mt-2 rounded-lg bg-navy-50/50 p-2 text-xs text-slate-500"><i class="bi bi-reply-fill mr-1"></i> {{ $rating->response->message }}</p>
                @endif
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <div class="tw-empty-icon"><i class="bi bi-star"></i></div>
                <h4 class="text-sm font-bold text-slate-700">No ratings yet</h4>
                <p class="mt-1 text-sm text-slate-400">This member has no ratings on record.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $ratings->links('pagination::tailwind') }}
    </div>
</div>
