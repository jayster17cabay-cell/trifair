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
            ->whereNotNull('complaint_type')
            ->with(['operator.user', 'response', 'proofs']);

        $totalCount = (clone $base)->count();
        $pendingCount = (clone $base)->whereNull('is_accepted')->where('is_solved', false)->count();
        $acceptedCount = (clone $base)->where('is_accepted', true)->where('is_solved', false)->count();
        $rejectedCount = (clone $base)->where('is_accepted', false)->count();
        $solvedCount = (clone $base)->isSolved()->count();

        $complaints = (clone $base)->latest()->paginate(15)->withQueryString();

        $notifications = Notification::where('user_id', $user->id)
            ->with('rating')
            ->latest()
            ->limit(10)
            ->get();

        return view('passenger.dashboard', compact('complaints', 'totalCount', 'pendingCount', 'acceptedCount', 'rejectedCount', 'solvedCount', 'notifications'));
    }
}