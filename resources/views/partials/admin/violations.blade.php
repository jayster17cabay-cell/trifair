{{--
    Reusable suspension-violation queue for superadmin / TFRB officer.
    Requires:
    - $routePrefix     string   'superadmin' | 'tfrb-officer'
    - $violations      LengthAwarePaginator of App\Models\SuspensionViolation
    - $filter          string   'pending' | 'reviewed' | 'solved' | 'all'
    - $search          string
    - $pendingCount, $reviewedCount, $solvedCount, $totalCount, $proofsTotal int
--}}

@php
    $statItems = [
        ['num' => $totalCount, 'label' => 'Total', 'icon' => 'bi-flag', 'chip' => 'bg-blue-50 text-navy-600'],
        ['num' => $pendingCount, 'label' => 'Pending', 'icon' => 'bi-clock-history', 'chip' => 'bg-amber-50 text-amber-600'],
        ['num' => $reviewedCount, 'label' => 'Reviewed', 'icon' => 'bi-eye', 'chip' => 'bg-sky-50 text-sky-600'],
        ['num' => $solvedCount, 'label' => 'Solved', 'icon' => 'bi-patch-check-fill', 'chip' => 'bg-emerald-50 text-emerald-600'],
        ['num' => $proofsTotal, 'label' => 'Proofs', 'icon' => 'bi-paperclip', 'chip' => 'bg-slate-100 text-slate-500'],
    ];
@endphp

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-flag mr-2 text-red-500"></i>Violation Reports</h1>
        <p class="tw-page-sub">Suspension violations reported by TODA presidents</p>
    </div>
</div>

{{-- Sticky summary + filter panel --}}
<div class="sticky top-[70px] z-20 -mx-4 mb-5 border-b border-slate-100 bg-white/95 px-4 pb-4 pt-3 shadow-[0_10px_30px_-20px_rgba(15,23,42,0.25)] backdrop-blur-sm sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($statItems as $stat)
            <div class="flex items-center gap-2.5 rounded-lg bg-slate-50 px-3 py-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $stat['chip'] }}"><i class="bi {{ $stat['icon'] }}"></i></span>
                <div class="min-w-0">
                    <div class="text-base font-extrabold leading-none text-slate-800">{{ $stat['num'] }}</div>
                    <div class="mt-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $stat['label'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route($routePrefix . '.violations') }}" class="mt-3.5 flex flex-wrap items-center gap-3">
        <div>
            <label for="violation_filter" class="sr-only">Status</label>
            <select name="filter" id="violation_filter" class="tw-select py-2 text-xs" onchange="this.form.submit()" aria-label="Filter violations by status">
                <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>All status ({{ $totalCount }})</option>
                <option value="pending" {{ $filter === 'pending' ? 'selected' : '' }}>Pending ({{ $pendingCount }})</option>
                <option value="reviewed" {{ $filter === 'reviewed' ? 'selected' : '' }}>Reviewed ({{ $reviewedCount }})</option>
                <option value="solved" {{ $filter === 'solved' ? 'selected' : '' }}>Solved ({{ $solvedCount }})</option>
            </select>
        </div>
        <div class="ml-auto flex flex-wrap items-center gap-2">
            <div class="tw-input-group">
                <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search operator, type, location"
                       class="tw-input py-2" style="width: min(16rem, 100%);" aria-label="Search violation reports">
            </div>
            <button type="submit" class="tw-btn tw-btn-sm tw-btn-navy"><i class="bi bi-search"></i><span class="hidden sm:inline">Search</span></button>
            @if ($search !== '')
                <a href="{{ route($routePrefix . '.violations', array_filter(['filter' => $filter], fn ($v) => $v !== null && $v !== '')) }}"
                   class="tw-btn tw-btn-sm tw-btn-ghost" title="Clear search">
                    <i class="bi bi-x-lg"></i><span class="hidden sm:inline">Clear</span>
                </a>
            @endif
        </div>
    </form>
</div>

