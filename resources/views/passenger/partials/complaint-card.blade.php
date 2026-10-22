@php
    $operator = $rating->operator;
    $operatorName = $operator->user->name ?? 'Unknown';
    $bodyNumber = $operator ? $operator->body_number : null;

    // Same precedence as the model's status_label: solved wins, then
    // accepted, rejected, and finally pending.
    if ($rating->is_solved) {
        $statusKey = 'solved';
    } elseif ($rating->is_accepted === true) {
        $statusKey = 'accepted';
    } elseif ($rating->is_accepted === false) {
        $statusKey = 'rejected';
    } else {
        $statusKey = 'pending';
    }
    $statusMap = [
        'pending' => ['label' => 'Pending', 'icon' => 'bi-clock-fill', 'badge' => 'tw-badge tw-badge-amber', 'border' => 'border-l-amber-400', 'avatar' => 'bg-amber-500/10 text-amber-600'],
        'accepted' => ['label' => 'Accepted', 'icon' => 'bi-check-circle-fill', 'badge' => 'tw-badge tw-badge-green', 'border' => 'border-l-emerald-500', 'avatar' => 'bg-emerald-500/10 text-emerald-600'],
        'rejected' => ['label' => 'Rejected', 'icon' => 'bi-x-circle-fill', 'badge' => 'tw-badge tw-badge-red', 'border' => 'border-l-red-500', 'avatar' => 'bg-red-500/10 text-red-600'],
        'solved' => ['label' => 'Solved', 'icon' => 'bi-patch-check-fill', 'badge' => 'tw-badge tw-badge-navy', 'border' => 'border-l-navy-600', 'avatar' => 'bg-navy-600/10 text-navy-700'],
    ];
    $status = $statusMap[$statusKey];

    // A failed follow-up submission reopens this card and its chat thread.
    $detailsOpen = $errors->has('message') && old('follow_up_rating_id') == $rating->id;
