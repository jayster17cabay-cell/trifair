<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TriFair — Complaint Update</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f1f5f9;margin:0;padding:0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
                    <tr>
                        <td style="background:#0a2a5e;padding:22px 32px;">
                            <span style="color:#ffffff;font-size:20px;font-weight:700;">Tri<span style="color:#f5b301;">Fair</span></span>
                            <span style="color:#94a3b8;font-size:12px;float:right;line-height:24px;">Complaint Update</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 32px;">
                            @php
                                $statusLabel = $status === 'solved' ? 'Solved' : ($status === 'submitted' ? 'Received' : 'Reviewed');
                                $statusVerb = $status === 'solved' ? 'solved' : ($status === 'submitted' ? 'received' : 'reviewed');
                                $statusColor = $status === 'solved' ? '#059669' : '#0f172a';
                            @endphp
                            <h1 style="margin:0 0 10px;font-size:18px;color:#0f172a;">
                                Your complaint {{ $rating->reference_number }} has been {{ $statusVerb }}
                            </h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#334155;">
                                Good day{{ $rating->passenger_name ? ', ' . $rating->passenger_name : '' }}! This is an update
                                on the complaint you filed against operator
                                <strong>{{ $rating->operator->user->name ?? 'Unknown' }}</strong>
                                @if ($rating->operator && $rating->operator->body_number)
                                    (B#{{ $rating->operator->body_number }})
                                @endif
                                for <strong>{{ $rating->complaint_type }}</strong>.
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                                <tr>
                                    <td style="padding:14px 18px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;padding:4px 0;">Complaint Reference</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:15px;font-weight:700;color:#0a2a5e;padding:0 0 10px;">{{ $rating->reference_number }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;padding:4px 0;">Complaint Type</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:15px;font-weight:700;color:#0f172a;padding:0 0 10px;">{{ $rating->complaint_type ?: 'Complaint' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:13px;color:#64748b;padding:4px 0;">Current Status</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:15px;font-weight:700;color:{{ $statusColor }};padding:0;">
                                                    {{ $statusLabel }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:18px 0 0;font-size:13px;line-height:1.6;color:#475569;">
                                @if ($status === 'solved')
                                    The concerning parties have been notified and the matter has been resolved. Thank you for
                                    helping us keep our transport services safe and accountable.
                                @elseif ($status === 'submitted')
                                    We have received your complaint. A TFRB officer will review it shortly, and you will
                                    receive a notification here once it is reviewed or resolved. Keep this reference number
                                    for tracking your complaint.
                                @else
                                    Your complaint has been reviewed by the TFRB. You will receive another notification once
                                    the complaint is resolved. Thank you for your patience.
                                @endif
                            </p>
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