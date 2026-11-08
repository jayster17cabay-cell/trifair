<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Single entry point for application email.
 *
 * Render deployments have historically kept a stale MAIL_MAILER value in their
 * cached environment, which made Laravel silently fall back to the "log"
 * transport: the action succeeded, no email was sent, and nothing reached the
 * user's inbox. Production sends therefore always use Brevo when its API key is
 * configured; local/testing keeps the configured mailer so the array transport
 * used by the test suite is never bypassed.
 */
final class TransactionalMail
{
    public static function send(string $to, Mailable $mailable): void
    {
        Mail::mailer(self::mailer())->to($to)->send($mailable);
    }

    public static function mailer(): string
    {
        $default = trim((string) config('mail.default', ''));

        // Outside production, keep whatever the app configured. The test suite
        // relies on the "array" transport, so it must never be overridden.
        if (!app()->environment('production')) {
            return $default !== '' ? $default : 'sendinblue';
        }

        // Production guard: the "log" transport (and a missing value) silently
        // discards mail. Only in that broken state do we fall back to Brevo, so
        // a real, explicitly configured mailer (e.g. a future SMTP relay or the
        // Gmail API transport) is always honoured instead of being overridden.
        if ($default === '' || $default === 'log') {
            return filled(config('services.sendinblue.key')) ? 'sendinblue' : $default;
        }

        return $default;
    }
}
