<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

/**
 * Verification email that carries a one-time code instead of a link.
 *
 * Link-based verification emails are frequently flagged as spam because they
 * contain a URL to a shared hosting domain. A short numeric code has a much
 * lower spam score and is the format Gmail users expect for OTP-style messages.
 */
class VerifyEmailCode extends TransactionalMailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $code;

    public int $expiresInMinutes;

    public function __construct(User $user, string $code, int $expiresInMinutes = 10)
    {
        $this->user = $user;
        $this->code = $code;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    public function build()
    {
        return $this
            ->subject('Your TriFair verification code')
            ->view('emails.verify-email-code')
            ->text('emails.verify-email-code-text')
            ->transactional();
    }
}
