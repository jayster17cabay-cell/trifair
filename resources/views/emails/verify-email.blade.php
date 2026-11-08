<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TriFair account confirmation</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#ffffff;margin:0;padding:0;color:#1f2937;">
    <div style="max-width:560px;margin:0 auto;padding:24px 20px;">
        <p style="margin:0 0 16px;font-size:15px;">Hi {{ $user->nameParts()['first'] ?: $user->name }},</p>

        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
            Thanks for creating a TriFair account. To finish setting it up, please confirm that
            <strong>{{ $user->getEmailForVerification() }}</strong> is your email address by opening
            the link below.
        </p>

        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;word-break:break-all;">
            <a href="{{ $verificationUrl }}" style="color:#0a2a5e;">{{ $verificationUrl }}</a>
        </p>

        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
            This link expires in {{ $expiresInMinutes }} minutes. If it has expired, you can request
            a new one from the TriFair website.
        </p>

        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
            If you did not create a TriFair account, you can safely ignore this message.
        </p>

        <p style="margin:24px 0 0;font-size:13px;color:#94a3b8;">
            — The TriFair Team<br>
            This is an automated message. Please do not reply.
        </p>
    </div>
</body>
</html>