@forelse ($violations as $violation)
    @php
        $statusBadge = $violation->is_solved
            ? ['label' => 'Solved', 'class' => 'tw-badge-green']
            : ($violation->is_reviewed ? ['label' => 'Reviewed', 'class' => 'tw-badge-blue'] : ['label' => 'Pending', 'class' => 'tw-badge-red']);
        $operatorName = $violation->operator->user->name ?? 'Unknown operator';
    @endphp
    <div class="tw-card mb-3 p-4" data-violation-card>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex min-w-0 items-start gap-3">
                <div class="tw-avatar tw-avatar-sm bg-navy-700">{{ strtoupper(substr($operatorName, 0, 1)) }}</div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="truncate text-sm font-bold text-slate-900">{{ $operatorName }}</span>
                        <span class="tw-badge {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span>
                    </div>
                    <div class="mt-0.5 text-xs text-slate-500">
                        Body #{{ $violation->operator->body_number ?? '—' }} · {{ $violation->operator->plate_number ?? '—' }}
                        @if ($violation->toda) · {{ $violation->toda->name }} @endif
                    </div>
                </div>
            </div>
            <span class="tw-badge tw-badge-gray"><i class="bi bi-exclamation-octagon"></i> {{ $violation->violation_type }}</span>
        </div>

        <div class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
            <div><i class="bi bi-calendar-event mr-1 text-slate-400"></i> {{ $violation->incident_at?->format('M d, Y g:i A') }}</div>
            @if ($violation->location)
                <div><i class="bi bi-geo-alt mr-1 text-slate-400"></i> {{ $violation->location }}</div>
            @endif
            <div><i class="bi bi-person-badge mr-1 text-slate-400"></i> Reported by {{ $violation->reporter->name ?? '—' }}</div>
            <div><i class="bi bi-clock mr-1 text-slate-400"></i> Filed {{ $violation->created_at->format('M d, Y g:i A') }}</div>
        </div>

        <p class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">{{ $violation->details }}</p>

        @if ($violation->proofs->count() > 0)
            <div class="mt-2">
                <small class="text-[0.65rem] font-bold uppercase tracking-widest text-navy-600"><i class="bi bi-paperclip mr-1"></i>Proofs</small>
                <div class="mt-1 flex flex-wrap gap-2">
                    @foreach ($violation->proofs as $proof)
                        <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-50">
                            <i class="bi bi-{{ str_contains($proof->file_type ?? '', 'image') ? 'image' : (str_contains($proof->file_type ?? '', 'video') ? 'play-circle' : 'file-earmark') }}"></i>
                            {{ $proof->original_name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Review details --}}
        @if ($violation->is_reviewed && ($violation->reviewer || $violation->review_note))
            <div class="mt-3 rounded-lg border border-sky-100 bg-sky-50/60 p-3 text-xs text-slate-600">
                <span class="font-semibold text-sky-700"><i class="bi bi-eye mr-1"></i>Reviewed</span>
                @if ($violation->reviewer) by {{ $violation->reviewer->name }} @endif
                @if ($violation->review_note) · {{ $violation->review_note }} @endif
            </div>
        @endif

        {{-- Resolution details --}}
        @if ($violation->is_solved)
            <div class="mt-2 rounded-lg border border-emerald-100 bg-emerald-50/60 p-3 text-xs text-slate-600">
                <div class="font-semibold text-emerald-700"><i class="bi bi-patch-check mr-1"></i>Solved{{ $violation->solved_at ? ' ' . $violation->solved_at->format('M d, Y') : '' }}</div>
                @if ($violation->penalty_imposed)
                    <div class="mt-1"><span class="font-semibold text-slate-700">Penalty:</span> {{ $violation->penalty_imposed }}</div>
                @endif
                @if ($violation->recommended_action)
                    <div class="mt-0.5"><span class="font-semibold text-slate-700">Action:</span> {{ $violation->recommended_action }}</div>
                @endif
                @if ($violation->resolution_note)
                    <div class="mt-0.5"><span class="font-semibold text-slate-700">Note:</span> {{ $violation->resolution_note }}</div>
                @endif
            </div>
        @endif

        {{-- Actions --}}
        <div class="mt-3 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3">
            @if (!$violation->is_solved)
                @if (!$violation->is_reviewed)
                    <button type="button" class="tw-btn tw-btn-sm tw-btn-outline"
                            data-violation-review
                            data-action="{{ route($routePrefix . '.violations.review', $violation->id) }}"
                            data-ref="#{{ $violation->id }} ({{ $operatorName }})">
                        <i class="bi bi-eye"></i> Review
                    </button>
                @endif
                <button type="button" class="tw-btn tw-btn-sm tw-btn-navy"
                        data-violation-solve
                        data-action="{{ route($routePrefix . '.violations.solve', $violation->id) }}"
                        data-ref="#{{ $violation->id }} ({{ $operatorName }})">
                    <i class="bi bi-patch-check"></i> Solve &amp; Impose Penalty
                </button>
            @else
                <form method="POST" action="{{ route($routePrefix . '.violations.reopen', $violation->id) }}"
                      onsubmit="return confirm('Reopen this violation report? It returns to the pending queue.');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline">
                        <i class="bi bi-arrow-counterclockwise"></i> Reopen
                    </button>
                </form>
            @endif
        </div>
    </div>
@empty
    <div class="tw-empty py-16">
        <div class="tw-empty-icon"><i class="bi bi-flag text-red-400"></i></div>
        <h3 class="tw-card-title mb-1">No violation reports</h3>
        <p class="text-sm text-slate-500">Reports filed by TODA presidents will appear here for review.</p>
    </div>
