@php
    $operator = $rating->operator;
    $operatorName = $operator->user->name ?? 'Unknown';
    $bodyNumber = $operator ? $operator->body_number : null;
@endphp
<div class="tw-card mb-3.5 overflow-hidden border-l-4 {{ $statusBorder }}">
    <div class="px-4 py-4 sm:px-5">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[0.7rem] font-semibold text-navy-600">{{ $rating->reference_number }}</span>
            @if ($bodyNumber)
                <span class="text-xs text-slate-400">B#{{ $bodyNumber }} — {{ $operatorName }}</span>
            @else
                <span class="text-xs text-slate-400">{{ $operatorName }}</span>
            @endif
            <span class="ml-auto inline-flex items-center gap-1 rounded-md border border-amber-100 bg-amber-50 px-2 py-0.5 text-[0.7rem] font-semibold text-amber-700">
                <i class="bi bi-exclamation-triangle"></i>{{ $rating->complaint_type }}
            </span>
        </div>

        <div class="mt-1.5 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-slate-400">
            <span><i class="bi bi-calendar3 mr-1" aria-hidden="true"></i>Filed {{ $rating->created_at->format('M d, Y h:i A') }}</span>
            @if ($rating->solved_at)
                <span><i class="bi bi-check2-all mr-1" aria-hidden="true"></i>Resolved {{ $rating->solved_at->diffForHumans() }}</span>
            @endif
        </div>

        @if ($rating->complaint_details)
            <p class="mt-3 text-sm italic leading-relaxed text-slate-500">"{{ $rating->complaint_details }}"</p>
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
            ])
        @endif
    </div>
</div>
