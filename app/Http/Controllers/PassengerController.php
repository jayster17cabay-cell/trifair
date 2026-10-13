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
            ->whereNotNull('complaint_type');

        // One round-trip instead of five: the status tallies are all derived
        // from the same base query via conditional aggregation.
        $tally = (clone $base)->selectRaw(
            'COUNT(*) as total_count'
            . ', SUM(CASE WHEN is_accepted IS NULL AND is_solved = false THEN 1 ELSE 0 END) as pending_count'
            . ', SUM(CASE WHEN is_accepted = true AND is_solved = false THEN 1 ELSE 0 END) as accepted_count'
            . ', SUM(CASE WHEN is_accepted = false THEN 1 ELSE 0 END) as rejected_count'
            . ', SUM(CASE WHEN is_solved = true THEN 1 ELSE 0 END) as solved_count'
        )->first();

        $totalCount = (int) $tally->total_count;
        $pendingCount = (int) $tally->pending_count;
        $acceptedCount = (int) $tally->accepted_count;
        $rejectedCount = (int) $tally->rejected_count;
        $solvedCount = (int) $tally->solved_count;

        $complaints = (clone $base)
            ->with(['operator.user', 'response', 'proofs', 'resolutionProofs'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $notifications = Notification::where('user_id', $user->id)
            ->with('rating')
            ->latest()
            ->limit(10)
            ->get();

        return view('passenger.dashboard', compact('complaints', 'totalCount', 'pendingCount', 'acceptedCount', 'rejectedCount', 'solvedCount', 'notifications'));
    }
}