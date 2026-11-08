<?php

namespace App\Providers;

use App\Http\ViewComposers\NotificationComposer;
use App\Mail\Transport\GmailApiTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Register the HTTPS-based Gmail transport. SMTP is blocked on Render's
        // free tier, and sending as a @gmail.com address through Brevo fails
        // SPF/DKIM/DMARC alignment (so it lands in Spam). Sending through the
        // Gmail API as the real mailbox fixes that without owning a domain.
        Mail::extend('gmail-api', function () {
            return new GmailApiTransport(
                (string) config('services.gmail_api.client_id'),
                (string) config('services.gmail_api.client_secret'),
                (string) config('services.gmail_api.refresh_token'),
                (string) config('services.gmail_api.sender')
            );
        });

        // Render terminates TLS at the edge and forwards plain HTTP to the
        // container, so URL helpers would emit http:// links (mixed content,
        // blocked by browsers, breaking CSS/JS). Force https in production.
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer(['layouts.tfrb-officer', 'layouts.superadmin', 'layouts.president', 'notifications.index'], NotificationComposer::class);
    }
}
