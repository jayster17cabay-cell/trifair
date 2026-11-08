TriFair account confirmation

Hi {{ $user->nameParts()['first'] ?: $user->name }},

Thanks for creating a TriFair account. To finish setting it up, please confirm that
{{ $user->getEmailForVerification() }} is your email address by opening the link below.

{{ $verificationUrl }}

This link expires in {{ $expiresInMinutes }} minutes. If it has expired, you can request
a new one from the TriFair website.

If you did not create a TriFair account, you can safely ignore this message.

— The TriFair Team
This is an automated message. Please do not reply.
