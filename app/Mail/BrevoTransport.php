<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Swift_Events_EventListener;
use Swift_Mime_SimpleMessage;
use Swift_MimePart;
use Swift_Transport;
use Swift_TransportException;

class BrevoTransport implements Swift_Transport
{
    protected $apiKey;

    protected $started = false;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function isStarted()
    {
        return $this->started;
    }

    public function start()
    {
        $this->started = true;
    }

    public function stop()
    {
        $this->started = false;
    }

    public function ping()
    {
        return true;
    }

    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
    }

    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $failedRecipients = [];

        $to = $this->addresses($message->getTo());
        if (empty($to)) {
            return 0;
        }

        [$html, $text] = $this->bodies($message);

        $payload = [
            'sender' => [
                'email' => config('mail.from.address'),
                'name' => config('mail.from.name') ?: 'TriFair',
            ],
            'to' => $to,
            'subject' => $message->getSubject(),
        ];

        if (! empty($html)) {
            $payload['htmlContent'] = $html;
        }
        if (! empty($text)) {
            $payload['textContent'] = $text;
        }
        if ($cc = $this->addresses($message->getCc())) {
            $payload['cc'] = $cc;
        }
        if ($bcc = $this->addresses($message->getBcc())) {
            $payload['bcc'] = $bcc;
        }
        if ($replyTo = $this->addresses($message->getReplyTo())) {
            $payload['replyTo'] = $replyTo;
        }

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', $payload);

        if ($response->failed()) {
            $failedRecipients = $to;
            throw new Swift_TransportException('Brevo API error ' . $response->status() . ': ' . $response->body());
        }

        return count($to);
    }

    protected function addresses($list): array
    {
        $out = [];
        foreach ((array) $list as $email => $name) {
            if (is_int($email)) {
                $email = $name;
                $name = null;
            }
            $out[] = ['email' => $email, 'name' => $name ?: null];
        }

        return $out;
    }

    protected function bodies(Swift_Mime_SimpleMessage $message): array
    {
        $html = null;
        $text = null;

        foreach ($message->getChildren() as $child) {
            if (! $child instanceof Swift_MimePart) {
                continue;
            }
            $type = strtolower((string) $child->getContentType());
            if (strpos($type, 'text/html') !== false) {
                $html = $child->getBody();
            } elseif (strpos($type, 'text/plain') !== false) {
                $text = $child->getBody();
            }
        }

        if ($html === null && $text === null) {
            $type = strtolower((string) $message->getContentType());
            if (strpos($type, 'text/html') !== false) {
                $html = $message->getBody();
            } else {
                $text = $message->getBody();
            }
        }

        return [$html, $text];
    }
}
