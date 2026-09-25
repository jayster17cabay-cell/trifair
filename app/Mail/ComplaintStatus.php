<?php

namespace App\Mail;

use App\Models\Rating;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ComplaintStatus extends Mailable
{
    use Queueable, SerializesModels;

    public Rating $rating;

    /** @var string 'reviewed' | 'solved' */
    public string $status;

    public function __construct(Rating $rating, string $status)
    {
        $this->rating = $rating;
        $this->status = $status;
    }

    public function build()
    {
        $subject = match ($this->status) {
            'solved' => "Your complaint {$this->rating->reference_number} has been solved",
            'submitted' => "Your complaint {$this->rating->reference_number} was received",
            default => "Your complaint {$this->rating->reference_number} has been reviewed",
        };

        return $this
            ->subject($subject)
            ->view('emails.complaint-status');
    }
}