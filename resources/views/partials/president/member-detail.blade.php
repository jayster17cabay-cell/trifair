{{--
    President member-detail modal (opened from the TODA members list). Ratings
    are shown first; suspension status/controls and history sit at the bottom.
    Requires: $member (App\Models\Operator), $ratings (paginator), $stats,
    $currentSuspension, $suspensionHistory.
--}}
<div class="tw-modal-head">
    <div class="flex items-center gap-3">
        <div class="tw-avatar tw-avatar-sm bg-navy-700">{{ strtoupper(substr($member->user->name, 0, 1)) }}</div>
        <div class="min-w-0">
            <div class="truncate text-base font-bold text-slate-900">{{ $member->user->name }}</div>
            <div class="truncate text-xs text-slate-500">Body #{{ $member->body_number }} · {{ $member->plate_number }}</div>
        </div>
    </div>
    <button type="button" class="tw-modal-close" data-tw-modal-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
</div>

<div class="tw-modal-body">
    @php
        $mAvg = $stats && $stats->total > 0 ? (float) $stats->avg : 0;
        $mTotal = $stats ? (int) $stats->total : 0;
        $mComplaints = $stats ? (int) $stats->complaints : 0;
        $activeSuspension = ($currentSuspension ?? null) && !$currentSuspension->isExpired() ? $currentSuspension : null;
        $suspendPayload = ['name' => $member->user->name, 'suspendUrl' => route('president.members.suspend', $member->id)];
    @endphp

    {{-- Rating summary --}}
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
                <div class="text-xl font-bold text-slate-900">{{ $mTotal }}</div>
                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Ratings</div>
            </div>
            <div>
                <div class="text-xl font-bold text-red-500">{{ $mComplaints }}</div>
                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Complaints</div>
            </div>
        </div>
    </div>

    {{-- Ratings list --}}
    <div class="space-y-3">
        @forelse ($ratings as $rating)
            <div class="rounded-xl border border-slate-100 p-3">
                <div class="flex items-center gap-1.5">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="bi {{ $i <= $rating->rating ? 'bi-star-fill text-gold' : 'bi-star text-slate-300' }} text-sm"></i>
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

    @if ($ratings->hasPages())
        <div class="mt-3" data-president-pagination>
            {{ $ratings->links('pagination::tailwind') }}
        </div>
    @endif

    {{-- Suspension status + controls (president-only, own TODA members) --}}
    <div class="mt-4 border-t border-slate-100 pt-4">
        @if (($suspensionHistory ?? collect())->isNotEmpty())
            <details class="mb-2 rounded-xl border border-slate-200">
                <summary class="flex cursor-pointer list-none items-center gap-2 px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <i class="bi bi-clock-history"></i> Suspension history ({{ $suspensionHistory->count() }})
                </summary>
                <ul class="divide-y divide-slate-100 border-t border-slate-100">
                    @foreach ($suspensionHistory as $suspension)
                        <li class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($suspension->ended_at)
                                    <span class="tw-badge tw-badge-gray">Ended</span>
                                @elseif ($suspension->isExpired())
                                    <span class="tw-badge tw-badge-gray">Lapsed</span>
                                @else
                                    <span class="tw-badge tw-badge-red">Active</span>
                                @endif
                                <span class="text-xs text-slate-500">{{ $suspension->suspended_at->format('M d, Y') }} – {{ $suspension->suspended_until->format('M d, Y') }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ $suspension->reason }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">
                                @if ($suspension->suspender) By {{ $suspension->suspender->name }} @endif
                                @if ($suspension->ended_at)
                                    · lifted {{ $suspension->ended_at->format('M d, Y') }}
                                    @if ($suspension->ender) by {{ $suspension->ender->name }} @else (automatic) @endif
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif

        @if ($activeSuspension)
            <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="flex items-start gap-3">
                    <i class="bi bi-slash-circle-fill text-lg text-red-500"></i>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="tw-badge tw-badge-red">Suspended</span>
                            <span class="text-xs font-medium text-slate-500">until {{ $activeSuspension->suspended_until->format('M d, Y') }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-700">{{ $activeSuspension->reason }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $activeSuspension->suspended_at->format('M d, Y') }} – {{ $activeSuspension->suspended_until->format('M d, Y') }}
                            @if ($activeSuspension->suspender) · by {{ $activeSuspension->suspender->name }} @endif
                        </p>
                        <form method="POST" action="{{ route('president.members.suspend.end', $member->id) }}" class="mt-3">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline-danger">
                                <i class="bi bi-play-circle"></i>End Suspension Early
                            </button>
                        </form>

                        {{-- Report a violation (still observed operating while suspended) --}}
                        <details class="mt-3 rounded-xl border border-red-200 bg-white" data-violation-report>
                            <summary class="flex cursor-pointer list-none items-center gap-2 px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-red-600">
                                <i class="bi bi-exclamation-octagon"></i> Report Violation (still operating)
                            </summary>
                            <div class="border-t border-red-100 p-4">
                                <form method="POST" action="{{ route('president.members.violation', $member->id) }}" enctype="multipart/form-data" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label for="violation_type_{{ $member->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Violation type</label>
                                        <select id="violation_type_{{ $member->id }}" name="violation_type" required class="tw-select">
                                            <option value="Operating while suspended">Operating while suspended</option>
                                            <option value="Disregarding suspension order">Disregarding suspension order</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="incident_at_{{ $member->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Incident date &amp; time</label>
                                        <input id="incident_at_{{ $member->id }}" type="datetime-local" name="incident_at" required class="tw-input">
                                    </div>
                                    <div>
                                        <label for="location_{{ $member->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Location (optional)</label>
                                        <input id="location_{{ $member->id }}" type="text" name="location" maxlength="255" class="tw-input" placeholder="e.g. Market Road, near terminal">
                                    </div>
                                    <div>
                                        <label for="details_{{ $member->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Details</label>
                                        <textarea id="details_{{ $member->id }}" name="details" rows="3" required maxlength="2000" class="tw-textarea" placeholder="Describe what you observed…"></textarea>
                                    </div>
                                    <div>
                                        <label for="proofs_{{ $member->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Proof attachments (optional, max 5)</label>
                                        <input id="proofs_{{ $member->id }}" type="file" name="proofs[]" multiple accept=".jpg,.jpeg,.png,.pdf,.mp4" class="tw-input">
                                        <p class="mt-1 text-xs text-slate-400">Photos, video, or documents (up to 5 files, 10 MB each).</p>
                                    </div>
                                    @if ($errors->any())
                                        <div class="rounded-lg bg-red-50 p-2 text-xs text-red-600">
                                            @foreach ($errors->all() as $error)
                                                <div>{{ $error }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-danger" style="background-color:#dc2626;color:#fff;"
                                            onclick="return confirm('Submit this violation report? It will be sent to TFRB/superadmin for review.');">
                                        <i class="bi bi-send"></i> Submit Report
                                    </button>
                                </form>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        @else
            <button type="button" class="tw-btn tw-btn-sm tw-btn-danger" style="background-color:#dc2626;color:#fff;"
                    data-suspend-open='@json($suspendPayload)'>
                <i class="bi bi-slash-circle"></i> Suspend
            </button>
        @endif
    </div>

    {{-- Violation reports filed by this president against the member --}}
    @if (($violations ?? collect())->isNotEmpty())
        <div class="mt-4 border-t border-slate-100 pt-4">
            <details class="rounded-xl border border-slate-200">
                <summary class="flex cursor-pointer list-none items-center gap-2 px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <i class="bi bi-flag"></i> Reported violations ({{ $violations->count() }})
                </summary>
                <ul class="divide-y divide-slate-100 border-t border-slate-100">
                    @foreach ($violations as $violation)
                        @php
                            $vBadge = $violation->is_solved
                                ? ['label' => 'Solved', 'class' => 'tw-badge-green']
                                : ($violation->is_reviewed ? ['label' => 'Reviewed', 'class' => 'tw-badge-blue'] : ['label' => 'Pending', 'class' => 'tw-badge-red']);
                        @endphp
                        <li class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="tw-badge {{ $vBadge['class'] }}">{{ $vBadge['label'] }}</span>
                                <span class="text-xs font-semibold text-slate-600">{{ $violation->violation_type }}</span>
                                <span class="ml-auto text-[11px] text-slate-400">{{ $violation->created_at->format('M d, Y') }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ $violation->details }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">
                                Incident {{ $violation->incident_at?->format('M d, Y g:i A') }}
                                @if ($violation->location) · {{ $violation->location }} @endif
                            </p>
                            @if ($violation->is_solved && ($violation->penalty_imposed || $violation->resolution_note))
                                <p class="mt-1 rounded-lg bg-emerald-50/70 p-2 text-xs text-emerald-700">
                                    @if ($violation->penalty_imposed) <strong>Penalty:</strong> {{ $violation->penalty_imposed }} @endif
                                    @if ($violation->resolution_note) <span class="block">{{ $violation->resolution_note }}</span> @endif
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </details>
        </div>
    @endif

    {{-- Remove from TODA (detaches the member; the account is kept) --}}
    <div class="mt-4 border-t border-slate-100 pt-4">
        <form method="POST" action="{{ route('president.members.remove', $member->id) }}"
              onsubmit="return confirm('Remove this member from the TODA? They will become a TODA-less operator. Their rating history is kept.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline-danger">
                <i class="bi bi-person-dash"></i> Remove from TODA
            </button>
        </form>
    </div>
</div>
