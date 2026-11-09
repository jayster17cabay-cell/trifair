@php
    $operator = $violation->operator;
    $operatorName = $operator->user->name ?? 'Unknown';
    $bodyNumber = $operator?->body_number;
    $isSolved = (bool) $violation->is_solved;
    $isReviewed = (bool) $violation->is_reviewed;

    $statusKey = $isSolved ? 'solved' : ($isReviewed ? 'reviewed' : 'pending');
    $statusMap = [
        'pending' => ['label' => 'Pending', 'icon' => 'bi-clock-fill', 'badge' => 'tw-badge tw-badge-amber', 'border' => 'border-l-amber-400', 'avatar' => 'bg-amber-500/10 text-amber-600'],
        'reviewed' => ['label' => 'Reviewed', 'icon' => 'bi-eye-fill', 'badge' => 'tw-badge tw-badge-blue', 'border' => 'border-l-blue-500', 'avatar' => 'bg-blue-500/10 text-blue-600'],
        'solved' => ['label' => 'Solved', 'icon' => 'bi-patch-check-fill', 'badge' => 'tw-badge tw-badge-navy', 'border' => 'border-l-navy-600', 'avatar' => 'bg-navy-600/10 text-navy-700'],
    ];
    $status = $statusMap[$statusKey];
@endphp
<div class="tw-card mb-3.5 overflow-hidden border-l-4 {{ $status['border'] }}">
    <button type="button"
            onclick="var b=this.parentElement.querySelector('[data-violation-details]');var o=b.classList.toggle('hidden')===false;this.setAttribute('aria-expanded',o?'true':'false');var c=this.querySelector('[data-violation-chevron]');if(c)c.classList.toggle('rotate-180',o);"
            aria-expanded="false"
            class="flex w-full items-start gap-3 px-4 py-3.5 text-left transition-colors hover:bg-slate-50 sm:px-5">
        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $status['avatar'] }}" aria-hidden="true">
            <i class="bi {{ $status['icon'] }}"></i>
        </span>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                <span class="rounded-md border border-navy-600/10 bg-navy-600/5 px-1.5 py-0.5 font-mono text-[10px] font-bold text-navy-700">{{ $violation->reference_number }}</span>
                <span class="truncate text-sm font-bold text-slate-800">{{ $operatorName }}</span>
                @if ($bodyNumber)
                    <span class="text-xs text-slate-400">B#{{ $bodyNumber }}</span>
                @endif
            </div>
            <div class="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-slate-400">
                <span class="{{ $status['badge'] }}"><i class="bi {{ $status['icon'] }}"></i> {{ $status['label'] }}</span>
                <span><i class="bi bi-calendar3 mr-1" aria-hidden="true"></i>Filed {{ $violation->created_at->format('M d, Y') }}</span>
                @if ($violation->solved_at)
                    <span><i class="bi bi-check2-all mr-1" aria-hidden="true"></i>Solved {{ $violation->solved_at->diffForHumans() }}</span>
                @endif
                <span class="inline-flex items-center gap-1 rounded-md border border-amber-100 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                    <i class="bi bi-exclamation-triangle"></i>{{ $violation->violation_type }}@if ($violation->other_reason) — {{ $violation->other_reason }}@endif
                </span>
                @if ($violation->proofs->count() > 0)
                    <span class="inline-flex items-center gap-1 rounded-md border border-violet-100 bg-violet-50 px-2 py-0.5 text-[10px] font-semibold text-violet-700">
                        <i class="bi bi-paperclip"></i>{{ $violation->proofs->count() }} proof{{ $violation->proofs->count() > 1 ? 's' : '' }}
                    </span>
                @endif
            </div>
        </div>

        <i data-violation-chevron class="bi bi-chevron-down mt-2 shrink-0 text-slate-400 transition-transform duration-200" aria-hidden="true"></i>
    </button>

    <div data-violation-details class="hidden border-t border-slate-100 bg-slate-50/40 px-4 py-4 sm:px-5">
        @if ($violation->start_location || $violation->end_location)
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                <i class="bi bi-sign-turn-right text-slate-400" aria-hidden="true"></i>
                <span>{{ $violation->start_location ?? '—' }}</span>
                <i class="bi bi-arrow-right text-slate-400" aria-hidden="true"></i>
                <span>{{ $violation->end_location ?? '—' }}</span>
            </div>
        @endif

        @if ($violation->details)
            <p class="mt-2 text-sm italic leading-relaxed text-slate-500">"{{ $violation->details }}"</p>
        @endif

        @if ($violation->proofs->count() > 0)
            <div class="mt-3">
                <div class="mb-1 text-[0.7rem] font-bold uppercase tracking-widest text-slate-400">Attached evidence ({{ $violation->proofs->count() }} file(s))</div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($violation->proofs as $proof)
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

        @if ($violation->is_reviewed && !$violation->is_solved)
            <div class="mt-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3">
                <div class="mb-1 flex items-center gap-1.5">
                    <i class="bi bi-eye-fill text-blue-500"></i>
                    <span class="text-[0.7rem] font-bold uppercase tracking-widest text-blue-600">Seen by the TFRB</span>
                </div>
                <p class="mb-0 text-sm text-slate-700">{{ $violation->review_note ?: 'Your report has been reviewed — an outcome will be posted here once the case is resolved.' }}</p>
            </div>
        @elseif ($violation->is_solved)
            <div class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                <div class="mb-1 flex items-center gap-1.5">
                    <i class="bi bi-patch-check-fill text-emerald-600"></i>
                    <span class="text-[0.7rem] font-bold uppercase tracking-widest text-emerald-700">Resolution</span>
                </div>
                @if ($violation->penalty_imposed)
                    <p class="mb-0 text-sm font-semibold text-red-700"><i class="bi bi-gavel mr-1"></i>Penalty: {{ $violation->penalty_imposed }}</p>
                @endif
                @if ($violation->resolution_note)
                    <p class="mb-0 mt-1 text-sm text-slate-700">{{ $violation->resolution_note }}</p>
                @elseif (!$violation->penalty_imposed)
                    <p class="mb-0 text-sm text-slate-700">The TFRB has resolved this report.</p>
                @endif
            </div>
        @endif

        {{-- Follow-up thread with the TFRB --}}
        <div class="mt-4 border-t border-slate-200 pt-4">
            @php $vThread = $violation->followUps; @endphp
            @if ($vThread->count() > 0)
                <div class="mb-3 space-y-2">
                    @foreach ($vThread as $fu)
                        @if ($fu->isFromStaff())
                            <div class="flex justify-start">
                                <div class="max-w-[85%] rounded-xl border border-navy-100 bg-navy-50 px-3 py-2 text-sm text-slate-700">
                                    <div class="mb-0.5 text-[0.65rem] font-bold uppercase tracking-wider text-navy-600">TFRB / Superadmin</div>
                                    {{ $fu->message }}
                                </div>
                            </div>
                        @else
                            <div class="flex justify-end">
                                <div class="max-w-[85%] rounded-xl bg-violet-600 px-3 py-2 text-sm text-white">
                                    <div class="mb-0.5 text-[0.65rem] font-bold uppercase tracking-wider text-violet-100">You</div>
                                    {{ $fu->message }}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
            <form action="{{ route('passenger.violations.followUp', $violation) }}" method="POST" class="flex flex-col gap-2">
                @csrf
                <textarea name="message" rows="2" maxlength="1000" required class="tw-textarea w-full bg-white" placeholder="Add a follow-up message to your report..."></textarea>
                <div class="flex justify-end">
                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-navy"><i class="bi bi-send-fill"></i> Send</button>
                </div>
            </form>
        </div>
    </div>
</div>
