{{-- Shared complaints page body. Requires: $routePrefix (e.g. 'superadmin' | 'tfrb-officer' | 'president'), $complaints, $filter, $pendingCount, $reviewedCount, $rejectedCount, $solvedCount, $totalCount. Pass $presidentMode=true + $activeOperators=absent to render the president's queue. --}}

@php
    $presidentMode = $presidentMode ?? false;
    // When the user is a TODA president, act as 'president' (shows the
    // Forward to TFRB button). $routePrefix alone is not reliable because
    // the staff queue also uses the same outer key.
    $actor = $actor ?? (auth()->check() && auth()->user()->role === 'operator_president' ? 'president' : 'staff');
@endphp
@include('partials.admin.complaint-list')
