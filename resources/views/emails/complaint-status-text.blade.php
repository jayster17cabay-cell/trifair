@php
    $statusLabel = match ($status) {
        'solved' => 'Solved',
        'rejected' => 'Not upheld',
        'forwarded' => 'Forwarded to TFRB',
        'escalated' => 'Escalated for re-review',
        default => 'Reviewed',
    };
    $statusMessage = match ($status) {
        'solved' => 'The concerning parties have been notified and the matter has been resolved.',
        'rejected' => 'The responsible office reviewed the available information and could not uphold this complaint as a violation, so it does not count against the operator\'s record. If you have additional evidence, you may file a new complaint.',
        'forwarded' => 'Your complaint has been forwarded to the TFRB/superadmin for further action. They will review it from here and notify you once it is resolved.',
        'escalated' => 'Your complaint was escalated for a heavier re-review. The TFRB/superadmin team will investigate further and follow up with you.',
        default => 'After reviewing the evidence, your complaint has been upheld by the responsible office and is now counted against the reported operator\'s record.',
    };
@endphp
TriFair complaint update

Hi {{ $rating->passenger_name ?: 'there' }},

This is an update about your TriFair complaint {{ $rating->reference_number }} against operator {{ $rating->operator->user->name ?? 'Unknown' }}{{ $rating->operator && $rating->operator->body_number ? ' (B#' . $rating->operator->body_number . ')' : '' }} for {{ $rating->complaint_type ?: 'Complaint' }}.

Current status: {{ $statusLabel }}

{{ $statusMessage }}

@if ($rating->resolution_note)
{{ $status === 'rejected' ? 'Reason' : 'Resolution details' }}: {{ $rating->resolution_note }}
@endif

@if ($status === 'solved')
Sign in to TriFair and open My Complaints to view any photos attached to the resolution.
@endif

Thank you for helping keep our transport services safe and accountable.

— The TriFair Team
