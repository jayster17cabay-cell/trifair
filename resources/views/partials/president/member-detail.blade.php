{{--
    Member detail modal body — shows one member's ratings/complaints history.
    Requires: $member (Operator with user), $ratings (paginator).
--}}
<div class="tw-modal-head">
    <div class="flex items-center gap-3">
        @php $memberName = $member->user ? $member->user->name : 'Unknown'; @endphp
        <div class="tw-avatar tw-avatar-md bg-gold text-navy-800">{{ strtoupper(substr($memberName, 0, 1)) }}</div>
        <div>
            <h5 class="text-base font-bold text-slate-900">{{ $memberName }}</h5>
            <p class="text-xs text-slate-500">Body #{{ $member->body_number }} · {{ $member->plate_number }}</p>
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
    @endphp

    {{-- Suspension status + controls (president-only, own TODA members) --}}
    <div class="mb-4">
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
                    </div>
                </div>
            </div>
        @else
            <details class="rounded-xl border border-slate-200 bg-slate-50">
                <summary class="flex cursor-pointer list-none items-center gap-2 px-4 py-3 text-sm font-semibold text-slate-700">
                    <i class="bi bi-slash-circle text-red-500"></i> Suspend this member
                </summary>
                <form method="POST" action="{{ route('president.members.suspend', $member->id) }}" class="space-y-3 border-t border-slate-200 px-4 py-4">
                    @csrf
                    <div>
                        <label for="suspendReason" class="tw-stat-label mb-1 block">Resolution / Reason</label>
                        <textarea id="suspendReason" name="reason" rows="3" required maxlength="1000"
                                  placeholder="State the resolution or reason for the suspension…"
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold"></textarea>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="suspendedAt" class="tw-stat-label mb-1 block">Start date</label>
                            <input id="suspendedAt" type="date" name="suspended_at" required value="{{ now()->toDateString() }}"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold">
                        </div>
                        <div>
                            <label for="suspendedUntil" class="tw-stat-label mb-1 block">End date</label>
                            <input id="suspendedUntil" type="date" name="suspended_until" required value="{{ now()->addDays(7)->toDateString() }}"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold">
                        </div>
                    </div>
                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-danger">
                        <i class="bi bi-slash-circle"></i>Suspend Member
                    </button>
                </form>
            </details>
        @endif

        @if (($suspensionHistory ?? collect())->isNotEmpty())
            <details class="mt-2 rounded-xl border border-slate-200">
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
    </div>
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

    @if ($ratings->hasPages())
        <div class="mt-3" data-president-pagination>
            {{ $ratings->links('pagination::tailwind') }}
        </div>
    @endif
</div>
