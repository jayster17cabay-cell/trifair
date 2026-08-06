<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Swift_Events_EventListener;
use Swift_Mime_SimpleMessage;
use Swift_MimePart;
use Swift_Transport;
use Swift_TransportException;

class MailjetTransport implements Swift_Transport
{
    protected $apiKey;

    protected $secretKey;

    protected $started = false;

    public function __construct(string $apiKey, string $secretKey)
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
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

        $messagePayload = [
            'From' => [
                'Email' => config('mail.from.address'),
                'Name' => config('mail.from.name') ?: 'TriFair',
            ],
            'To' => $to,
            'Subject' => $message->getSubject(),
        ];

        if (! empty($html)) {
            $messagePayload['HTMLPart'] = $html;
        }
        if (! empty($text)) {
            $messagePayload['TextPart'] = $text;
        }
        if ($cc = $this->addresses($message->getCc())) {
            $messagePayload['Cc'] = $cc;
        }
        if ($bcc = $this->addresses($message->getBcc())) {
            $messagePayload['Bcc'] = $bcc;
        }
        if ($replyTo = $this->addresses($message->getReplyTo())) {
            $messagePayload['ReplyTo'] = $replyTo;
        }

        $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
            ->asJson()
            ->post('https://api.mailjet.com/v3.1/send', [
                'Messages' => [$messagePayload],
            ]);

        if ($response->failed()) {
            $failedRecipients = $to;
            throw new Swift_TransportException('Mailjet API error ' . $response->status() . ': ' . $response->body());
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
            $out[] = ['Email' => $email, 'Name' => $name ?: null];
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
