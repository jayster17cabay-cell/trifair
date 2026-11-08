TriFair — Violation Report Update

Your violation report {{ $violation->reference_number }} {{ $status === 'solved' ? 'has been solved' : 'has been reviewed' }}.

Good day! This is an update on the violation report you filed against operator {{ $violation->operator->user->name ?? 'Unknown' }}{{ $violation->operator && $violation->operator->body_number ? ' (B#' . $violation->operator->body_number . ')' : '' }} for {{ $violation->violation_type }}{{ $violation->other_reason ? ' — ' . $violation->other_reason : '' }}.

Report reference: {{ $violation->reference_number }}
Violation type: {{ $violation->violation_type ?: 'Violation' }}
Current status: {{ $status === 'solved' ? 'Solved' : 'Reviewed' }}
@if ($violation->penalty_imposed)
Penalty imposed: {{ $violation->penalty_imposed }}
@endif

@if ($violation->resolution_note)
{{ $status === 'solved' ? 'Resolution details' : 'Review note' }}: {{ $violation->resolution_note }}
@elseif ($violation->review_note)
Review note: {{ $violation->review_note }}
@endif

@if ($status === 'solved')
The matter has been reviewed and resolved by the TFRB. Thank you for helping us keep our transport services safe and accountable. You can view the outcome anytime in your Passenger Dashboard.
@else
Your report has been reviewed by the TFRB. You will receive another notification once the matter is resolved. Thank you for your patience.
@endif

This is an automated email from the TriFair Passenger Feedback System. Please do not reply to this email.
