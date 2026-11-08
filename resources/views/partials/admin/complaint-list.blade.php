{{--
    Reusable complaints list. Requires:
    - $routePrefix     string             'superadmin' | 'tfrb-officer' | 'president'
    - $presidentMode   bool               (optional) true = president's queue
    - $complaints      LengthAwarePaginator of App\Models\Rating
    - $filter          string             'open' | 'rejected' | 'solved' | 'all'
    - $ratingFilter    int|null           star rating filter: 1 | 2 | null
    - $rejectedCount, $solvedCount, $totalCount, $openCount int
    - $star1Count, $star2Count           int
    - $activeOperators Collection          active operators for export filter
    - $proofsTotal    int|null         (optional)
    - $readOnly       bool              (optional) hide action buttons
--}}
@php
    $search = trim((string) ($search ?? request('search', '')));
    $presidentMode = $presidentMode ?? false;
    $readOnly = $readOnly ?? false;
    $source = $source ?? 'all';
    $sourceCounts = $sourceCounts ?? [
        'actionable' => 0,
        'forwarded' => 0,
        'against_president' => 0,
        'unassigned' => 0,
        'awaiting' => 0,
        'all' => $totalCount ?? 0,
    ];
    $openCount = $openCount ?? 0;

    $sourceUrl = function (string $key) use ($routePrefix, $filter, $ratingFilter, $search) {
        return route($routePrefix . '.complaints', array_filter([
            'source' => $key,
            'filter' => $filter,
            'rating' => $ratingFilter,
            'search' => $search,
        ], fn ($value) => $value !== null && $value !== ''));
    };

    $sourceTabs = [
        ['key' => 'actionable', 'label' => 'Actionable', 'short' => 'Actionable', 'icon' => 'bi-lightning-charge-fill', 'tone' => 'text-navy-600'],
        ['key' => 'forwarded', 'label' => 'Forwarded', 'short' => 'Forwarded', 'icon' => 'bi-send-fill', 'tone' => 'text-violet-600'],
        ['key' => 'against_president', 'label' => 'vs President', 'short' => 'vs Pres', 'icon' => 'bi-person-x', 'tone' => 'text-red-600'],
        ['key' => 'unassigned', 'label' => 'Unassigned', 'short' => 'Unassigned', 'icon' => 'bi-person-dash', 'tone' => 'text-slate-500'],
        ['key' => 'awaiting', 'label' => 'Awaiting', 'short' => 'Awaiting', 'icon' => 'bi-hourglass-split', 'tone' => 'text-amber-600'],
        ['key' => 'all', 'label' => 'All', 'short' => 'All', 'icon' => 'bi-collection', 'tone' => 'text-slate-500'],
    ];

    $statItems = [
        ['num' => $totalCount, 'label' => 'Total', 'icon' => 'bi-exclamation-circle', 'chip' => 'bg-blue-50 text-navy-600'],
        ['num' => $openCount, 'label' => 'Open', 'icon' => 'bi-clock-history', 'chip' => 'bg-amber-50 text-amber-600'],
        ['num' => $rejectedCount, 'label' => 'Rejected', 'icon' => 'bi-x-circle', 'chip' => 'bg-red-50 text-red-600'],
        ['num' => $solvedCount, 'label' => 'Solved', 'icon' => 'bi-patch-check-fill', 'chip' => 'bg-violet-50 text-violet-600'],
    ];
@endphp

