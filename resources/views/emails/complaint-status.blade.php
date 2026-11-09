<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TriFair complaint update</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#ffffff;margin:0;padding:0;color:#1f2937;">
    <div style="max-width:520px;margin:0 auto;padding:24px 20px;">
        @php
            $statusLabel = 'Reviewed';
            $statusMessage = 'After reviewing the evidence, your complaint has been upheld by the responsible office and is now counted against the reported operator\'s record.';
            $statusColor = '#0f172a';
            if ($status === 'solved') {
                $statusLabel = 'Solved';
                $statusMessage = 'The concerning parties have been notified and the matter has been resolved.';
                $statusColor = '#059669';
            } elseif ($status === 'rejected') {
                $statusLabel = 'Not upheld';
                $statusMessage = 'The responsible office reviewed the available information and could not uphold this complaint as a violation, so it does not count against the operator\'s record. If you have additional evidence, you may file a new complaint.';
                $statusColor = '#dc2626';
            } elseif ($status === 'forwarded') {
                $statusLabel = 'Forwarded to TFRB';
                $statusMessage = 'Your complaint has been forwarded to the TFRB/superadmin for further action. They will review it from here and notify you once it is resolved.';
                $statusColor = '#0a2a5e';
            } elseif ($status === 'escalated') {
                $statusLabel = 'Escalated for re-review';
                $statusMessage = 'Your complaint was escalated for a heavier re-review. The TFRB/superadmin team will investigate further and follow up with you.';
                $statusColor = '#0a2a5e';
            }
        @endphp

        <p style="margin:0 0 18px;font-size:15px;">Hi {{ $rating->passenger_name ?: 'there' }},</p>

        <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
            This is an update about your TriFair complaint
            <strong>{{ $rating->reference_number }}</strong> against operator
            <strong>{{ $rating->operator->user->name ?? 'Unknown' }}</strong>@if ($rating->operator && $rating->operator->body_number) (B#{{ $rating->operator->body_number }})@endif
            for <strong>{{ $rating->complaint_type ?: 'Complaint' }}</strong>.
        </p>

        <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
            Current status: <strong style="color:{{ $statusColor }};">{{ $statusLabel }}</strong>
        </p>

        <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
            {{ $statusMessage }}
        </p>

        @if ($rating->resolution_note)
            <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
                {{ $status === 'rejected' ? 'Reason' : 'Resolution details' }}:
                {{ $rating->resolution_note }}
            </p>
        @endif

        @if (in_array($status, ['solved', 'rejected'], true) && $rating->reviewed_by_display)
            <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#475569;">
                {{ $status === 'solved' ? 'Solved by' : 'Reviewed by' }}
                <strong>{{ $rating->reviewed_by_display }}</strong>@if ($rating->resolution_at) on {{ $rating->resolution_at->format('M j, Y g:i A') }}@endif.
            </p>
        @endif

        @if ($status === 'solved' && $attachedProofCount > 0)
            <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#475569;">
                {{ $attachedProofCount }} resolution photo{{ $attachedProofCount === 1 ? '' : 's' }}
                {{ $attachedProofCount === 1 ? 'is' : 'are' }} attached to this email.
                You can also view {{ $attachedProofCount === 1 ? 'it' : 'them' }} under My Complaints in TriFair.
            </p>
        @endif

        @if (in_array($status, ['solved', 'rejected'], true))
            <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
                @if ($rating->canAppeal())
                    If you are not satisfied with this decision, sign in to TriFair and open My Complaints to file an appeal with the TFRB/superadmin.
                @else
                    This decision was made by the TFRB/superadmin and is final.
                @endif
            </p>
        @endif

        <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
            Thank you for helping keep our transport services safe and accountable.
        </p>

        <p style="margin:24px 0 0;font-size:13px;color:#94a3b8;">
            — The TriFair Team
        </p>
    </div>
</body>
</html>
