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

    /** @var string 'accepted' | 'rejected' | 'reviewed' | 'solved' | 'submitted' | 'forwarded' | 'escalated' */
    public string $status;

    public function __construct(Rating $rating, string $status)
    {
        $this->rating = $rating;
        $this->status = $status;
    }

    public function build()
    {
        $subject = match ($this->status) {
            'accepted' => "Your complaint {$this->rating->reference_number} has been reviewed",
            'reviewed' => "Your complaint {$this->rating->reference_number} has been reviewed",
            'rejected' => "Update on your complaint {$this->rating->reference_number}",
            'solved' => "Your complaint {$this->rating->reference_number} has been solved",
            'forwarded' => "Your complaint {$this->rating->reference_number} is now with the TFRB",
            'escalated' => "Your complaint {$this->rating->reference_number} was escalated for a heavier re-review",
            default => "Your complaint {$this->rating->reference_number} has been updated",
        };

        return $this
            ->subject($subject)
            ->view('emails.complaint-status');
    }
}