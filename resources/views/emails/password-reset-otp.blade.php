<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TriFair — Account Recovery Code</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f1f5f9;margin:0;padding:0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
                    <tr>
                        <td style="background:#0a2a5e;padding:22px 32px;">
                            <span style="color:#ffffff;font-size:20px;font-weight:700;">Tri<span style="color:#f5b301;">Fair</span></span>
                            <span style="color:#94a3b8;font-size:12px;float:right;line-height:24px;">Account Security</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 32px;">
                            <h1 style="margin:0 0 10px;font-size:18px;color:#0f172a;">Hi {{ $user->name }},</h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#334155;">
                                We received a request to reset the password for your TriFair account
                                ({{ $user->email }}). Use the one-time code below to set a new password.
                            </p>
                            <p style="margin:0 0 20px;background:#fff8e1;border:1px solid #f5b301;border-radius:8px;padding:14px 18px;font-size:24px;font-weight:700;letter-spacing:8px;color:#0a2a5e;text-align:center;">
                                {{ $otp }}
                            </p>
                            <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#475569;">
                                This code expires in <strong>10 minutes</strong>. If you did not request this, you can
                                safely ignore this email — your password will not be changed.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:16px 32px;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;color:#94a3b8;">Never share this code with anyone. TriFair staff will never ask for it.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>