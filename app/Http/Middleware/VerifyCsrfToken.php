<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Public passenger rate form. Smartphone in-app browsers frequently
        // drop/block cookies, which repeatedly produced 419 "Page Expired" on
        // submit. Abuse is already bounded by the throttle:30,1 middleware and
        // the one-rating-per-operator-per-day IP/cookie dedup in
        // RatingController::existingRatingFor(). All admin/auth routes keep
        // full CSRF protection.
        'rate/*',
    ];
}
