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
@endphp

{{-- Primary hero action: pending join requests are the president's most urgent
     job, so surface them first; otherwise fall back to the members list. --}}
@php
    $heroJoinCount = ($joinRequests ?? collect())->count();
    if ($heroJoinCount > 0) {
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

{{-- KPI cards --}}
<div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('president.dashboard', ['section' => 'ratings']), 'icon' => 'bi-star-fill', 'value' => number_format($summary['ownAvg'], 1), 'label' => 'My Rating']])
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('president.dashboard', ['section' => 'members']), 'icon' => 'bi-people', 'value' => $summary['totalMembers'], 'label' => 'Members']])
    {{-- Member-level averages and complaints are reviewed per member, so both
         cards deep-link into the members section (same table the summary counts
         are drawn from) instead of being dead panels. --}}
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('president.dashboard', ['section' => 'members']), 'icon' => 'bi-star-half', 'value' => number_format($summary['avgMemberRating'], 1), 'label' => 'Avg Rating']])
    @include('partials.dashboard-kpi', ['kpi' => ['href' => route('president.dashboard', ['section' => 'members']), 'icon' => 'bi-flag', 'value' => $summary['totalComplaints'], 'label' => 'Complaints']])
</div>

{{-- Section 1 — My Ratings (the president's own performance as an operator).
     Member-level ratings are intentionally omitted here; the president reviews
     them per member from the TODA members list below. --}}
@if ($showRatings)
<div id="ownRatings" class="mb-4 scroll-mt-24">
    @include('partials.president.own-ratings', ['ownOperator' => $ownOperator, 'ownRecentRatings' => $ownRecentRatings])
</div>
@endif

{{-- Join requests: operators asking to join this TODA (president reviews first,
     then either the TFRB officer or the superadmin gives the final approval). --}}
@if ($showJoin && ($joinRequests ?? collect())->isNotEmpty())
    <div id="joinRequestsSection" class="scroll-mt-24">
        @include('partials.president.join-requests', ['joinRequests' => $joinRequests])
    </div>
@endif

{{-- Section 2 — Members toggle button --}}
{{-- Members must open as soon as the sidebar section is picked (or a search/status
     filter is applied) — otherwise the section shows only its header/toggle and
     the list stays hidden behind a second click. --}}
@php
    $membersActive = ($membersActive ?? false)
        || request()->has('search')
        || request()->has('status')
        || $section === 'members';
@endphp
{{-- Section 2 — Members search + toggle (search is always visible) --}}
@if ($showMembers)
<div id="membersSection" class="scroll-mt-24 tw-card mb-4 overflow-hidden">
    <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between" style="background-image: linear-gradient(135deg, #071e42 0%, #0A2A5E 100%);">
        <div class="flex items-center gap-2">
            <button id="presidentMembersToggle" type="button" class="tw-btn tw-btn-gold px-4 py-2 text-sm" onclick="togglePresidentMembers()">
                <i class="bi bi-people-fill mr-1"></i><span id="presidentMembersToggleLabel">{{ $membersActive ? 'Hide Members' : 'View Members' }}</span>
                <i id="presidentMembersToggleChevron" class="bi {{ $membersActive ? 'bi-chevron-up' : 'bi-chevron-down' }} ml-1"></i>
            </button>
        </div>
        <form id="presidentMemberFilter" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center" method="GET" action="{{ route('president.dashboard') }}">
            <input type="text" id="presidentMemberSearch" name="search" value="{{ request('search') }}" placeholder="Search name, body #, plate…" aria-label="Search members by name, body number, or plate"
                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 placeholder-slate-400 focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold sm:w-56">
            <select id="presidentMemberStatus" name="status" aria-label="Filter members by status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold">
                <option value="">All status</option>
                @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended', 'pending' => 'Pending', 'rejected' => 'Rejected'] as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

{{-- Section 4 — members list (hidden until toggled / search active), each member opens their ratings --}}
<div id="presidentMembersWrap" class="mt-3 {{ $membersActive ? '' : 'hidden' }}">
    @include('partials.president.members-section', ['members' => $members, 'memberDetailUrl' => route('president.members')])
</div>
@endif

<div id="presidentMemberModal" class="tw-modal-backdrop hidden">
    <div class="tw-modal" role="dialog" aria-modal="true" aria-labelledby="presidentMemberModalBody">
        <div id="presidentMemberModalBody"><!-- populated by AJAX --></div>
    </div>
</div>

@include('partials.admin.operator-suspend-modal', ['suspendTitle' => 'Suspend Member'])
@include('partials.president.add-member-modal')
@include('partials.president.add-existing-member-modal')

<script>
    function togglePresidentMembers() {
        var wrap = document.getElementById('presidentMembersWrap');
        var label = document.getElementById('presidentMembersToggleLabel');
        var chevron = document.getElementById('presidentMembersToggleChevron');
        var hidden = wrap.classList.contains('hidden');
        wrap.classList.toggle('hidden');
        label.textContent = hidden ? 'Hide Members' : 'View Members';
        chevron.classList.toggle('bi-chevron-down', !hidden);
        chevron.classList.toggle('bi-chevron-up', hidden);
        if (hidden) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    (function () {
        var form = document.getElementById('presidentMemberFilter');
        var searchInput = document.getElementById('presidentMemberSearch');
        var statusSelect = document.getElementById('presidentMemberStatus');
        if (!form || !searchInput || !statusSelect) return;
        var timer = null;
        var fireSearch = function () {
            clearTimeout(timer);
            timer = setTimeout(function () { form.submit(); }, 400);
        };
        searchInput.addEventListener('input', fireSearch);
        searchInput.addEventListener('keydown', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                clearTimeout(timer);
                form.submit();
            }
        });
        statusSelect.addEventListener('change', function () {
            clearTimeout(timer);
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
@endsection
