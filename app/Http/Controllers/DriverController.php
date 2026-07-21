<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\DriverResponse;
use App\Helpers\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function dashboard()
    {
        $driver = Auth::user()->driver;
        $averageRating = $driver->ratings()->valid()->avg('rating');
        $totalRatings = $driver->ratings()->valid()->count();
        $recentRatings = $driver->ratings()->valid()
            ->with('proofs', 'response')
            ->latest()
            ->take(5)
            ->get();

        $ratingCounts = [];
        for ($i = 1; $i <= 5; $i++) {
            $ratingCounts[$i] = $driver->ratings()->valid()->where('rating', $i)->count();
        }

        return view('driver.dashboard', compact(
            'driver', 'averageRating', 'totalRatings',
            'recentRatings', 'ratingCounts'
        ));
    }

    public function ratings()
    {
        $driver = Auth::user()->driver;
        $ratings = $driver->ratings()->valid()
            ->with('proofs', 'response')
            ->latest()
            ->paginate(10);

        $averageRating = $driver->ratings()->valid()->avg('rating');
        $totalRatings = $driver->ratings()->valid()->count();

        return view('driver.ratings', compact(
            'driver', 'ratings', 'averageRating', 'totalRatings'
        ));
    }

    public function respond(Request $request, Rating $rating)
    {
        $driver = Auth::user()->driver;

        if ($rating->driver_id !== $driver->id) {
            return back()->with('error', 'Unauthorized.');
        }

        $data = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $response = DriverResponse::updateOrCreate(
            ['rating_id' => $rating->id],
            ['message' => $data['message']]
        );

        $isNew = $response->wasRecentlyCreated;
        ActivityLogger::log(
            $isNew ? 'driver_respond' : 'update_driver_response',
            ($isNew ? 'Responded to' : 'Updated response on') . " rating #{$rating->id}",
            $rating,
            'driver'
        );

        return back()->with('success', 'Your response has been submitted.');
    }
}
