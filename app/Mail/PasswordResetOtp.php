<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtp extends TransactionalMailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $otp;

    public function __construct(User $user, string $otp)
    {
        $this->user = $user;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this
            ->subject('Your TriFair Account Recovery Code')
            ->view('emails.password-reset-otp')
            ->text('emails.password-reset-otp-text')
            ->transactional();
    }
}