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

        // Prefer the Gmail API whenever it is fully configured. It sends as the
        // real Gmail mailbox, so SPF/DKIM/DMARC align and Gmail does not treat
        // the message as a spoofed @gmail.com sender. This is the only transport
        // that fixes spam placement without a custom domain.
        if (self::gmailApiConfigured()) {
            return 'gmail-api';
        }

        // Outside production, keep whatever the app configured. The test suite
        // relies on the "array" transport, so it must never be overridden.
        if (!app()->environment('production')) {
            return $default !== '' && $default !== 'gmail-api' ? $default : 'sendinblue';
        }

        // Production guard: the "log" transport (and a missing value) silently
        // discards mail. Only in that broken state do we fall back to Brevo, so
        // a real, explicitly configured mailer is always honoured.
        if ($default === '' || $default === 'log' || $default === 'gmail-api') {
            return filled(config('services.sendinblue.key')) ? 'sendinblue' : $default;
        }

        return $default;
    }

    private static function gmailApiConfigured(): bool
    {
        foreach (['client_id', 'client_secret', 'refresh_token', 'sender'] as $key) {
            if (!filled(config("services.gmail_api.{$key}"))) {
                return false;
            }
        }

        return true;
    }
}
