{{--
    Reusable complaint card. Requires:
    - $rating      App\Models\Rating  (must be eager-loaded with operator.user, proofs, response)
    - $routePrefix string             'superadmin' | 'tfrb-officer' | 'president'
    - $actor       string             (optional) 'president' on the president queue, 'staff' elsewhere
    Collapsed by default; clicking the header reveals the full detail grid.
    The Forward to TFRB action is only shown to the assigned TODA president
    (actor=president) while the case is still Pending/Reviewed and not yet
    forwarded — it is intentionally absent from staff cards.
--}}
@php
    $operator = $rating->operator;
    $operatorName = $operator->user->name ?? 'Unknown';
    $bodyNumber = $operator ? $operator->body_number : null;
    $severity = \App\Models\Rating::complaintSeverity($rating->complaint_type);

    $borderClass = [
        'danger' => 'border-l-red-500',
        'warning' => 'border-l-amber-400',
        'neutral' => 'border-l-slate-300',
    ][$severity];

    $typeChipClass = [
        'danger' => 'border-red-100 bg-red-50 text-red-600',
        'warning' => 'border-amber-100 bg-amber-50 text-amber-700',
        'neutral' => 'border-slate-200 bg-slate-100 text-slate-600',
    ][$severity];

    $typeIconClass = $severity === 'danger' ? 'text-red-500' : ($severity === 'warning' ? 'text-amber-500' : 'text-slate-400');
    $actor = $actor ?? (Auth::check() && Auth::user()->role === 'operator_president' ? 'president' : 'staff');
    $isPresidentView = $actor === 'president' || $routePrefix === 'president';

    // Who may act on this complaint right now — staff (tfrb-officer /
    // superadmin) and the assigned TODA president; everyone else gets a
    // read-only card with a badge explaining why. The president queue
    // passes $actionableComplaintIds (every case routed to them and still
    // open) and wins whenever present; otherwise fall back to the
    // routing service so staff cards get the same decision.
    $complaintRouting = app(\App\Services\ComplaintRoutingService::class);
    $actionableComplaintIds = $actionableComplaintIds ?? null;
    $canAct = $actionableComplaintIds !== null
        ? in_array((int) $rating->id, $actionableComplaintIds, true)
        : ($complaintRouting->actingAs(Auth::user(), $rating) !== null);
    $canAct = $canAct && !($readOnly ?? false);
    $assigneeBadge = $complaintRouting->assigneeBadge($rating);
    $assignmentNote = $complaintRouting->assignmentNote($rating);

    // Forward affordance: ONLY the assigned TODA president, and only while
    // the case is still Pending/Reviewed/Solved and not yet forwarded. A
    // solved case the president cannot fully resolve is escalated the same
    // way staff escalate a solved case: it goes straight back to the
    // TFRB / superadmin queue. Hidden on staff queues, on rejected cases,
    // and once forwarded.
    $showForwardButton = $isPresidentView
        && $canAct
        // Open is the current status label for an unresolved complaint. Keep
        // the legacy labels for older rows/views so the president's required
        // one-way escalation action remains available in every valid state.
        && in_array($rating->status_label, ['Open', 'Pending', 'Reviewed', 'Solved'], true)
        && empty($rating->forwarded_at);
    $isForwarded = !empty($rating->forwarded_at);
    $isForwardedByPresident = !empty($rating->forwarded_by);
    $appeals = $rating->passengerFollowUps->filter(fn ($followUp) => (bool) $followUp->is_appeal);

    // At-a-glance origin badge. Staff queues distinguish forwarded
    // escalations, direct-to-TFRB complaints and cases still with a
    // president; the president queue only ever shows its own cases (plus
    // the ones it already forwarded).
    $sourceBadge = null;
    if ($isForwarded) {
        $sourceBadge = [
            'label' => $isPresidentView ? 'Forwarded to TFRB' : 'Forwarded by President',
            'short' => $isPresidentView ? 'Forwarded' : 'Forwarded',
            'icon' => 'bi-send-fill',
            'class' => 'border-violet-200 bg-violet-50 text-violet-700',
            'title' => $rating->forwardedBy?->name
                ? 'Forwarded by ' . $rating->forwardedBy->name
                : 'Forwarded to the TFRB',
        ];
    } elseif (!$isPresidentView && $rating->routed_to === null) {
        $isAgainstPresident = ($rating->operator?->user?->role ?? null) === 'operator_president';
        $sourceBadge = [
            'label' => $isAgainstPresident ? 'Against President' : 'Unassigned',
            'short' => $isAgainstPresident ? 'vs President' : 'Unassigned',
            'icon' => $isAgainstPresident ? 'bi-person-x' : 'bi-person-dash',
            'class' => $isAgainstPresident ? 'border-red-200 bg-red-50 text-red-700' : 'border-slate-200 bg-slate-100 text-slate-600',
            'title' => $isAgainstPresident
                ? 'Complaint filed against a TODA president — handled by the TFRB / superadmin'
                : 'Complaint from an unassigned operator (no TODA president) — handled by the TFRB / superadmin',
        ];
    } elseif (!$isPresidentView) {
        $sourceBadge = [
            'label' => 'With ' . ($rating->routedToPresident?->name ?? 'TODA president'),
            'short' => 'With President',
            'icon' => 'bi-hourglass-split',
            'class' => 'border-amber-200 bg-amber-50 text-amber-700',
            'title' => 'Still assigned to the TODA president',
        ];
    }