<div class="tw-page-head">
    <div>
        @if ($presidentMode)
            <h1 class="tw-page-title"><i class="bi bi-flag mr-2 text-amber-500"></i>Complaints Queue</h1>
            <p class="tw-page-sub">Passenger complaints against members of your TODA — you handle them first</p>
            @if (($actionableCount ?? 0) > 0)
                <span class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">
                    <i class="bi bi-hourglass-split"></i>{{ $actionableCount }} waiting for your action
                </span>
            @endif
        @else
            <h1 class="tw-page-title"><i class="bi bi-exclamation-triangle mr-2 text-amber-500"></i>Complaints</h1>
            <p class="tw-page-sub">Organized by source — pick a tab to filter.</p>
        @endif
    </div>
    @unless ($presidentMode)
        @include('partials.admin.export-dropdown', [
            'exportRoute' => route($routePrefix . '.complaints.export'),
            'exportLabel' => 'Complaints',
            'exportIcon' => 'bi-exclamation-triangle',
            'activeOperators' => $activeOperators,
            'exportFilters' => [
                ['type' => 'select', 'name' => 'filter', 'label' => 'Status', 'options' => [
                    '' => 'All', 'open' => 'Open', 'rejected' => 'Rejected', 'solved' => 'Solved',
                ], 'value' => $filter],
                ['type' => 'select', 'name' => 'rating', 'label' => 'Stars', 'options' => [
                    '' => 'All stars', '1' => '1 Star', '2' => '2 Stars',
                ], 'value' => $ratingFilter ?? ''],
                ['type' => 'daterange', 'prefix' => 'date', 'from' => request('date_from'), 'to' => request('date_to')],
            ],
            'preservedParams' => array_filter(['source' => $presidentMode ? null : $source, 'search' => $search], fn ($v) => $v !== null && $v !== ''),
        ])
    @endunless
</div>

