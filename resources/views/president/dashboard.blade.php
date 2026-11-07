@extends('layouts.president')

@section('title', 'TODA President Dashboard')

@section('content')
@php
    // Sidebar-driven view filtering: picking a section in the sidebar shows only
    // that block on the dashboard. No section (= Overview) shows everything.
    $section = request()->query('section');
    $showAll = !$section || !in_array($section, ['ratings', 'join-requests', 'members'], true);
    $showRatings = $showAll || $section === 'ratings';
    $showJoin = $showAll || $section === 'join-requests';
    $showMembers = $showAll || $section === 'members';

    $membersActive = ($membersActive ?? false)
        || request()->has('search')
        || request()->has('status')
        || $section === 'members';
@endphp

{{-- Primary hero action: an open passenger complaint is the most urgent thing
     a president owns, so it wins the hero; pending join requests come next,
     then the members list. --}}
@php
    $heroJoinCount = ($joinRequests ?? collect())->count();
    $heroComplaintCount = (int) ($summary['pendingComplaints'] ?? 0);
    if ($heroComplaintCount > 0) {
        $heroActionLabel = $heroComplaintCount . ' Pending ' . ($heroComplaintCount === 1 ? 'Complaint' : 'Complaints');
        $heroActionHref = route('president.complaints', ['filter' => 'pending']);
        $heroActionIcon = 'bi-flag-fill';
    } elseif ($heroJoinCount > 0) {
        $heroActionLabel = $heroJoinCount . ' Pending Join ' . ($heroJoinCount === 1 ? 'Request' : 'Requests');
        $heroActionHref = route('president.dashboard', ['section' => 'join-requests']);
        $heroActionIcon = 'bi-box-arrow-in-down';
    } else {
        $heroActionLabel = 'View Members';
        $heroActionHref = route('president.dashboard', ['section' => 'members']);
        $heroActionIcon = 'bi-people';
    }
@endphp
@include('partials.dashboard-hero', [
    'eyebrow' => 'TriFair TODA President',
    'subtitle' => $summary['todaArea'] ? ($summary['todaArea'] . ' · ' . $summary['totalMembers'] . ' members in ' . $toda->name) : ($summary['totalMembers'] . ($summary['totalMembers'] === 1 ? ' member' : ' members') . ' in ' . $toda->name),
    'actionHref' => $heroActionHref,
    'actionLabel' => $heroActionLabel,
    'actionIcon' => $heroActionIcon,
])

@if (($ownOperator ?? null) && $ownOperator->isFranchiseExpiringSoon())
<div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    <i class="bi bi-exclamation-triangle-fill mr-1"></i>
    <strong>Expiring Soon:</strong> Your franchise expires in {{ $ownOperator->currentTerm?->daysUntilExpiry() }} days. Please contact the TFRB Office to renew it before it lapses.
</div>
@endif
{{-- KPI cards --}}
<div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('president.complaints', ['filter' => 'pending']), 'icon' => 'bi-flag-fill', 'value' => $summary['pendingComplaints'], 'label' => 'Pending Complaints', 'live' => 'pendingComplaints']])
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('president.dashboard', ['section' => 'ratings']), 'icon' => 'bi-star-fill', 'value' => number_format($summary['ownAvg'], 1), 'label' => 'My Rating', 'live' => 'ownAvg']])
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('president.dashboard', ['section' => 'members']), 'icon' => 'bi-people', 'value' => $summary['totalMembers'], 'label' => 'Members']])
    {{-- Member-level averages and complaints are reviewed per member, so both
         cards deep-link into the members section (same table the summary counts
         are drawn from) instead of being dead panels. --}}
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('president.dashboard', ['section' => 'members']), 'icon' => 'bi-star-half', 'value' => number_format($summary['avgMemberRating'], 1), 'label' => 'Avg Rating']])
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('president.dashboard', ['section' => 'members']), 'icon' => 'bi-flag', 'value' => $summary['totalComplaints'], 'label' => 'Complaints', 'live' => 'totalComplaints']])
</div>