@endphp

<div class="tw-card mb-3 overflow-hidden border-l-4 {{ $borderClass }}" data-complaint-card>
    <div class="flex cursor-pointer select-none items-center gap-3 px-4 py-3.5 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-navy-600/20 sm:px-5" data-complaint-toggle role="button" tabindex="0" aria-expanded="false" aria-controls="complaint-details-{{ $rating->id }}"
         onclick="var d=document.getElementById(this.getAttribute('aria-controls'));if(!d)return;var x=this.getAttribute('aria-expanded')==='true';this.setAttribute('aria-expanded',x?'false':'true');d.style.setProperty('display',x?'none':'block','important');var c=this.querySelector('[data-complaint-chevron]');if(c)c.classList.toggle('rotate-180',!x);"
         onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                <span class="truncate text-sm font-bold text-slate-800">{{ $operatorName }}</span>
                <span class="rounded-md border border-navy-600/10 bg-navy-600/5 px-1.5 py-0.5 font-mono text-[10px] font-bold text-navy-700">{{ $rating->reference_number }}</span>
                @if ($bodyNumber)
                    <span class="text-xs text-slate-400">B#{{ $bodyNumber }}</span>
                @endif
            </div>
            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                <span class="inline-flex items-center gap-1.5"><i class="bi bi-calendar3 text-slate-400"></i>{{ $rating->created_at->format('M d, Y \a\t h:i A') }}</span>
                <span class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-[10px] font-semibold {{ $typeChipClass }}">
                    <i class="bi bi-exclamation-triangle"></i>{{ $rating->complaint_type ?: 'Complaint' }}
                </span>
                @if ($sourceBadge)
                    <span class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-[10px] font-bold {{ $sourceBadge['class'] }}" title="{{ $sourceBadge['title'] }}">
                        <i class="bi {{ $sourceBadge['icon'] }}"></i>
                        <span class="hidden sm:inline">{{ $sourceBadge['label'] }}</span>
                        <span class="sm:hidden">{{ $sourceBadge['short'] }}</span>
                    </span>
                @endif
                @if ($rating->passengerFollowUps->count() > 0)
                    <span class="inline-flex items-center gap-1 rounded-md border border-violet-100 bg-violet-50 px-2 py-0.5 text-[10px] font-semibold text-violet-700" title="Passenger follow-up">
                        <i class="bi bi-chat-left-text"></i>{{ $rating->passengerFollowUps->count() }} follow-up{{ $rating->passengerFollowUps->count() > 1 ? 's' : '' }}
                    </span>
                @endif
                @if ($appeals->isNotEmpty())
                    <span class="inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-extrabold text-amber-700" title="Passenger appeal requires attention">
                        <i class="bi bi-megaphone-fill"></i>{{ $appeals->count() }} appeal{{ $appeals->count() > 1 ? 's' : '' }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            <div class="hidden items-center gap-2 sm:flex">
                <span class="tw-badge tw-badge-blue">{{ $rating->rating }} / 5</span>
                <span class="flex gap-0.5">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="bi {{ $i <= $rating->rating ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-200' }}"></i>
                    @endfor
                </span>
            </div>
            @if ($rating->status_label === 'Solved')
                <span class="tw-badge tw-badge-navy"><i class="bi bi-patch-check-fill"></i> Solved</span>
            @elseif ($rating->status_label === 'Rejected')
                <span class="tw-badge tw-badge-red"><i class="bi bi-x-circle-fill"></i> Rejected</span>
            @else
                <span class="tw-badge tw-badge-amber"><i class="bi bi-clock-fill"></i> Open</span>
            @endif
            <i class="bi bi-chevron-down text-slate-400 transition-transform duration-200" data-complaint-chevron></i>
        </div>
    </div>

    <div id="complaint-details-{{ $rating->id }}" class="border-t border-slate-100 bg-slate-50/40" style="display:none!important" data-complaint-details>
        @if ($isForwarded)
            {{-- Escalation trail: makes it obvious the case came from a TODA
                 president and that ownership now sits with the TFRB. --}}
            <div class="mx-4 mt-4 rounded-xl border border-violet-100 bg-violet-50/70 px-4 py-3 sm:mx-5">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <i class="bi bi-send-fill text-violet-600"></i>
                    <span class="text-[0.7rem] font-bold uppercase tracking-widest text-violet-700">Forwarded to TFRB</span>
                    <span class="ml-auto text-xs text-slate-500">{{ $rating->forwarded_at->format('M j, Y g:i A') }}</span>
                </div>
                <p class="mb-0 mt-1 text-xs leading-relaxed text-slate-600">
                    Escalated by <strong>{{ $rating->forwardedBy?->name ?? 'a TODA president' }}</strong>.
                    The TODA president can no longer act on this complaint — the TFRB / superadmin now owns it.
                </p>
                @if (!$isPresidentView)
                    <p class="mt-1 text-xs font-semibold leading-relaxed text-violet-800">
                        <i class="bi bi-arrow-right-circle mr-1"></i>Your next step: solve or reject this complaint.
                    </p>
                @endif
            </div>
        @endif
        <div class="grid gap-x-10 gap-y-5 p-4 sm:p-5 md:grid-cols-2">
            <div class="space-y-4">
                <div>
                    <div class="tw-stat-label mb-1.5"><i class="bi bi-exclamation-triangle mr-1 {{ $typeIconClass }}"></i>Complaint Type</div>
                    <div class="text-sm font-semibold text-slate-700">{{ $rating->complaint_type }}</div>
                    @if ($rating->complaint_details)
                        <p class="mt-1.5 rounded-lg border border-slate-100 bg-white px-3 py-2.5 text-sm leading-relaxed text-slate-600">"{{ $rating->complaint_details }}"</p>
                    @endif
                </div>
                <div>
                    <div class="tw-stat-label mb-1.5"><i class="bi bi-person mr-1 text-violet-500"></i>Passenger</div>
                    <div class="text-sm text-slate-700">
                        {{ $rating->passenger_name ?: 'Anonymous' }}
                        @if ($rating->passenger_contact)
                            &middot; <a href="tel:{{ $rating->passenger_contact }}" class="font-semibold text-navy-600 hover:underline">{{ $rating->passenger_contact }}</a>
                        @endif
                        @if ($rating->passenger_email)
                            &middot; <a href="mailto:{{ $rating->passenger_email }}" class="font-semibold text-navy-600 hover:underline">{{ $rating->passenger_email }}</a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <div class="tw-stat-label mb-1.5"><i class="bi bi-signpost-2 mr-1 text-blue-500"></i>Route</div>
                    @if ($rating->start_location || $rating->end_location)
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if ($rating->start_location)
                                <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    <i class="bi bi-circle-fill text-[0.55rem] text-emerald-600"></i> {{ $rating->start_location }}
                                </span>
                            @endif
                            @if ($rating->start_location && $rating->end_location)
                                <i class="bi bi-arrow-right text-[0.7rem] text-slate-300"></i>
                            @endif
                            @if ($rating->end_location)
                                <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    <i class="bi bi-circle-fill text-[0.55rem] text-red-600"></i> {{ $rating->end_location }}
                                </span>
                            @endif
                        </div>
                    @else
                        <div class="text-sm text-slate-400">No route data</div>
                    @endif
                </div>
                <div>
                    <div class="tw-stat-label mb-1.5"><i class="bi bi-paperclip mr-1 text-amber-500"></i>Evidence</div>
                    @if ($rating->proofs->count() > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach ($rating->proofs as $proof)
                                <a href="{{ URL::signedRoute('proof.serve', ['path' => $proof->file_path]) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg border-[1.5px] border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-600 transition hover:border-blue-300 hover:bg-blue-100">
                                    <i class="bi bi-{{ str_contains($proof->file_type ?? '', 'image') ? 'image' : (str_contains($proof->file_type ?? '', 'video') ? 'play-circle' : 'file-earmark') }}"></i>
                                    {{ $proof->original_name }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-slate-400">No evidence attached</div>
                    @endif
                </div>
            </div>
        </div>

        @if ($rating->response)
            <div class="mx-4 mb-4 rounded-xl border border-blue-100 bg-blue-50/60 px-4 py-3 sm:mx-5">
                <div class="mb-1.5 flex flex-wrap items-center gap-1.5">
                    <i class="bi bi-reply-fill text-navy-600"></i>
                    <span class="text-[0.7rem] font-bold uppercase tracking-widest text-navy-600">Operator's Response</span>
                    <span class="ml-auto text-xs text-slate-500">{{ $rating->response->created_at->diffForHumans() }}</span>
                </div>
                <p class="mb-0 text-sm leading-relaxed text-slate-700">{{ $rating->response->message }}</p>
                @if ($rating->operatorProofs->count() > 0)
                    <div class="mt-2.5 border-t border-navy-600/10 pt-2">
                        <small class="text-[0.65rem] font-bold uppercase tracking-widest text-navy-600"><i class="bi bi-paperclip mr-1"></i>Attached Proof</small>
                        <div class="mt-1.5 flex flex-wrap gap-2">
                            @foreach ($rating->operatorProofs as $proof)
                                <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg border-[1.5px] border-navy-200 bg-white px-3 py-1.5 text-xs font-semibold text-navy-600 transition hover:border-navy-300 hover:bg-navy-50">
                                    <i class="bi bi-{{ str_contains($proof->file_type ?? '', 'image') ? 'image' : 'file-earmark' }}"></i>
                                    {{ $proof->original_name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Chat thread with the filing passenger: their follow-ups plus any
             staff/president updates, with a composer only for whoever may act
             on the complaint. --}}
        <div class="mx-4 mb-4 sm:mx-5">
            @if ($canAct || ($readOnly ?? false))
                @include('partials.complaint-chat', [
                    'rating' => $rating,
                    'viewSide' => 'staff',
                    'composerAction' => route($routePrefix . '.complaints.reply', $rating),
                    'readOnly' => $readOnly ?? false,
                ])
            @else
                <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5">
                    <i class="bi bi-lock-fill mt-0.5 shrink-0 text-amber-600" aria-hidden="true"></i>
                    <span class="text-xs font-semibold leading-relaxed text-amber-800">{{ $assignmentNote }}</span>
                </div>
            @endif
        </div>

        {{-- Resolution details captured when the officer rejected or solved the
             complaint. Shown here so reviewers can see the recorded reason,
             any proof attached and who resolved it and when. --}}
        @if (in_array($rating->status_label, ['Rejected', 'Solved'], true))
            @php $isSolved = $rating->status_label === 'Solved'; @endphp
            <div class="mx-4 mb-4 rounded-xl border {{ $isSolved ? 'border-emerald-100 bg-emerald-50/60' : 'border-red-100 bg-red-50/60' }} px-4 py-3 sm:mx-5">
                <div class="mb-1.5 flex flex-wrap items-center gap-1.5">
                    <i class="bi {{ $isSolved ? 'bi-patch-check-fill text-emerald-600' : 'bi-x-circle-fill text-red-500' }}"></i>
                    <span class="text-[0.7rem] font-bold uppercase tracking-widest {{ $isSolved ? 'text-emerald-700' : 'text-red-600' }}">
                        {{ $isSolved ? 'Resolution Details' : 'Reason for Rejection' }}
                    </span>
                </div>
                @if ($rating->resolution_note)
                    <p class="mb-0 text-sm leading-relaxed text-slate-700">{{ $rating->resolution_note }}</p>
                @endif

                @if ($rating->hasEscalationChecklist())
                    <div class="mt-2.5 border-t border-slate-200 pt-2">
                        <small class="text-[0.65rem] font-bold uppercase tracking-widest text-slate-500"><i class="bi bi-list-check mr-1"></i>Panel Follow-up Checklist</small>
                        <ul class="mt-1.5 grid gap-1 sm:grid-cols-2">
                            @foreach ($rating->panelChecklistItems() as $checklistItem)
                                <li class="flex items-start gap-2 text-xs leading-relaxed {{ $checklistItem['checked'] ? 'text-slate-700' : 'text-slate-400' }}">
                                    <i class="bi {{ $checklistItem['checked'] ? 'bi-check2-circle-fill text-emerald-600' : 'bi-circle text-slate-300' }} mt-0.5 shrink-0"></i>
                                    <span>{{ $checklistItem['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($rating->resolutionProofs->count() > 0)
                    <div class="mt-2.5 border-t border-slate-200 pt-2">
                        <small class="text-[0.65rem] font-bold uppercase tracking-widest text-slate-500"><i class="bi bi-paperclip mr-1"></i>Resolution Proof</small>
                        <div class="mt-1.5 flex flex-wrap gap-2">
                            @foreach ($rating->resolutionProofs as $proof)
                                <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg border-[1.5px] border-emerald-200 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-50">
                                    <i class="bi bi-{{ str_contains($proof->file_type ?? '', 'image') ? 'image' : (str_contains($proof->file_type ?? '', 'video') ? 'play-circle' : 'file-earmark') }}"></i>
                                    {{ $proof->original_name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-slate-200 pt-2 text-[0.7rem] text-slate-500">
                    @if ($rating->reviewed_by_name)
                        <span><i class="bi bi-person-badge mr-1"></i>Reviewed by {{ $rating->reviewed_by_name }}</span>
                    @endif
                    @if ($rating->resolution_at)
                        <span><i class="bi bi-clock-history mr-1"></i>{{ $rating->resolution_at->format('M j, Y g:i A') }}</span>
                    @endif
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-white/80 px-4 py-3.5 sm:px-5">
            @if ($rating->response)
                <span class="text-xs font-medium text-emerald-600"><i class="bi bi-check2-all mr-1"></i>Operator responded</span>
            @else
                <span class="text-xs font-medium text-slate-400"><i class="bi bi-hourglass mr-1"></i>No response yet</span>
            @endif
            @if ($canAct)
                {{-- Contextual next step so staff always know what to do with
                     an actionable complaint: solve it or reject it. Solving
                     marks the complaint reviewed; rejecting closes it. --}}
                @if ($actor !== 'president')
                    <div class="w-full rounded-lg border border-navy-100 bg-navy-50 px-3 py-2 text-[11px] font-semibold leading-snug text-navy-700">
                        <i class="bi bi-arrow-right-circle mr-1"></i>@if ($rating->status_label === 'Pending')
                            Next step: solve or reject this complaint — solving marks it reviewed and counts toward the operator's rating; rejecting closes it with a reason.
                        @else
                            Next step: final decision — solve or reject this complaint.
                        @endif
                    </div>
                @endif
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    @if (in_array($rating->status_label, ['Open'], true))
                        {{-- An incoming complaint only offers the two final
                             decisions. Solving also marks it reviewed (so it
                             counts towards the rating); rejecting closes it
                             without affecting the score. The separate Review
                             endpoint is kept for legacy data only. --}}
                        <button type="button" class="tw-btn tw-btn-sm tw-btn-navy" title="Mark complaint as solved"
                                data-complaint-resolution data-mode="solve"
                                data-action="{{ route($routePrefix . '.complaints.solve', $rating) }}"
                                data-ref="{{ $rating->reference_number }}">
                            <i class="bi bi-patch-check"></i>Solved
                        </button>
                        <button type="button" class="tw-btn tw-btn-sm tw-btn-outline" title="Reject complaint"
                                data-complaint-resolution data-mode="reject"
                                data-action="{{ route($routePrefix . '.complaints.reject', $rating) }}"
                                data-ref="{{ $rating->reference_number }}">
                            <i class="bi bi-x-lg"></i>Reject
                        </button>
                    @elseif ($rating->status_label === 'Rejected')
                        <form action="{{ route($routePrefix . '.complaints.reset', $rating) }}" method="POST" onsubmit="return confirm('Undo rejection and return this complaint to the pending queue?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline" title="Undo rejection">
                                <i class="bi bi-arrow-counterclockwise"></i>Undo Reject
                            </button>
                        </form>
                    @elseif ($rating->status_label === 'Solved')
                        {{-- Solved but the passenger is not satisfied? The case
                             leaves the terminal Solved state and re-enters the
                             actionable queue for a heavier TFRB / superadmin
                             re-review instead of staying closed for good. Staff
                             own that escalate path; the president reaches the
                             same outcome through the one-way Forward action. --}}
                        @if ($actor !== 'president')
                            <div class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                                <div class="mb-2 flex items-center gap-1.5 text-[0.65rem] font-bold uppercase tracking-widest text-slate-500">
                                    <i class="bi bi-list-check"></i>Panel follow-up questions (optional)
                                </div>
                                <form action="{{ route($routePrefix . '.complaints.escalate', $rating) }}" method="POST" onsubmit="return confirm('Escalate this complaint to the TFRB/superadmin for a heavier re-review? The solved state will be reset and the case will return to the actionable queue.')">
                                    @csrf
                                    @method('PATCH')
                                    <div class="grid gap-1 sm:grid-cols-2">
                                        @foreach (\App\Models\Rating::PANEL_ESCALATION_CHECKLIST as $checklistItem)
                                            <label class="flex cursor-pointer items-start gap-2 rounded-md px-2 py-1 transition hover:bg-white">
                                                <input type="checkbox" name="escalation_checklist[]" value="{{ $checklistItem['key'] }}" class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-navy-600 focus:ring-navy-500">
                                                <span class="text-xs leading-snug text-slate-700">{{ $checklistItem['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <div class="mt-2.5 flex items-center justify-end">
                                        <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline-danger" title="Escalate to the TFRB/superadmin for a heavier re-review">
                                            <i class="bi bi-arrow-up-right-dots"></i>Escalate to Superadmin
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                        <form action="{{ route($routePrefix . '.complaints.reopen', $rating) }}" method="POST" onsubmit="return confirm('Reopen this complaint?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline" title="Reopen complaint">
                                <i class="bi bi-arrow-counterclockwise"></i>Reopen
                            </button>
                        </form>
                    @endif

                    @if ($showForwardButton)
                        <span class="mx-0.5 hidden h-5 w-px bg-slate-200 sm:block" aria-hidden="true"></span>
                        <div class="w-full rounded-lg border border-violet-200 bg-violet-50/60 px-3 py-3">
                            <div class="mb-2 flex items-center gap-1.5 text-[0.65rem] font-bold uppercase tracking-widest text-violet-700">
                                <i class="bi bi-list-check"></i>Panel follow-up questions (optional)
                            </div>
                            <form action="{{ route($routePrefix . '.complaints.forward', $rating) }}" method="POST" onsubmit="return confirm('Forward this complaint to the TFRB/superadmin? They will take over completely — you can no longer act on it. This cannot be undone.')">
                                @csrf
                                @method('PATCH')
                                <div class="grid gap-1 sm:grid-cols-2">
                                    @foreach (\App\Models\Rating::PANEL_ESCALATION_CHECKLIST as $checklistItem)
                                        <label class="flex cursor-pointer items-start gap-2 rounded-md px-2 py-1 transition hover:bg-white">
                                            <input type="checkbox" name="escalation_checklist[]" value="{{ $checklistItem['key'] }}" class="mt-0.5 h-4 w-4 shrink-0 rounded border-violet-300 text-violet-600 focus:ring-violet-500">
                                            <span class="text-xs leading-snug text-violet-800">{{ $checklistItem['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="mt-2.5 flex items-center justify-end">
                                    <button type="submit" class="tw-btn tw-btn-sm border border-violet-300 bg-violet-50 font-semibold text-violet-700 transition hover:border-violet-400 hover:bg-violet-100" title="Forward to TFRB/superadmin (one-way)">
                                        <i class="bi bi-send-fill"></i>Forward to TFRB
                                    </button>
                                </div>
                            </form>
                        </div>
                    @elseif ($isForwarded && $isPresidentView)
                        <span class="tw-badge tw-badge-blue"><i class="bi bi-send-fill"></i> Forwarded to TFRB</span>
                    @endif

                    @if (in_array($rating->status_label, ['Pending', 'Rejected'], true) && $routePrefix !== 'president')
                        <form action="{{ route($routePrefix . '.complaints.destroy', $rating) }}" method="POST" onsubmit="return confirm('Delete this complaint?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline-danger" title="Delete complaint">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    @endif
                </div>
            @else
                <div class="flex shrink-0 items-center">
                    <span class="tw-badge {{ $assigneeBadge['class'] }}"><i class="bi {{ $assigneeBadge['icon'] }}"></i> {{ $assigneeBadge['label'] }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