{{-- Sticky summary + filter panel --}}
<div class="sticky top-[70px] z-20 -mx-4 mb-5 border-b border-slate-100 bg-white/95 px-4 pb-4 pt-3 shadow-[0_10px_30px_-20px_rgba(15,23,42,0.25)] backdrop-blur-sm sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    {{-- Summary stats --}}
    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
        @foreach ($statItems as $stat)
            @php $statTag = !empty($stat['href']) ? 'a' : 'div'; @endphp
            <{{ $statTag }}
                @if (!empty($stat['href'])) href="{{ $stat['href'] }}" title="Show forwarded complaints" @endif
                class="flex items-center gap-2.5 rounded-lg bg-slate-50 px-3 py-2.5 {{ !empty($stat['href']) ? 'transition hover:bg-violet-50 hover:ring-1 hover:ring-violet-200' : '' }}">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $stat['chip'] }}"><i class="bi {{ $stat['icon'] }}"></i></span>
                <div class="min-w-0">
                    <div class="text-base font-extrabold leading-none text-slate-800">{{ $stat['num'] }}</div>
                    <div class="mt-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $stat['label'] }}</div>
                </div>
                @if (!empty($stat['href']))
                    <i class="bi bi-arrow-right ml-auto text-violet-400"></i>
                @endif
            </{{ $statTag }}>
        @endforeach
    </div>

    @unless ($presidentMode)
        {{-- Source tabs --}}
        <div class="mt-3 flex flex-wrap items-center gap-1.5" role="tablist" aria-label="Complaint source" data-source-tablist data-source-poll-url="{{ route($routePrefix . '.complaints.sourceCounts') }}">
            @foreach ($sourceTabs as $tab)
                @php $isActiveSource = $source === $tab['key']; @endphp
                <a href="{{ $sourceUrl($tab['key']) }}" role="tab" aria-selected="{{ $isActiveSource ? 'true' : 'false' }}" data-source="{{ $tab['key'] }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-bold transition {{ $isActiveSource ? 'border-navy-600 bg-navy-600 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-navy-200 hover:bg-slate-50' }}">
                    <i class="bi {{ $tab['icon'] }} {{ $isActiveSource ? '' : $tab['tone'] }}"></i>
                    <span class="hidden sm:inline">{{ $tab['label'] }}</span>
                    <span class="sm:hidden">{{ $tab['short'] }}</span>
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-extrabold {{ $isActiveSource ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}" data-source-count="{{ $sourceCounts[$tab['key']] ?? 0 }}">{{ $sourceCounts[$tab['key']] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
    @endunless

    {{-- Status, severity and search filters. --}}
    <form method="GET" action="{{ route($routePrefix . '.complaints') }}" class="mt-3.5 flex flex-wrap items-end gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-2.5 sm:bg-transparent sm:p-0">
        @unless ($presidentMode)
            <input type="hidden" name="source" value="{{ $source }}">
        @endunless
        <div class="min-w-[9rem] flex-1 sm:flex-none">
            <label for="complaint_filter" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400 sm:sr-only">Status</label>
            <select name="filter" id="complaint_filter" class="tw-select w-full py-2 text-xs sm:w-auto" onchange="this.form.submit()" aria-label="Filter complaints by status">
                <option value="open" {{ $filter === 'open' ? 'selected' : '' }}>Open ({{ $openCount }})</option>
                <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>All status ({{ $totalCount }})</option>
                <option value="rejected" {{ $filter === 'rejected' ? 'selected' : '' }}>Rejected ({{ $rejectedCount }})</option>
                <option value="solved" {{ $filter === 'solved' ? 'selected' : '' }}>Solved ({{ $solvedCount }})</option>
            </select>
        </div>
        <div class="min-w-[9rem] flex-1 sm:flex-none">
            <label for="complaint_rating" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400 sm:sr-only">Rating</label>
            <select name="rating" id="complaint_rating" class="tw-select w-full py-2 text-xs sm:w-auto" onchange="this.form.submit()" aria-label="Filter complaints by severity">
                <option value="" {{ $ratingFilter === null ? 'selected' : '' }}>All stars</option>
                <option value="1" {{ $ratingFilter === 1 ? 'selected' : '' }}>1 Star ({{ $star1Count }})</option>
                <option value="2" {{ $ratingFilter === 2 ? 'selected' : '' }}>2 Stars ({{ $star2Count }})</option>
            </select>
        </div>
        <div class="flex w-full flex-wrap items-end gap-2 sm:ml-auto sm:w-auto">
            <div class="tw-input-group">
                <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by reference #"
                       class="tw-input w-full py-2 sm:w-56" aria-label="Search complaint by reference number"
                       data-complaint-live-search>
            </div>
            @if ($search !== '')
                <a href="{{ route($routePrefix . '.complaints', array_filter(['source' => $presidentMode ? null : $source, 'filter' => $filter, 'rating' => $ratingFilter], fn ($v) => $v !== null && $v !== '')) }}"
                   class="tw-btn tw-btn-sm tw-btn-ghost" title="Clear search">
                    <i class="bi bi-x-lg"></i><span class="hidden sm:inline">Clear</span>
                </a>
            @endif
        </div>
    </form>
</div>

@php
    if ($search !== '') {
        $emptyTitle = 'No complaints found';
        $emptyMsg = 'Nothing matches "' . $search . '". Try the full reference number, e.g. TFR-2026-0001.';
        if ($filter !== 'all') {
            $emptyMsg = 'No ' . $filter . ' complaint matches "' . $search . '". Try the full reference number, e.g. TFR-2026-0001.';
        }
    } else {
        $emptyTitle = 'No Complaints';
        $emptyMsg = 'All operators are doing great! No complaints filed.';
        if ($filter === 'open') { $emptyTitle = 'No Open Complaints'; $emptyMsg = 'Nothing is pending or under review right now. Everything is closed out.'; }
        elseif ($filter === 'rejected') { $emptyTitle = 'No Rejected Complaints'; $emptyMsg = 'Complaints rejected after review will appear here.'; }
        elseif ($filter === 'solved') { $emptyTitle = 'No Solved Complaints'; $emptyMsg = 'Complaints you mark as solved will appear here.'; }

        if (!$presidentMode && in_array($filter, ['', 'all', 'open'], true)) {
            if ($source === 'forwarded') { $emptyTitle = 'No Forwarded Complaints'; $emptyMsg = 'No TODA president has escalated a complaint to the TFRB yet.'; }
            elseif ($source === 'against_president') { $emptyTitle = 'No Complaints Against Presidents'; $emptyMsg = 'No complaint has been filed against a TODA president.'; }
            elseif ($source === 'unassigned') { $emptyTitle = 'No Unassigned Complaints'; $emptyMsg = 'No complaint has come from an unassigned operator.'; }
            elseif ($source === 'awaiting') { $emptyTitle = 'Nothing Awaiting a President'; $emptyMsg = 'Every routed complaint has already been handled or forwarded.'; }
            elseif ($source === 'actionable') { $emptyTitle = 'Nothing to Act On'; $emptyMsg = 'No forwarded or direct complaints are waiting for the TFRB right now.'; }
        }
    }
@endphp

@forelse ($complaints as $rating)
    @include('partials.admin.complaint-card', ['rating' => $rating, 'routePrefix' => $routePrefix, 'actor' => $actor, 'readOnly' => $readOnly ?? false])
@empty
    <div class="tw-empty py-16">
        <div class="tw-empty-icon"><i class="bi bi-check-circle text-emerald-500"></i></div>
        <h3 class="tw-card-title mb-1">{{ $emptyTitle }}</h3>
        <p class="text-sm text-slate-500">{{ $emptyMsg }}</p>
    </div>
@endforelse

@if ($complaints->hasPages())
    <div class="mt-4">
        {{ $complaints->links('pagination::tailwind') }}
    </div>
@endif

<script>
    (function () {
        var input = document.querySelector('input[data-complaint-live-search]');
        if (input && input.form) {
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    input.form.submit();
                }
            });
        }
    })();
