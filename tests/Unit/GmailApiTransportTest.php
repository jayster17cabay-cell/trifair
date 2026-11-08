<?php

namespace Tests\Unit;

use App\Mail\Transport\GmailApiTransport;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Swift_Message;
use Tests\TestCase;

/**
 * The Gmail API transport is the no-domain path out of the spam folder, so its
 * two critical guarantees are covered here: it sends the exact MIME message
 * through users.messages.send with a refreshed OAuth token, and it refuses to
 * send as an address other than the authenticated mailbox.
 */
class GmailApiTransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::flush();
    }

    public function test_it_refreshes_a_token_and_sends_the_raw_message_through_gmail(): void
    {
        $history = [];
        $transport = $this->makeTransport($history, [
            new Response(200, [], json_encode(['access_token' => 'access-token'])),
            new Response(200, [], json_encode(['id' => 'message-id'])),
        ]);

        $message = (new Swift_Message())
            ->setFrom('sender@gmail.com', 'TriFair')
            ->setTo('passenger@example.com')
            ->setSubject('Verify your TriFair email address')
            ->setBody('Hello there');

        $this->assertSame(1, $transport->send($message));

        $this->assertCount(2, $history);
        $this->assertStringContainsString('/token', (string) $history[0]['request']->getUri());
        $this->assertSame('Bearer access-token', $history[1]['request']->getHeaderLine('Authorization'));

        $payload = json_decode((string) $history[1]['request']->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('raw', $payload);

        $raw = base64_decode(strtr($payload['raw'], '-_', '+/'));
        $this->assertStringContainsString('Subject: Verify your TriFair email address', $raw);
        $this->assertStringContainsString('To: passenger@example.com', $raw);
    }

    public function test_it_reuses_the_cached_access_token_across_sends(): void
    {
        $history = [];
        $transport = $this->makeTransport($history, [
            new Response(200, [], json_encode(['access_token' => 'access-token'])),
            new Response(200, [], json_encode(['id' => 'one'])),
            new Response(200, [], json_encode(['id' => 'two'])),
        ]);

        $transport->send($this->message());
        $transport->send($this->message());

        // One token refresh + two sends.
        $this->assertCount(3, $history);
    }

    public function test_the_gmail_api_mailer_is_registered_with_the_custom_transport(): void
    {
        config([
            'services.gmail_api.client_id' => 'client-id',
            'services.gmail_api.client_secret' => 'client-secret',
            'services.gmail_api.refresh_token' => 'refresh-token',
            'services.gmail_api.sender' => 'sender@gmail.com',
        ]);

        $mailer = app('mail.manager')->mailer('gmail-api');

        $this->assertInstanceOf(\Illuminate\Mail\Mailer::class, $mailer);
        $this->assertInstanceOf(GmailApiTransport::class, $mailer->getSwiftMailer()->getTransport());
    }

    public function test_it_refuses_to_send_as_a_different_address(): void
    {
        $history = [];
        $transport = $this->makeTransport($history, []);

        $message = (new Swift_Message())
            ->setFrom('someone-else@gmail.com')
            ->setTo('passenger@example.com')
            ->setSubject('Nope')
            ->setBody('Nope');

        $this->expectException(RuntimeException::class);
        $transport->send($message);
    }

    private function makeTransport(array &$history, array $responses): GmailApiTransport
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        return new GmailApiTransport(
            'client-id',
            'client-secret',
            'refresh-token',
            'sender@gmail.com',
            new Client(['handler' => $stack])
        );
    }

    private function message(): Swift_Message
    {
        return (new Swift_Message())
            ->setFrom('sender@gmail.com')
            ->setTo('passenger@example.com')
            ->setSubject('Test')
            ->setBody('Body');
    }
}
