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

@if (in_array($status, ['solved', 'rejected'], true) && $rating->reviewed_by_display)
{{ $status === 'solved' ? 'Solved by' : 'Reviewed by' }} {{ $rating->reviewed_by_display }}@if ($rating->resolution_at) on {{ $rating->resolution_at->format('M j, Y g:i A') }}@endif.
@endif

@if ($status === 'solved' && $attachedProofCount > 0)
{{ $attachedProofCount }} resolution photo{{ $attachedProofCount === 1 ? '' : 's' }} {{ $attachedProofCount === 1 ? 'is' : 'are' }} attached to this email. You can also view {{ $attachedProofCount === 1 ? 'it' : 'them' }} under My Complaints in TriFair.
@endif

@if (in_array($status, ['solved', 'rejected'], true))
@if ($rating->canAppeal())
If you are not satisfied with this decision, sign in to TriFair and open My Complaints to file an appeal with the TFRB/superadmin.
@else
This decision was made by the TFRB/superadmin and is final.
@endif
@endif

Thank you for helping keep our transport services safe and accountable.

— The TriFair Team
