{{--
    Reusable complaints list. Requires:
    - $routePrefix     string             'superadmin' | 'tfrb-officer'
    - $complaints      LengthAwarePaginator of App\Models\Rating
    - $filter          string             'pending' | 'accepted' | 'rejected' | 'solved' | 'all'
    - $ratingFilter    int|null           star rating filter: 1 | 2 | null
    - $pendingCount, $acceptedCount, $rejectedCount, $solvedCount, $totalCount int
    - $star1Count, $star2Count           int  (badges on the star chips)
    - $activeOperators Collection          active operators for export filter
    - $proofsTotal    int|null         (optional) global proof count across all complaints
--}}

@php
    $search = trim((string) ($search ?? request('search', '')));

    $statItems = [
        ['num' => $totalCount, 'label' => 'Total', 'icon' => 'bi-exclamation-circle', 'chip' => 'bg-blue-50 text-navy-600'],
        ['num' => $pendingCount, 'label' => 'Pending', 'icon' => 'bi-clock-history', 'chip' => 'bg-amber-50 text-amber-600'],
        ['num' => $acceptedCount, 'label' => 'Accepted', 'icon' => 'bi-check-circle', 'chip' => 'bg-emerald-50 text-emerald-600'],
        ['num' => $rejectedCount, 'label' => 'Rejected', 'icon' => 'bi-x-circle', 'chip' => 'bg-red-50 text-red-600'],
        ['num' => $solvedCount, 'label' => 'Solved', 'icon' => 'bi-patch-check-fill', 'chip' => 'bg-violet-50 text-violet-600'],
        ['num' => $proofsTotal ?? $complaints->sum(fn($r) => $r->proofs->count()), 'label' => 'Proofs', 'icon' => 'bi-paperclip', 'chip' => 'bg-slate-100 text-slate-500'],
    ];
@endphp

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-exclamation-triangle mr-2 text-amber-500"></i>Complaints</h1>
        <p class="tw-page-sub">Passenger complaints filed against operators</p>
    </div>
    @include('partials.admin.export-dropdown', [
        'exportRoute' => route($routePrefix . '.complaints.export'),
        'exportLabel' => 'Complaints',
        'exportIcon' => 'bi-exclamation-triangle',
        'activeOperators' => $activeOperators,
        'exportFilters' => [
            ['type' => 'select', 'name' => 'filter', 'label' => 'Status', 'options' => [
                '' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted', 'rejected' => 'Rejected', 'solved' => 'Solved',
            ], 'value' => $filter],
            ['type' => 'select', 'name' => 'rating', 'label' => 'Stars', 'options' => [
                '' => 'All stars', '1' => '1 Star', '2' => '2 Stars',
            ], 'value' => $ratingFilter ?? ''],
            ['type' => 'daterange', 'prefix' => 'date', 'from' => request('date_from'), 'to' => request('date_to')],
        ],
        'preservedParams' => array_filter(['search' => $search], fn ($v) => $v !== null && $v !== ''),
    ])
</div>

{{-- Sticky summary + filter panel: stays pinned below the topbar while the list scrolls. --}}
<div class="sticky top-[70px] z-20 -mx-4 mb-5 border-b border-slate-100 bg-white/95 px-4 pb-4 pt-3 shadow-[0_10px_30px_-20px_rgba(15,23,42,0.25)] backdrop-blur-sm sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    {{-- Summary stats --}}
    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-6">
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

    {{-- Status, severity and search filters. --}}
    <form method="GET" action="{{ route($routePrefix . '.complaints') }}" class="mt-3.5 flex flex-wrap items-center gap-3">
        <div>
            <label for="complaint_filter" class="sr-only">Status</label>
            <select name="filter" id="complaint_filter" class="tw-select py-2 text-xs" onchange="this.form.submit()" aria-label="Filter complaints by status">
                <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>All statuses ({{ $totalCount }})</option>
                <option value="pending" {{ $filter === 'pending' ? 'selected' : '' }}>Pending ({{ $pendingCount }})</option>
                <option value="accepted" {{ $filter === 'accepted' ? 'selected' : '' }}>Accepted ({{ $acceptedCount }})</option>
                <option value="rejected" {{ $filter === 'rejected' ? 'selected' : '' }}>Rejected ({{ $rejectedCount }})</option>
                <option value="solved" {{ $filter === 'solved' ? 'selected' : '' }}>Solved ({{ $solvedCount }})</option>
            </select>
        </div>
        <div>
            <label for="complaint_rating" class="sr-only">Severity</label>
            <select name="rating" id="complaint_rating" class="tw-select py-2 text-xs" onchange="this.form.submit()" aria-label="Filter complaints by severity">
                <option value="" {{ $ratingFilter === null ? 'selected' : '' }}>All stars</option>
                <option value="1" {{ $ratingFilter === 1 ? 'selected' : '' }}>1 Star ({{ $star1Count }})</option>
                <option value="2" {{ $ratingFilter === 2 ? 'selected' : '' }}>2 Stars ({{ $star2Count }})</option>
            </select>
        </div>
        <div class="ml-auto flex flex-wrap items-center gap-2">
            <div class="tw-input-group">
                <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by reference #"
                       class="tw-input py-2" style="width: min(14rem, 100%);" aria-label="Search complaint by reference number"
                       data-complaint-live-search>
            </div>
            @if ($search !== '')
                <a href="{{ route($routePrefix . '.complaints', array_filter(['filter' => $filter, 'rating' => $ratingFilter], fn ($v) => $v !== null && $v !== '')) }}"
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
        if ($filter === 'pending') { $emptyTitle = 'No Pending Complaints'; $emptyMsg = 'Nothing waiting for review. Keep it up!'; }
        elseif ($filter === 'accepted') { $emptyTitle = 'No Accepted Complaints'; $emptyMsg = 'Complaints accepted by an officer will appear here.'; }
        elseif ($filter === 'rejected') { $emptyTitle = 'No Rejected Complaints'; $emptyMsg = 'Complaints rejected after review will appear here.'; }
        elseif ($filter === 'solved') { $emptyTitle = 'No Solved Complaints'; $emptyMsg = 'Complaints you mark as solved will appear here.'; }
    }
@endphp

@forelse ($complaints as $rating)
    @include('partials.admin.complaint-card', ['rating' => $rating, 'routePrefix' => $routePrefix])
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
        if (!input || !input.form) return;
        var timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { input.form.submit(); }, 400);
        });
        input.addEventListener('keydown', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                clearTimeout(timer);
                input.form.submit();
            }
        });
    })();
</script>

{{-- Shared resolution modal opened by the Reject / Solved buttons on a card.
     The note is required here in the UI; the server keeps it optional so the
     plain reject/solve endpoints remain backwards compatible. --}}
<div id="complaintResolutionModal" class="tw-modal-backdrop hidden" data-complaint-resolution-modal>
    <div class="tw-modal" role="dialog" aria-modal="true" aria-labelledby="complaintResolutionTitle">
        <div class="tw-modal-head">
            <h3 id="complaintResolutionTitle" class="text-base font-bold text-slate-800" data-resolution-title>Review Complaint</h3>
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
        var submit = modal.querySelector('[data-resolution-submit]');

        function open(mode, action, ref) {
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
            if (submit) { submit.textContent = solved ? 'Mark Solved' : 'Reject'; }
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            note.focus();
        }

        function close() {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
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
    })();
</script>