@endphp
<div class="tw-card mb-3.5 overflow-hidden border-l-4 {{ $status['border'] }}">
    <button type="button"
            onclick="var b=this.parentElement.querySelector('[data-complaint-details]');var o=b.classList.toggle('hidden')===false;this.setAttribute('aria-expanded',o?'true':'false');var c=this.querySelector('[data-complaint-chevron]');if(c)c.classList.toggle('rotate-180',o);"
            aria-expanded="{{ $detailsOpen ? 'true' : 'false' }}"
            class="flex w-full items-start gap-3 px-4 py-3.5 text-left transition-colors hover:bg-slate-50 sm:px-5">
        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $status['avatar'] }}" aria-hidden="true">
            <i class="bi {{ $status['icon'] }}"></i>
        </span>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                <span class="rounded-md border border-navy-600/10 bg-navy-600/5 px-1.5 py-0.5 font-mono text-[10px] font-bold text-navy-700">{{ $rating->reference_number }}</span>
                <span class="truncate text-sm font-bold text-slate-800">{{ $operatorName }}</span>
                @if ($bodyNumber)
                    <span class="text-xs text-slate-400">B#{{ $bodyNumber }}</span>
                @endif
            </div>
            <div class="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-slate-400">
                <span class="{{ $status['badge'] }}"><i class="bi {{ $status['icon'] }}"></i> {{ $status['label'] }}</span>
                <span><i class="bi bi-calendar3 mr-1" aria-hidden="true"></i>Filed {{ $rating->created_at->format('M d, Y') }}</span>
                @if ($rating->solved_at)
                    <span><i class="bi bi-check2-all mr-1" aria-hidden="true"></i>Resolved {{ $rating->solved_at->diffForHumans() }}</span>
                @endif
                <span class="inline-flex items-center gap-1 rounded-md border border-amber-100 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                    <i class="bi bi-exclamation-triangle"></i>{{ $rating->complaint_type }}
                </span>
                @if ($rating->passengerFollowUps->count() > 0)
                    <span class="inline-flex items-center gap-1 rounded-md border border-violet-100 bg-violet-50 px-2 py-0.5 text-[10px] font-semibold text-violet-700">
                        <i class="bi bi-chat-left-text"></i>{{ $rating->passengerFollowUps->count() }} message{{ $rating->passengerFollowUps->count() > 1 ? 's' : '' }}
                    </span>
                @endif
            </div>
        </div>

        <i data-complaint-chevron class="bi bi-chevron-down mt-2 shrink-0 text-slate-400 transition-transform duration-200 {{ $detailsOpen ? 'rotate-180' : '' }}" aria-hidden="true"></i>
    </button>

    <div data-complaint-details class="{{ $detailsOpen ? '' : 'hidden' }} border-t border-slate-100 bg-slate-50/40 px-4 py-4 sm:px-5">
        @if ($rating->complaint_details)
            <p class="text-sm italic leading-relaxed text-slate-500">"{{ $rating->complaint_details }}"</p>
        @endif

        @if ($rating->proofs->count() > 0)
            <div class="mt-3">
                <div class="mb-1 text-[0.7rem] font-bold uppercase tracking-widest text-slate-400">Attached evidence ({{ $rating->proofs->count() }} file(s))</div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($rating->proofs as $proof)
                        @if (str_starts_with($proof->file_type, 'image'))
                            <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}">
                                <img src="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                     alt="{{ $proof->original_name }}"
                                     class="h-16 w-16 rounded-lg border border-slate-200 object-cover">
                            </a>
                        @else
                            <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                               class="tw-btn tw-btn-sm tw-btn-outline">
                                <i class="bi bi-file-earmark"></i> {{ $proof->original_name }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        @if ($rating->response)
            <div class="mt-3 rounded-xl bg-navy-600/10 px-4 py-3">
                <div class="mb-1 flex items-center gap-1.5">
                    <i class="bi bi-reply-fill text-navy-600"></i>
                    <span class="text-[0.7rem] font-bold uppercase tracking-widest text-navy-600">Operator's response</span>
                </div>
                <p class="mb-0 text-sm text-slate-700">{{ $rating->response->message }}</p>
            </div>
        @elseif ($rating->is_accepted === true)
            <p class="mt-3 text-xs text-slate-400"><i class="bi bi-hourglass mr-1"></i>No response from the operator yet.</p>
        @endif

        @if ($rating->is_accepted === false)
            <div class="mt-3 rounded-xl border border-red-100 bg-red-50 px-4 py-3">
                <div class="mb-1 flex items-center gap-1.5">
                    <i class="bi bi-x-circle-fill text-red-500"></i>
                    <span class="text-[0.7rem] font-bold uppercase tracking-widest text-red-600">Reason for rejection</span>
                </div>
                @if ($rating->resolution_note)
                    <p class="mb-0 text-sm text-slate-700">{{ $rating->resolution_note }}</p>
                @endif
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-red-100 pt-2 text-[0.7rem] text-slate-500">
                    @if ($rating->reviewed_by_name)
                        <span><i class="bi bi-person-badge mr-1"></i>Reviewed by {{ $rating->reviewed_by_name }}</span>
                    @endif
                    @if ($rating->resolution_at)
                        <span><i class="bi bi-clock-history mr-1"></i>{{ $rating->resolution_at->format('M j, Y g:i A') }}</span>
                    @endif
                </div>
            </div>
        @elseif ($rating->is_solved)
            <div class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                <div class="mb-1 flex items-center gap-1.5">
                    <i class="bi bi-patch-check-fill text-emerald-600"></i>
                    <span class="text-[0.7rem] font-bold uppercase tracking-widest text-emerald-700">Resolution details</span>
                </div>
                @if ($rating->resolution_note)
                    <p class="mb-0 text-sm text-slate-700">{{ $rating->resolution_note }}</p>
                @endif
                @if ($rating->resolutionProofs->count() > 0)
                    <div class="mt-2.5 border-t border-emerald-100 pt-2">
                        <div class="mb-1.5 text-[0.7rem] font-bold uppercase tracking-widest text-slate-400">Resolution proof ({{ $rating->resolutionProofs->count() }} file(s))</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($rating->resolutionProofs as $proof)
                                @if (str_starts_with((string) $proof->file_type, 'image'))
                                    <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}">
                                        <img src="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                             alt="{{ $proof->original_name }}"
                                             class="h-16 w-16 rounded-lg border border-slate-200 object-cover">
                                    </a>
                                @else
                                    <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                       class="tw-btn tw-btn-sm tw-btn-outline">
                                        <i class="bi bi-file-earmark"></i> {{ $proof->original_name }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-emerald-100 pt-2 text-[0.7rem] text-slate-500">
                    @if ($rating->reviewed_by_name)
                        <span><i class="bi bi-person-badge mr-1"></i>Reviewed by {{ $rating->reviewed_by_name }}</span>
                    @endif
                    @if ($rating->resolution_at)
                        <span><i class="bi bi-clock-history mr-1"></i>{{ $rating->resolution_at->format('M j, Y g:i A') }}</span>
                    @endif
                </div>
            </div>
        @endif

        {{-- Chat with the TFRB/superadmin on this complaint — the thread stays
             readable after solving, but the composer is withdrawn. --}}
        @if ($rating->is_solved && $rating->passengerFollowUps->isEmpty())
            <p class="mt-3 flex items-center gap-1.5 text-xs text-slate-400">
                <i class="bi bi-check2-all text-emerald-500"></i>
                This complaint has been resolved — you can no longer follow up.
            </p>
        @else
            @include('partials.complaint-chat', [
                'rating' => $rating,
                'viewSide' => 'passenger',
                'composerAction' => route('passenger.complaints.followUp', $rating),
                'chatOpen' => $detailsOpen,
            ])
        @endif
    </div>
</div>