@endforelse

@if ($violations->hasPages())
    <div class="mt-4">
        {{ $violations->links('pagination::tailwind') }}
    </div>
@endif

{{-- Shared resolution modal --}}
<div id="violationResolutionModal" class="tw-modal-backdrop hidden" data-violation-modal>
    <div class="tw-modal" role="dialog" aria-modal="true" aria-labelledby="violationResolutionTitle">
        <div class="tw-modal-head">
            <h3 id="violationResolutionTitle" class="text-base font-bold text-slate-800" data-violation-title>Review Violation</h3>
            <button type="button" class="tw-modal-close" data-violation-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="violationResolutionForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <div class="tw-modal-body">
                <p class="mb-3 text-sm leading-relaxed text-slate-500" data-violation-help></p>

                <div data-violation-review-note-wrap>
                    <label class="tw-label" for="violationReviewNote">Review note (optional)</label>
                    <textarea id="violationReviewNote" name="review_note" rows="3" maxlength="2000" class="tw-textarea" placeholder="Notes for the record..."></textarea>
                </div>

                <div class="hidden" data-violation-solve-wrap>
                    <label class="tw-label" for="violationResolutionNote">Resolution note <span class="text-red-500">*</span></label>
                    <textarea id="violationResolutionNote" name="resolution_note" rows="3" maxlength="2000" class="tw-textarea" placeholder="Describe how this violation was resolved..."></textarea>

                    <label class="tw-label mt-3" for="violationRecommendedAction">Recommended action (optional)</label>
                    <input type="text" id="violationRecommendedAction" name="recommended_action" maxlength="255" class="tw-input" placeholder="e.g. Additional 30-day suspension">

                    <label class="tw-label mt-3" for="violationPenalty">Penalty imposed (optional)</label>
                    <textarea id="violationPenalty" name="penalty_imposed" rows="2" maxlength="2000" class="tw-textarea" placeholder="e.g. ₱2,000 fine + franchise suspension"></textarea>

                    <label class="tw-label mt-3" for="violationProofs">Supporting proof (optional)</label>
                    <input type="file" id="violationProofs" name="resolution_proofs[]" multiple
                           accept="image/*,video/*,.pdf" class="tw-input">
                    <p class="mt-1 text-xs text-slate-400">Attach evidence of the imposed penalty (up to 5 files).</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                <button type="button" class="tw-btn tw-btn-sm tw-btn-outline" data-violation-close>Cancel</button>
                <button type="submit" class="tw-btn tw-btn-sm tw-btn-navy" data-violation-submit>Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var modal = document.querySelector('[data-violation-modal]');
        if (!modal) return;

        var form = document.getElementById('violationResolutionForm');
        var title = modal.querySelector('[data-violation-title]');
        var help = modal.querySelector('[data-violation-help]');
        var reviewWrap = modal.querySelector('[data-violation-review-note-wrap]');
        var solveWrap = modal.querySelector('[data-violation-solve-wrap]');
        var reviewNote = document.getElementById('violationReviewNote');
        var resolutionNote = document.getElementById('violationResolutionNote');
        var submit = modal.querySelector('[data-violation-submit]');

        function setRequired(el, required) {
            if (!el) return;
            if (required) { el.setAttribute('required', 'required'); }
            else { el.removeAttribute('required'); }
        }

        function open(mode, action, ref) {
            form.setAttribute('action', action);
            reviewNote.value = '';
            resolutionNote.value = '';
            var solved = mode === 'solve';
            title.textContent = solved ? 'Solve Violation & Impose Penalty' : 'Review Violation';
            help.textContent = solved
                ? 'Confirm the penalty for violation ' + ref + '. This closes the report.'
                : 'Acknowledge violation ' + ref + '. It stays open until you impose a penalty.';
            reviewWrap.classList.toggle('hidden', solved);
            solveWrap.classList.toggle('hidden', !solved);
            setRequired(resolutionNote, solved);
            setRequired(reviewNote, !solved);
            if (submit) { submit.textContent = solved ? 'Mark Solved' : 'Mark Reviewed'; }
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            (solved ? resolutionNote : reviewNote).focus();
        }

        function close() {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        document.addEventListener('click', function (e) {
            if (!e.target || !e.target.closest) return;
            var review = e.target.closest('[data-violation-review]');
            if (review) {
                e.preventDefault();
                open('review', review.getAttribute('data-action'), review.getAttribute('data-ref'));
                return;
            }
            var solve = e.target.closest('[data-violation-solve]');
            if (solve) {
                e.preventDefault();
                open('solve', solve.getAttribute('data-action'), solve.getAttribute('data-ref'));
                return;
            }
            if (e.target.closest('[data-violation-close]')) {
                close();
                return;
            }
            if (e.target === modal) {
                close();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                close();
            }
        });
    })();
</script>
