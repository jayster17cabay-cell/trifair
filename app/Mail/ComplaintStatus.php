<?php

namespace App\Mail;

use App\Models\Rating;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class ComplaintStatus extends TransactionalMailable
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
            'accepted' => "TriFair complaint {$this->rating->reference_number} reviewed",
            'reviewed' => "TriFair complaint {$this->rating->reference_number} reviewed",
            'rejected' => "TriFair complaint {$this->rating->reference_number} not upheld",
            'solved' => "TriFair complaint {$this->rating->reference_number} solved",
            'forwarded' => "TriFair complaint {$this->rating->reference_number} forwarded to TFRB",
            'escalated' => "TriFair complaint {$this->rating->reference_number} escalated",
            default => "TriFair complaint {$this->rating->reference_number} update",
        };

        return $this
            ->subject($subject)
            ->view('emails.complaint-status')
            ->text('emails.complaint-status-text')
            ->transactional();
    }
}