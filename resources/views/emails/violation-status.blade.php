<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TriFair — Violation Report Update</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f1f5f9;margin:0;padding:0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
                    <tr>
                        <td style="background:#0a2a5e;padding:22px 32px;">
                            <span style="color:#ffffff;font-size:20px;font-weight:700;">Tri<span style="color:#f5b301;">Fair</span></span>
                            <span style="color:#94a3b8;font-size:12px;float:right;line-height:24px;">Violation Report Update</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 32px;">
                            @php
                                $isSolved = $status === 'solved';
                                $heading = $isSolved ? 'has been solved' : 'has been reviewed';
                                $statusColor = $isSolved ? '#059669' : '#0a2a5e';
                                $statusLabel = $isSolved ? 'Solved' : 'Reviewed';
                            @endphp
                            <h1 style="margin:0 0 10px;font-size:18px;color:#0f172a;">
                                Your violation report {{ $violation->reference_number }} {{ $heading }}
                            </h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#334155;">
                                Good day! This is an update on the violation report you filed against operator
                                <strong>{{ $violation->operator->user->name ?? 'Unknown' }}</strong>
                                @if ($violation->operator && $violation->operator->body_number)
                                    (B#{{ $violation->operator->body_number }})
                                @endif
                                for <strong>{{ $violation->violation_type }}</strong>@if ($violation->other_reason) — {{ $violation->other_reason }}@endif.
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                                <tr>
                                    <td style="padding:14px 18px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;padding:4px 0;">Report Reference</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:15px;font-weight:700;color:#0a2a5e;padding:0 0 10px;">{{ $violation->reference_number }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;padding:4px 0;">Violation Type</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:15px;font-weight:700;color:#0f172a;padding:0 0 10px;">{{ $violation->violation_type ?: 'Violation' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;padding:4px 0;">Current Status</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:15px;font-weight:700;color:{{ $statusColor }};padding:0;">
                                                    {{ $statusLabel }}
                                                </td>
                                            </tr>
                                            @if ($violation->penalty_imposed)
                                                <tr>
                                                    <td style="font-size:13px;color:#64748b;padding:10px 0 4px;">Penalty Imposed</td>
                                                </tr>
                                                <tr>
                                                    <td style="font-size:15px;font-weight:700;color:#b91c1c;padding:0;">{{ $violation->penalty_imposed }}</td>
                                                </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:18px 0 0;font-size:13px;line-height:1.6;color:#475569;">
                                @if ($isSolved)
                                    The matter has been reviewed and resolved by the TFRB. Thank you for helping us keep
                                    our transport services safe and accountable. You can view the outcome anytime in your
                                    Passenger Dashboard.
                                @else
                                    Your report has been reviewed by the TFRB. You will receive another notification once
                                    the matter is resolved. Thank you for your patience.
                                @endif
                            </p>
                            @if ($violation->resolution_note || $isSolved)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;margin-top:18px;">
                                    <tr>
                                        <td style="padding:14px 18px;">
                                            <div style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#047857;padding-bottom:6px;">
                                                {{ $isSolved ? 'Resolution Details' : 'Review Note' }}
                                            </div>
                                            @if ($violation->resolution_note)
                                                <div style="font-size:14px;line-height:1.6;color:#334155;">{{ $violation->resolution_note }}</div>
                                            @elseif ($violation->review_note)
                                                <div style="font-size:14px;line-height:1.6;color:#334155;">{{ $violation->review_note }}</div>
                                            @endif
                                            @if ($violation->reviewed_by || $violation->solved_at)
                                                <div style="font-size:12px;color:#64748b;padding-top:8px;">
                                                    @if ($violation->reviewer)Reviewed by <strong>{{ $violation->reviewer->name }}</strong>@endif
                                                    @if ($violation->reviewer && $violation->solved_at) on @endif
                                                    @if ($violation->solved_at){{ $violation->solved_at->format('M j, Y g:i A') }}@endif
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:16px 32px;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;color:#94a3b8;">This is an automated email from the TriFair Passenger Feedback System. Please do not reply to this email.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
