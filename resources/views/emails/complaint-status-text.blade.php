@php
    $statusLabel = match ($status) {
        'solved' => 'Solved',
        'rejected' => 'Rejected',
        'forwarded' => 'Forwarded to TFRB',
        'escalated' => 'Escalated for re-review',
        default => 'Reviewed',
    };
    $heading = match ($status) {
        'solved' => 'has been solved',
        'rejected' => 'was not upheld after review',
        'forwarded' => 'has been forwarded to the TFRB',
        'escalated' => 'was escalated for a heavier re-review',
        default => 'has been reviewed',
    };
@endphp
TriFair — Complaint Update

Your complaint {{ $rating->reference_number }} {{ $heading }}.

Good day{{ $rating->passenger_name ? ', ' . $rating->passenger_name : '' }}!

This is an update on the complaint you filed against operator {{ $rating->operator->user->name ?? 'Unknown' }}{{ $rating->operator && $rating->operator->body_number ? ' (B#' . $rating->operator->body_number . ')' : '' }} for {{ $rating->complaint_type }}.

Complaint reference: {{ $rating->reference_number }}
Complaint type: {{ $rating->complaint_type ?: 'Complaint' }}
Current status: {{ $statusLabel }}

@if ($rating->resolution_note)
{{ $status === 'rejected' ? 'Reason' : 'Resolution details' }}: {{ $rating->resolution_note }}
@endif

@if ($status === 'solved')
The concerning parties have been notified and the matter has been resolved. Thank you for helping us keep our transport services safe and accountable.
@elseif ($status === 'rejected')
Your complaint was reviewed by the responsible office. Based on the available information it could not be upheld as a violation, so it does not count against the operator's record. If you have additional evidence, you may file a new complaint.
@elseif ($status === 'forwarded')
Your complaint has been forwarded to the TFRB/superadmin by the TODA president for further action. They will review it from here and notify you once it is resolved.
@elseif ($status === 'escalated')
Your complaint was escalated for a heavier re-review after the previous resolution did not satisfy you. The TFRB/superadmin team will investigate further and follow up with you.
@else
After reviewing the evidence, your complaint has been upheld by the responsible office and is now counted against the reported operator's record.
@endif

You can view the full complaint in your TriFair Passenger Dashboard.

This is an automated email from the TriFair Passenger Feedback System. Please do not reply to this email.
