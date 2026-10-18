<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/login';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Passenger rating/trip submission.
        //
        // Keyed on the per-device cookie rather than the IP address, for exactly
        // the reason documented on RatingController::existingRatingFor(): carriers,
        // terminals and other NAT'd networks put many unrelated passengers behind
        // one public IP, so an IP bucket would start returning 429s for innocent
        // riders the moment traffic spikes. The cookie is APP_KEY-signed, so a bot
        // cannot mint its own bucket — omitting it falls back to the IP key and
        // keeps the usual protection against cookie-less floods.
        RateLimiter::for('rate-submit', function (Request $request) {
            $deviceId = (string) $request->cookie('tf_pid');

            return $deviceId !== ''
                ? Limit::perMinute(30)->by('device:' . $deviceId)
                : Limit::perMinute(30)->by('ip:' . $request->ip());
        });
    }
}
