<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'supabase' => [
        'url' => env('SUPABASE_URL', ''),
        'key' => env('SUPABASE_SERVICE_ROLE_KEY', ''),
        'bucket' => env('SUPABASE_BUCKET', 'proofs'),
        // Keep existing public-bucket deployments working. Set this to false
        // when the Supabase bucket is private so the app issues short-lived
        // signed object URLs instead of redirecting to a public URL.
        'bucket_public' => env('SUPABASE_BUCKET_PUBLIC', true),
    ],

    'cleanup_token' => env('CLEANUP_TOKEN', ''),

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'sendinblue' => [
        'key_identifier' => env('SENDINBLUE_KEY_IDENTIFIER', 'api-key'),
        // Trimmed so a key pasted into a dashboard with a stray space or newline
        // still works — a raw space makes the HTTP header invalid and the send fails.
        'key' => trim((string) env('SENDINBLUE_KEY')),
    ],

    // Gmail API transport (no custom domain required). When all four values are
    // present, TransactionalMail sends every notification through Gmail so the
    // mailbox provider sees a genuinely authenticated Gmail message.
    'gmail_api' => [
        'client_id' => trim((string) env('GMAIL_API_CLIENT_ID')),
        'client_secret' => trim((string) env('GMAIL_API_CLIENT_SECRET')),
        'refresh_token' => trim((string) env('GMAIL_API_REFRESH_TOKEN')),
        'sender' => trim((string) env('GMAIL_API_SENDER', env('MAIL_FROM_ADDRESS'))),
    ],

];
