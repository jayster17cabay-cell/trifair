<?php

namespace App\Http\Controllers;

use App\Services\AdminDashboardService;

class LandingController extends Controller
{
    /**
     * The public landing page. The hero rating is rendered from real data on
     * the first paint so it never shows a placeholder, then the page polls the
     * stats endpoint to keep the number in step as new ratings arrive.
     */
    public function index()
    {
        return view('landing', app(AdminDashboardService::class)->publicRatingStats());
    }

    /**
     * Live JSON payload for the landing page hero: system-wide average, total
     * ratings and this month's count. Public because it is only an aggregate.
     */
    public function stats()
    {
        return response()->json(app(AdminDashboardService::class)->publicRatingStats());
    }
}
