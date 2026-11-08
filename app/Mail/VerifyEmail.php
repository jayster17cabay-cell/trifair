<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Branded email-verification message.
 *
 * Laravel's stock notification only ships an HTML body and uses whatever mailer
 * happens to be configured. This mailable adds a plain-text alternative (spam
 * filters penalise HTML-only mail), sends through the same Brevo pipeline as
 * every other TriFair notification, and carries the standard transactional
 * headers from TransactionalMailable.
 */
class VerifyEmail extends TransactionalMailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $verificationUrl;

    public int $expiresInMinutes;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->expiresInMinutes = max(1, (int) config('auth.verification.expire', 60));
        $this->verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes($this->expiresInMinutes),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );
    }

    public function build()
    {
        return $this
            ->subject('TriFair account confirmation')
            ->view('emails.verify-email')
            ->text('emails.verify-email-text')
            ->transactional();
    }
}
