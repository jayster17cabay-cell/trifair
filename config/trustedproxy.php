<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Behind a reverse proxy (Render), set TRUSTED_PROXIES=* so Laravel trusts
    | the IMMEDIATE caller (Render's proxy) and honors X-Forwarded-Proto/For.
    | Laravel's "*" only trusts the calling IP — not arbitrary clients — and the
    | rating dedup guard is additionally keyed on a signed cookie, so it stays
    | robust against spoofed X-Forwarded-For.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: null,
];
