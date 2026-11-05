{{-- Shared complaints page body. Requires: $routePrefix (e.g. 'superadmin' | 'tfrb-officer' | 'president'), $complaints, $filter, $pendingCount, $reviewedCount, $rejectedCount, $solvedCount, $totalCount. Pass $presidentMode=true + $activeOperators=absent to render the president's queue. --}}

@php
    $presidentMode = $presidentMode ?? false;
    $actor = $actor ?? 'staff';
@endphp
@include('partials.admin.complaint-list')
