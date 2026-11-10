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

    // Franchise details for the shared franchise modal (issue / edit / renew).
    $currentTerm = $member->currentTerm;
    $franchiseNo = $currentTerm->franchise_no ?? null;
    $franchiseStatus = $member->franchiseStatus();
    $franchiseExpiringSoon = $member->isFranchiseExpiringSoon();
    $canIssueFranchise = !$member->isArchived() && in_array($member->status, ['active', 'inactive'], true);
    $franchiseViewData = [
        'id' => $member->id,
        'name' => $member->user->name ?? 'Unknown',
        'email' => $member->user->email ?? '',
        'toda' => $member->toda ? $member->toda->name : null,
        'contact' => $member->contact_number,
        'plate' => $member->plate_number,
        'body' => $member->body_number,
        'color' => $member->motorcycle_model,
        'statusLabel' => ucfirst($member->status),
        'franchiseNo' => $franchiseNo,
        'franchiseIssued' => optional($currentTerm ? $currentTerm->issued_at : null)->format('M d, Y'),
        'franchiseValidUntil' => ($currentTerm && $currentTerm->valid_until) ? $currentTerm->valid_until->format('M d, Y') : null,
        'franchiseIssuedRaw' => ($currentTerm && $currentTerm->issued_at) ? $currentTerm->issued_at->toDateString() : null,
        'franchiseValidUntilRaw' => ($currentTerm && $currentTerm->valid_until) ? $currentTerm->valid_until->toDateString() : null,
        'franchiseStatus' => $franchiseStatus,
        'franchiseExpiringSoon' => $franchiseExpiringSoon,
        'hasTerm' => (bool) $currentTerm,
        'canIssueFranchise' => $canIssueFranchise,
        'issueUrl' => route('president.members.franchise.issue', $member),
        'updateUrl' => route('president.members.franchise.update', $member),
        'renewUrl' => route('president.members.franchise.renew', $member),
    ];

        // Legend for the complaint actions
        $complaintActionLegend = [
            'resolve' => ['label' => 'Resolve', 'class' => 'tw-btn-gold'],
            'forward' => ['label' => 'Forward to TFRB', 'class' => 'tw-btn-outline border-violet-300 text-violet-700'],
        ];
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

    {{-- Ratings list — compact, tap-through for complaints --}}
    <div class="space-y-2.5">
        @forelse ($ratings as $rating)
            @php
                $r = (int) $rating->rating;
                if ($r >= 4) { $mClass = 'bg-blue-50 text-navy-700'; }
                elseif ($r <= 2) { $mClass = 'bg-amber-600 text-white'; }
                else { $mClass = 'bg-gold-50 text-gold-700'; }
                $preview = $rating->complaint_details ?: $rating->reason;
                $isComplaint = (bool) $rating->complaint_type;
                // Only complaints still routed to this president's queue can be
                // opened here — forwarded / TFRB-owned cases must not 403.
                $detailUrl = ($isComplaint && (int) $rating->routed_to === (int) \Illuminate\Support\Facades\Auth::id())
                    ? route('president.complaints.show', $rating)
                    : null;
                $tag = $detailUrl ? 'a' : 'div';
            @endphp
            <{{ $tag }} @if ($detailUrl) href="{{ $detailUrl }}" @endif
               class="group flex items-start gap-3 rounded-xl border border-slate-100 bg-white px-3.5 py-3 transition-colors hover:bg-slate-50 no-underline {{ $detailUrl ? 'cursor-pointer' : '' }}">
                <div class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full text-sm font-extrabold {{ $mClass }}">
                    {{ $rating->rating }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="flex gap-0.5" aria-label="{{ $r }} out of 5 stars">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="bi {{ $i <= $r ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-300' }} text-xs"></i>
                                @endfor
                            </span>
                            @if ($isComplaint)
                                <span class="tw-badge tw-badge-red"><i class="bi bi-exclamation-triangle"></i>{{ $rating->complaint_type }}</span>
                                @php
                                    $st = $rating->status_label;
                                    $stBadge = $st === 'Solved' ? 'tw-badge tw-badge-navy' : ($st === 'Rejected' ? 'tw-badge tw-badge-red' : 'tw-badge tw-badge-amber');
                                    $stText = $st === 'Solved' ? 'Solved' : ($st === 'Rejected' ? 'Rejected' : 'Under review');
                                @endphp
                                <span class="{{ $stBadge }}">{{ $stText }}</span>
                            @elseif ($rating->response)
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600">
                                    <i class="bi bi-check-circle-fill"></i> Responded
                                </span>
                            @elseif ($r <= 2)
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-600">
                                    <i class="bi bi-exclamation-circle"></i> Needs response
                                </span>
                            @endif
                        </div>
                        <small class="whitespace-nowrap text-[11px] text-slate-400">{{ $rating->created_at->format('M d, Y') }}</small>
                    </div>

                    @if ($preview)
                        <p class="mt-1 text-sm leading-snug text-slate-600 line-clamp-2">{{ $preview }}</p>
                    @endif

                    @if ($rating->response)
                        <p class="mt-1.5 rounded-lg bg-navy-50/60 px-2.5 py-1.5 text-xs text-slate-500">
                            <i class="bi bi-reply-fill mr-1"></i> {{ \Illuminate\Support\Str::limit($rating->response->message, 120) }}
                        </p>
                    @endif

                    @if ($rating->start_location || $rating->end_location)
                        <p class="mt-1 text-[11px] text-slate-400">
                            <i class="bi bi-geo-alt mr-1"></i>{{ $rating->start_location ?: '—' }} <i class="bi bi-arrow-right mx-0.5"></i> {{ $rating->end_location ?: '—' }}
                        </p>
                    @endif
                </div>

                @if ($detailUrl)
                    <i class="bi bi-chevron-right mt-1 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-navy-500" aria-hidden="true"></i>
                @endif
            </{{ $tag }}>
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

    {{-- Franchise term (issue / edit / renew) --}}
    <div class="mt-4 border-t border-slate-100 pt-4">
        <div class="mb-2 flex items-center justify-between">
            <div class="text-xs font-bold uppercase tracking-wider text-navy-600">Franchise</div>
            @if ($currentTerm)
                <span class="tw-badge {{ $franchiseStatus === 'expired' ? 'tw-badge-red' : 'tw-badge-green' }}">
                    <i class="bi {{ $franchiseStatus === 'expired' ? 'bi-x-circle' : 'bi-check-circle' }}"></i>
                    {{ $franchiseStatus === 'expired' ? 'Expired' : 'Active' }}
                </span>
            @else
                <span class="tw-badge tw-badge-gray"><i class="bi bi-dash-circle"></i> None</span>
            @endif
        </div>
        <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            @if ($currentTerm)
                <div class="flex flex-wrap gap-x-4 gap-y-1">
                    @if ($franchiseNo)
                        <span><span class="text-slate-400">No.</span> <span class="font-semibold text-slate-700">{{ $franchiseNo }}</span></span>
                    @endif
                    @if ($currentTerm->issued_at)
                        <span><span class="text-slate-400">Issued:</span> {{ $currentTerm->issued_at->format('M d, Y') }}</span>
                    @endif
                    @if ($currentTerm->valid_until)
                        <span><span class="text-slate-400">Valid until:</span> {{ $currentTerm->valid_until->format('M d, Y') }}</span>
                    @else
                        <span class="text-slate-400">No expiry</span>
                    @endif
                </div>
                @if ($franchiseExpiringSoon)
                    <p class="mt-1 text-xs font-semibold text-amber-600"><i class="bi bi-exclamation-triangle mr-1"></i>Expiring soon</p>
                @endif
            @else
                <p class="text-sm text-slate-500">No franchise term issued yet.</p>
            @endif
        </div>
        @if ($canIssueFranchise)
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button type="button" class="tw-btn tw-btn-sm tw-btn-outline-gold"
                        data-franchise-open='@json($franchiseViewData)'
                        data-franchise-mode="{{ $currentTerm ? 'edit' : 'issue' }}">
                    <i class="bi {{ $currentTerm ? 'bi-pencil-square' : 'bi-patch-check' }}"></i>
                    {{ $currentTerm ? 'Edit Franchise' : 'Issue Franchise' }}
                </button>
            </div>
        @endif
    </div>

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
                        <form method="POST" action="{{ route('president.members.suspend.end', $member->id) }}" class="mt-3" onsubmit="return confirm('End this member\'s suspension early?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline-danger">
                                <i class="bi bi-play-circle"></i>End Suspension Early
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <button type="button" class="tw-btn tw-btn-sm tw-btn-danger"
                    data-suspend-open='@json($suspendPayload)'>
                <i class="bi bi-slash-circle"></i> Suspend
            </button>
        @endif
    </div>

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
