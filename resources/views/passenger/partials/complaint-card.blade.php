@php
    $operator = $rating->operator;
    $operatorName = $operator->user->name ?? 'Unknown';
    $bodyNumber = $operator ? $operator->body_number : null;
    $followUpOpen = $errors->has('message') && old('follow_up_rating_id') == $rating->id;
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

        @if ($rating->passengerFollowUps->isNotEmpty())
            <div class="mt-3 rounded-xl border border-violet-100 bg-violet-50/60 px-4 py-3">
                <div class="mb-1.5 flex items-center gap-1.5">
                    <i class="bi bi-chat-left-text-fill text-violet-600"></i>
                    <span class="text-[0.7rem] font-bold uppercase tracking-widest text-violet-700">
                        Your follow-ups ({{ $rating->passengerFollowUps->count() }})
                    </span>
                </div>
                <div class="space-y-2">
                    @foreach ($rating->passengerFollowUps as $followUp)
                        <div class="border-t border-violet-100 pt-2 first:border-t-0 first:pt-0">
                            <p class="text-sm leading-relaxed text-slate-700">{{ $followUp->message }}</p>
                            <div class="mt-0.5 text-[0.68rem] text-slate-400">
                                {{ $followUp->created_at->format('M d, Y h:i A') }}
                                <span class="ml-1 rounded bg-violet-100 px-1.5 py-0.5 font-semibold text-violet-700">Sent</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($rating->is_solved)
            <p class="mt-3 flex items-center gap-1.5 text-xs text-slate-400">
                <i class="bi bi-check2-all text-emerald-500"></i>
                This complaint has been resolved — you can no longer follow up.
            </p>
        @else
            {{-- One collapsed follow-up action per card — the form only
                 appears after the passenger asks for it. --}}
            <div class="mt-3 border-t border-slate-100 pt-3">
                <button type="button"
                        onclick="toggleFollowUp({{ $rating->id }})"
                        aria-expanded="{{ $followUpOpen ? 'true' : 'false' }}"
                        aria-controls="followup-panel-{{ $rating->id }}"
                        class="tw-btn tw-btn-sm tw-btn-outline">
                    <i class="bi bi-chat-left-text text-violet-500"></i> Follow up this complaint
                </button>
                <div id="followup-panel-{{ $rating->id }}" class="{{ $followUpOpen ? '' : 'hidden' }} mt-3">
                    <form action="{{ route('passenger.complaints.followUp', $rating) }}" method="POST">
                        @csrf
                        <input type="hidden" name="follow_up_rating_id" value="{{ $rating->id }}">
                        <label for="followup-{{ $rating->id }}" class="tw-label mb-1">
                            <i class="bi bi-send-fill mr-1 text-violet-500"></i>Follow up this complaint
                        </label>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                            <textarea id="followup-{{ $rating->id }}" name="message" rows="2" maxlength="1000"
                                      class="tw-textarea flex-1"
                                      placeholder="Add details or request a status update...">{{ old('follow_up_rating_id') == $rating->id ? old('message') : '' }}</textarea>
                            <div class="flex shrink-0 gap-2">
                                <button type="submit" class="tw-btn tw-btn-sm tw-btn-navy">
                                    <i class="bi bi-send"></i> Send
                                </button>
                                <button type="button" onclick="toggleFollowUp({{ $rating->id }})" class="tw-btn tw-btn-sm tw-btn-ghost">
                                    Cancel
                                </button>
                            </div>
                        </div>
                        @if ($errors->has('message') && old('follow_up_rating_id') == $rating->id)
                            <span class="tw-error-text" role="alert">{{ $errors->first('message') }}</span>
                        @endif
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
