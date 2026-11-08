<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * Base class for every user-facing TriFair email.
 *
 * Adds the headers that mailbox providers use to recognise legitimate
 * transactional messages (as opposed to bulk/marketing mail) and points
 * replies at the monitored support mailbox configured in mail.reply_to.
 */
abstract class TransactionalMailable extends Mailable
{
    protected function transactional(): static
    {
        $replyTo = trim((string) config('mail.reply_to.address'));

        if ($replyTo !== '') {
            $this->replyTo($replyTo, (string) config('mail.reply_to.name'));
        }

        return $this->withSwiftMessage(function (\Swift_Message $message) {
            $headers = $message->getHeaders();
            $headers->addTextHeader('Auto-Submitted', 'auto-generated');
            $headers->addTextHeader('X-Auto-Response-Suppress', 'All');
            $headers->addTextHeader('X-Mailer', 'TriFair');
        });
    }
}
