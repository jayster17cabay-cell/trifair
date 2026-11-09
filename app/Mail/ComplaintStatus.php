<?php

namespace App\Mail;

use App\Models\Rating;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ComplaintStatus extends TransactionalMailable
{
    use Queueable, SerializesModels;

    /**
     * Keep the message under Gmail's 25 MB limit. Phone photos are usually
     * several megabytes each, so cap the total attachment payload as well.
     */
    private const MAX_ATTACHMENT_BYTES = 8 * 1024 * 1024;

    private const MAX_TOTAL_ATTACHMENT_BYTES = 20 * 1024 * 1024;

    public Rating $rating;

    /** @var string 'accepted' | 'rejected' | 'reviewed' | 'solved' | 'submitted' | 'forwarded' | 'escalated' */
    public string $status;

    /** Number of proof files actually attached to this message. */
    public int $attachedProofCount = 0;

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

        $mail = $this
            ->subject($subject)
            ->view('emails.complaint-status')
            ->text('emails.complaint-status-text')
            ->transactional();

        if ($this->status === 'solved') {
            $this->attachResolutionProofs();
        }

        return $mail;
    }

    /**
     * Attach the officer's resolution photos as regular files. Attachments
     * avoid the remote image/link pattern that Gmail flags as spam while still
     * letting the passenger view the evidence straight from the inbox.
     */
    private function attachResolutionProofs(): void
    {
        $total = 0;
        $this->attachedProofCount = 0;

        foreach ($this->rating->resolutionProofs as $proof) {
            $contents = $proof->contents();
            if ($contents === null || $contents === '') {
                continue;
            }

            $size = strlen($contents);
            if ($size > self::MAX_ATTACHMENT_BYTES || ($total + $size) > self::MAX_TOTAL_ATTACHMENT_BYTES) {
                Log::warning('Resolution proof skipped for email: attachment too large', [
                    'proof_id' => $proof->id,
                    'rating_id' => $this->rating->id,
                    'bytes' => $size,
                ]);
                continue;
            }

            $this->attachData($contents, $proof->original_name ?: ('resolution-proof-' . $proof->id), [
                'mime' => $proof->file_type ?: 'application/octet-stream',
            ]);

            $total += $size;
            $this->attachedProofCount++;
        }
    }
}