{{-- Section 1 — My Ratings (the president's own performance as an operator).
     Member-level ratings are intentionally omitted here; the president reviews
     them per member from the TODA members list below. --}}
@if ($showRatings)
<div id="ownRatings" class="mb-4 scroll-mt-24">
    @include('partials.president.own-ratings', ['ownOperator' => $ownOperator, 'ownRecentRatings' => $ownRecentRatings])
</div>
@endif

{{-- Join requests: operators asking to join this TODA. The president is the
     final approver — approving activates the membership immediately. --}}
@if ($showJoin && ($joinRequests ?? collect())->isNotEmpty())
    <div id="joinRequestsSection" class="scroll-mt-24">
        @include('partials.president.join-requests', ['joinRequests' => $joinRequests])
    </div>
@endif

{{-- Section 2 — Members --}}
@if ($showMembers)
<div id="membersSection" class="mb-4 scroll-mt-24 tw-card overflow-hidden">
    <div class="flex flex-col gap-2 px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between" style="background-image: linear-gradient(135deg, #071e42 0%, #0A2A5E 100%);">
        <div class="flex items-center">
            <button id="presidentMembersToggle" type="button" class="tw-btn tw-btn-gold px-3 py-1.5 text-sm" onclick="togglePresidentMembers()">
                <i class="bi bi-people-fill mr-1"></i><span id="presidentMembersToggleLabel">TODA Members</span>
                <span class="tw-badge tw-badge-amber ml-1.5 px-1.5">{{ ($members ?? collect())->total() }}</span>
                <i id="presidentMembersToggleChevron" class="bi {{ $membersActive ? 'bi-chevron-up' : 'bi-chevron-down' }} ml-1"></i>
            </button>
        </div>
        <div class="flex flex-col items-stretch gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
            <form id="presidentMemberFilter" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center" method="GET" action="{{ route('president.dashboard') }}">
                <input type="text" id="presidentMemberSearch" name="search" value="{{ request('search') }}" placeholder="Search name, body #, plate…" aria-label="Search members by name, body number, or plate"
                       class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 placeholder-slate-400 focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold sm:w-56">
                <select id="presidentMemberStatus" name="status" aria-label="Filter members by status" class="h-9 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold">
                    <option value="">All status</option>
                    @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended', 'pending' => 'Pending', 'rejected' => 'Rejected'] as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
            <button type="button" class="tw-btn tw-btn-gold h-9 whitespace-nowrap px-3 text-xs" data-tw-modal-open="presidentAddMemberModal">
                <i class="bi bi-person-plus-fill mr-1"></i> Add Member
            </button>
        </div>
    </div>

    <div id="presidentMembersWrap" class="{{ $membersActive ? '' : 'hidden' }}">
        @include('partials.president.members-table', ['members' => $members, 'memberDetailUrl' => route('president.members')])
    </div>
</div>
@endif

<div id="presidentMemberModal" class="tw-modal-backdrop hidden">
    <div class="tw-modal" role="dialog" aria-modal="true" aria-labelledby="presidentMemberModalBody">
        <div id="presidentMemberModalBody"><!-- populated by AJAX --></div>
    </div>
</div>

@include('partials.admin.operator-suspend-modal', ['suspendTitle' => 'Suspend Member'])
@include('partials.president.add-member-modal')

<script>
    function togglePresidentMembers() {
        var wrap = document.getElementById('presidentMembersWrap');
        var chevron = document.getElementById('presidentMembersToggleChevron');
        var label = document.getElementById('presidentMembersToggleLabel');
        if (!wrap) return;
        var hidden = wrap.classList.contains('hidden');
        wrap.classList.toggle('hidden');
        if (chevron) {
            chevron.classList.toggle('bi-chevron-down', hidden);
            chevron.classList.toggle('bi-chevron-up', !hidden);
        }
        if (label) {
            label.textContent = hidden ? 'Hide Members' : 'TODA Members';
        }
        if (hidden) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    (function () {
        var form = document.getElementById('presidentMemberFilter');
        var searchInput = document.getElementById('presidentMemberSearch');
        var statusSelect = document.getElementById('presidentMemberStatus');
        if (!form || !searchInput || !statusSelect) return;
        // Search on Enter only: typing must never reload the list on its own.
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                form.submit();
            }
        });
        // The status dropdown is a filter, so it can still apply immediately.
        statusSelect.addEventListener('change', function () {
            form.submit();
        });
    })();

    @if ($membersActive)
        document.addEventListener('DOMContentLoaded', function () {
            var wrap = document.getElementById('presidentMembersWrap');
            if (wrap) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    @endif

    @if ($errors->any() && (old('plate_number') || old('body_number') || old('motorcycle_model')))
        // A failed Add Member submission bounces back here — reopen the modal so
        // the president can see the errors and correct the form.
        document.addEventListener('DOMContentLoaded', function () {
            var wrap = document.getElementById('presidentMembersWrap');
            if (wrap) wrap.classList.remove('hidden');
            var modal = document.getElementById('presidentAddMemberModal');
            if (modal) { modal.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
        });
    @endif
</script>

@include('partials.dashboard-live')
@endsection
