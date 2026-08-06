<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Http\ViewComposers\NotificationComposer;
use App\Mail\MailjetTransport;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Render terminates TLS at the edge and forwards plain HTTP to the
        // container, so URL helpers would emit http:// links (mixed content,
        // blocked by browsers, breaking CSS/JS). Force https in production.
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer(['layouts.tfrb-officer', 'layouts.superadmin', 'notifications.index'], NotificationComposer::class);

        Mail::extend('mailjet', function (array $config) {
            return new MailjetTransport($config['api_key'] ?? '', $config['secret_key'] ?? '');
        });
    }
}
