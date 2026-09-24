<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Rating;
use Illuminate\Http\Request;

class PassengerController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $base = Rating::query()
            ->where('passenger_user_id', $user->id)
            ->with(['operator.user', 'response']);

        $totalCount = (clone $base)->count();
        $pendingCount = (clone $base)->where('is_reviewed', false)->where('is_solved', false)->count();
        $reviewedCount = (clone $base)->where('is_reviewed', true)->where('is_solved', false)->count();
        $solvedCount = (clone $base)->isSolved()->count();

        $complaints = (clone $base)->latest()->paginate(15)->withQueryString();

        $notifications = Notification::where('user_id', $user->id)
            ->with('rating')
            ->latest()
            ->limit(10)
            ->get();

        return view('passenger.dashboard', compact('complaints', 'totalCount', 'pendingCount', 'reviewedCount', 'solvedCount', 'notifications'));
    }
}