</script>

<script>
    (function () {
        var board = document.querySelector('[data-source-tablist]');
        if (!board) return;
        var pollUrl = board.getAttribute('data-source-poll-url');
        if (!pollUrl) return;

        function refreshSourceCounts() {
            fetch(pollUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (res) { return res.ok ? res.json() : null; })
                .then(function (data) {
                    if (!data || !data.sourceCounts) return;
                    document.querySelectorAll('[data-source-tablist] [data-source]').forEach(function (el) {
                        var badge = el.querySelector('[data-source-count]');
                        if (badge) {
                            badge.textContent = String(data.sourceCounts[el.getAttribute('data-source')] ?? 0);
                        }
                    });
                })
                .catch(function () { /* polling is best-effort */ });
        }

        var pollTimer = null;
        function startPolling() {
            if (!pollTimer && !document.hidden) pollTimer = setInterval(refreshSourceCounts, 15000);
        }
        function stopPolling() {
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        }
        refreshSourceCounts();
        startPolling();
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) stopPolling(); else { refreshSourceCounts(); startPolling(); }
        });
    })();
</script>

{{-- Shared resolution modal --}}
<div id="complaintResolutionModal" class="tw-modal-backdrop hidden" data-complaint-resolution-modal>
    <div class="tw-modal" role="dialog" aria-modal="true" aria-labelledby="complaintResolutionTitle">
        <div class="tw-modal-head">
            <h3 id="complaintResolutionTitle" class="text-base font-bold text-slate-800" data-resolution-title>Resolve Complaint</h3>
            <button type="button" class="tw-modal-close" data-resolution-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="complaintResolutionForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <div class="tw-modal-body">
                <p class="mb-3 text-sm leading-relaxed text-slate-500" data-resolution-help></p>
                <label class="tw-label" for="resolutionNote" data-resolution-note-label>Resolution details</label>
                <textarea id="resolutionNote" name="resolution_note" rows="4" required maxlength="2000" class="tw-textarea" placeholder="Describe the resolution..."></textarea>

                <div class="mt-3 hidden" data-resolution-proof-wrap>
                    <label class="tw-label" for="resolutionProofs">Resolution proof (optional)</label>
                    <input type="file" id="resolutionProofs" name="resolution_proofs[]" multiple
                           accept="image/*,video/*,.pdf,.doc,.docx" class="tw-input">
                    <p class="mt-1 text-xs text-slate-400">Attach evidence that the complaint was resolved (photos, documents, up to 5 files).</p>
                </div>

                <div class="mt-3 hidden" data-resolution-checklist-wrap>
                    <label class="tw-label" for="escalationChecklist">Panel follow-up checklist (optional)</label>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2" id="escalationChecklistList">
                        <div class="grid gap-1 sm:grid-cols-2">
                            @foreach (\App\Models\Rating::PANEL_ESCALATION_CHECKLIST as $checklistItem)
                                <label class="flex cursor-pointer items-start gap-2 rounded-md px-2 py-1 transition hover:bg-white">
                                    <input type="checkbox" name="escalation_checklist[]" value="{{ $checklistItem['key'] }}" class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-navy-600 focus:ring-navy-500">
                                    <span class="text-xs leading-snug text-slate-700">{{ $checklistItem['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed text-slate-400">Follow-up questions the professional panel should assess. Stored with the resolution.</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                <button type="button" class="tw-btn tw-btn-sm tw-btn-outline" data-resolution-close>Cancel</button>
                <button type="submit" class="tw-btn tw-btn-sm tw-btn-navy" data-resolution-submit>Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var modal = document.querySelector('[data-complaint-resolution-modal]');
        if (!modal) return;

        var form = document.getElementById('complaintResolutionForm');
        var title = modal.querySelector('[data-resolution-title]');
        var help = modal.querySelector('[data-resolution-help]');
        var noteLabel = modal.querySelector('[data-resolution-note-label]');
        var note = document.getElementById('resolutionNote');
        var proofWrap = modal.querySelector('[data-resolution-proof-wrap]');
        var proofInput = document.getElementById('resolutionProofs');
        var checklistWrap = modal.querySelector('[data-resolution-checklist-wrap]');
         var submit = modal.querySelector('[data-resolution-submit]');
         var lastTrigger = null;

         function open(mode, action, ref) {
             lastTrigger = document.activeElement;
            form.setAttribute('action', action);
            note.value = '';
            if (proofInput) { proofInput.value = ''; }
            var solved = mode === 'solve';
            title.textContent = solved ? 'Mark Complaint Solved' : 'Reject Complaint';
            noteLabel.textContent = solved ? 'Resolution details' : 'Reason for rejection';
            help.textContent = solved
                ? 'Explain how complaint ' + ref + ' was resolved. The passenger will see this and any proof you attach.'
                : 'Explain why complaint ' + ref + ' is being rejected. The passenger will see this reason.';
            proofWrap.classList.toggle('hidden', !solved);
            checklistWrap?.classList.toggle('hidden', !solved);
            if (submit) { submit.textContent = solved ? 'Mark Solved' : 'Reject'; }
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            note.focus();
        }

         function close() {
             modal.classList.add('hidden');
             document.body.style.overflow = '';
             if (lastTrigger && typeof lastTrigger.focus === 'function') lastTrigger.focus();
             lastTrigger = null;
        }

        document.addEventListener('click', function (e) {
            var trigger = e.target && e.target.closest ? e.target.closest('[data-complaint-resolution]') : null;
            if (trigger) {
                e.preventDefault();
                open(trigger.getAttribute('data-mode'), trigger.getAttribute('data-action'), trigger.getAttribute('data-ref'));
                return;
            }
            if (e.target && e.target.closest && e.target.closest('[data-resolution-close]')) {
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

         form.addEventListener('submit', function () {
             if (!submit) return;
             submit.disabled = true;
             submit.innerHTML = '<i class="bi bi-arrow-repeat animate-spin" aria-hidden="true"></i> Saving...';
         });
     })();
</script>
