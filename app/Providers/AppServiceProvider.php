<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Mail;
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
        View::composer(['layouts.tfrb-officer', 'layouts.superadmin', 'notifications.index'], NotificationComposer::class);

        Mail::extend('mailjet', function (array $config) {
            return new MailjetTransport($config['api_key'] ?? '', $config['secret_key'] ?? '');
        });
    }
}
