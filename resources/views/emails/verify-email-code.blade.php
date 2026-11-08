<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TriFair verification code</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#ffffff;margin:0;padding:0;color:#1f2937;">
    <div style="max-width:480px;margin:0 auto;padding:24px 20px;">
        <p style="margin:0 0 16px;font-size:15px;">Hi {{ $user->nameParts()['first'] ?: $user->name }},</p>

        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
            Your TriFair verification code is:
        </p>

        <p style="margin:0 0 16px;font-size:32px;font-weight:700;letter-spacing:8px;color:#0a2a5e;text-align:center;">
            {{ $code }}
        </p>

        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
            Enter this code on the TriFair website to confirm your email address.
            It expires in {{ $expiresInMinutes }} minutes.
        </p>

        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
            If you did not create a TriFair account, you can safely ignore this message.
        </p>

        <p style="margin:24px 0 0;font-size:13px;color:#94a3b8;">
            — The TriFair Team
        </p>
    </div>
</body>
</html>
