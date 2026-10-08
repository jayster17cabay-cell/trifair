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
        // Same underlying cause: in the in-app browsers that drop/block the
        // session cookie mid-flow, the Sign out POST loses its session and its
        // CSRF token, so the passenger is stuck with a 419 every logout.
        // Exempting only the logout action lets them sign out cleanly; the only
        // risk of a missing token here is a forced sign-out (benign), never a
        // data change. All other auth/admin routes keep full CSRF protection.
        'logout',
    ];
}
