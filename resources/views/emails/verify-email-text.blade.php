TriFair — Verify your email address

Hi {{ $user->nameParts()['first'] ?: $user->name }},

Please confirm that {{ $user->getEmailForVerification() }} is your TriFair email address.

Verify your account by opening this link:
{{ $verificationUrl }}

This link expires in {{ $expiresInMinutes }} minutes.

If you did not create a TriFair account, you can safely ignore this email. No further action is required.

Tip: To make sure future TriFair emails reach you, add this sender to your contacts or mark this message as "Not spam".

This is an automated message from the TriFair Passenger Feedback System. Please do not reply to this email.
