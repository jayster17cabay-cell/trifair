<?php

namespace App\Mail\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Mail\Transport\Transport;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Swift_Mime_SimpleMessage;

/**
 * Sends mail through the Gmail API using the owner's Gmail account.
 *
 * Why this exists: Render's free tier blocks outbound SMTP (25/465/587), so the
 * app has to use an HTTP API. Brevo works, but when the From address is a
 * @gmail.com mailbox it cannot be authenticated (SPF/DKIM/DMARC do not align
 * with Brevo's sending domain), so Gmail reliably files those messages under
 * Spam. The Gmail API sends as the real mailbox over HTTPS, so the message is
 * genuinely from Gmail and authentication passes without buying a domain.
 *
 * Credentials come from a one-time OAuth consent for the account in
 * GMAIL_API_SENDER. See .env.example for the setup steps.
 */
class GmailApiTransport extends Transport
{
    private ClientInterface $client;

    private string $clientId;

    private string $clientSecret;

    private string $refreshToken;

    private string $sender;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $refreshToken,
        string $sender,
        ClientInterface $client = null
    ) {
        $this->clientId = trim($clientId);
        $this->clientSecret = trim($clientSecret);
        $this->refreshToken = trim($refreshToken);
        $this->sender = strtolower(trim($sender));
        $this->client = $client ?: new Client(['timeout' => 30]);
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $from = $message->getFrom();
        $fromAddress = is_array($from) ? (string) array_key_first($from) : (string) $from;

        // Gmail only accepts mail sent as the authenticated mailbox (or one of
        // its verified aliases). Failing loudly is safer than silently sending
        // as a different identity, which is exactly what gets mail sent to spam.
        if ($fromAddress !== '' && strcasecmp($fromAddress, $this->sender) !== 0) {
            throw new RuntimeException(
                "Gmail API sender mismatch: From [{$fromAddress}] must match GMAIL_API_SENDER [{$this->sender}]."
            );
        }

        $raw = rtrim(strtr(base64_encode($message->toString()), '+/', '-_'), '=');

        $this->dispatch($raw);

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    private function dispatch(string $raw): void
    {
        try {
            $this->postMessage($raw);
        } catch (RequestException $e) {
            // A cached access token can be revoked or expire early. Refresh it
            // once and retry instead of failing a user-facing notification.
            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 401) {
                Cache::forget($this->tokenCacheKey());
                $this->postMessage($raw);

                return;
            }

            throw $e;
        }
    }

    private function postMessage(string $raw): void
    {
        $this->client->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken(),
                'Accept' => 'application/json',
            ],
            'json' => ['raw' => $raw],
            'http_errors' => true,
        ]);
    }

    private function accessToken(): string
    {
        return Cache::remember($this->tokenCacheKey(), 3300, function () {
            $response = $this->client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->refreshToken,
                    'grant_type' => 'refresh_token',
                ],
                'http_errors' => true,
            ]);

            $data = json_decode((string) $response->getBody(), true);
            $token = $data['access_token'] ?? null;

            if (!is_string($token) || $token === '') {
                throw new RuntimeException('Gmail API token refresh did not return an access_token.');
            }

            return $token;
        });
    }

    private function tokenCacheKey(): string
    {
        return 'gmail-api-access-token:' . sha1($this->clientId . '|' . $this->sender);
    }
}
