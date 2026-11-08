<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TriFair — Verify your email</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f1f5f9;margin:0;padding:0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
                    <tr>
                        <td style="background:#0a2a5e;padding:22px 32px;">
                            <span style="color:#ffffff;font-size:20px;font-weight:700;">Tri<span style="color:#f5b301;">Fair</span></span>
                            <span style="color:#94a3b8;font-size:12px;float:right;line-height:24px;">Email Verification</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 32px;">
                            <h1 style="margin:0 0 10px;font-size:18px;color:#0f172a;">
                                Hi {{ $user->nameParts()['first'] ?: $user->name }},
                            </h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#334155;">
                                Please confirm that <strong>{{ $user->getEmailForVerification() }}</strong> is your
                                TriFair email address. Click the button below to activate your account.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0;">
                                <tr>
                                    <td style="background:#f5b301;border-radius:8px;">
                                        <a href="{{ $verificationUrl }}"
                                           style="display:inline-block;padding:13px 26px;font-size:14px;font-weight:700;color:#0a2a5e;text-decoration:none;">
                                            Verify Email Address
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 10px;font-size:13px;line-height:1.6;color:#475569;">
                                This link expires in <strong>{{ $expiresInMinutes }} minutes</strong>. If the button
                                does not work, copy and paste this address into your browser:
                            </p>
                            <p style="margin:0 0 18px;font-size:12px;line-height:1.6;word-break:break-all;">
                                <a href="{{ $verificationUrl }}" style="color:#0a2a5e;text-decoration:underline;">{{ $verificationUrl }}</a>
                            </p>
                            <p style="margin:0 0 14px;font-size:13px;line-height:1.6;color:#475569;">
                                If you did not create a TriFair account, you can safely ignore this email — no further
                                action is required.
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#94a3b8;">
                                Tip: To make sure future TriFair emails reach you, add this sender to your contacts or
                                mark this message as <strong>Not spam</strong>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:16px 32px;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;color:#94a3b8;">
                                This is an automated email from the TriFair Passenger Feedback System. Please do not reply
                                to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
