<?php

namespace App\Mail;

use App\Models\SuspensionViolation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ViolationStatus extends Mailable
{
    use Queueable, SerializesModels;

    public SuspensionViolation $violation;

    /** @var string 'reviewed' | 'solved' */
    public string $status;

    public function __construct(SuspensionViolation $violation, string $status)
    {
        $this->violation = $violation;
        $this->status = $status;
    }

    public function build()
    {
        $subject = $this->status === 'solved'
            ? "Your violation report {$this->violation->reference_number} has been solved"
            : "Your violation report {$this->violation->reference_number} has been reviewed";

        return $this
            ->subject($subject)
            ->view('emails.violation-status');
    }
}
