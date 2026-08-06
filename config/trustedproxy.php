<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated IPs / CIDRs that sit in front of the app (e.g. a load
    | balancer). When empty, no proxy is trusted: X-Forwarded-* headers are
    | ignored and the immediate peer address is treated as the real client IP.
    |
    | Do NOT set this to '*' on production: it lets any client forge their IP
    | via the X-Forwarded-For header, which defeats per-IP rate limiting and
    | the rating dedup guard (passenger_ip). See .env.example.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: null,
];
