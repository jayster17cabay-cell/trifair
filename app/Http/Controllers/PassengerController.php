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

        $statusFilters = ['pending', 'accepted', 'rejected', 'solved'];
        $activeStatus = $request->query('status');
        $activeStatus = in_array($activeStatus, $statusFilters, true) ? $activeStatus : null;

        $query = (clone $base)
            ->with(['operator.user', 'response', 'proofs', 'resolutionProofs', 'reviewer', 'passengerFollowUps.user']);

        if ($activeStatus === 'pending') {
            $query->whereNull('is_accepted')->where('is_solved', false);
        } elseif ($activeStatus === 'accepted') {
            $query->where('is_accepted', true)->where('is_solved', false);
        } elseif ($activeStatus === 'rejected') {
            $query->where('is_accepted', false);
        } elseif ($activeStatus === 'solved') {
            $query->where('is_solved', true);
        }

        // Group the list so tickets that still need attention (pending, then
        // accepted) always sit above closed ones (rejected, then solved) —
        // newest first inside each group.
        $query->orderByRaw(
            'CASE'
            . ' WHEN is_solved = true THEN 3'
            . ' WHEN is_accepted IS NULL THEN 0'
            . ' WHEN is_accepted = true THEN 1'
            . ' ELSE 2 END'
        )->orderByDesc('created_at');

        $complaints = $query->paginate(15)->withQueryString();

        $notifications = Notification::where('user_id', $user->id)
            ->with('rating')
            ->latest()
            ->limit(10)
            ->get();

        return view('passenger.dashboard', compact(
            'complaints',
            'totalCount',
            'pendingCount',
            'acceptedCount',
            'rejectedCount',
            'solvedCount',
            'notifications',
            'activeStatus'
        ));
    }

    /**
     * Add a follow-up message to the passenger's own complaint so they can ask
     * for an update or supply extra context while the ticket is still open.
     */
    public function followUp(Request $request, Rating $rating)
    {
        $user = $request->user();

        // Only the passenger who filed it may follow up, and only while it is
        // still open — a solved ticket has nothing left to chase.
        abort_unless((int) $rating->passenger_user_id === (int) $user->id, 403);
        abort_unless($rating->complaint_type !== null, 404);

        if ($rating->is_solved) {
            return back()->withErrors([
                'follow_up' => 'This complaint has already been resolved. If you have a new issue, file a new complaint.',
            ]);
        }

        $data = $request->validate([
            'message' => 'required|string|min:3|max:1000',
        ], [
            'message.required' => 'Enter your follow-up message before submitting.',
            'message.min' => 'Enter at least 3 characters.',
            'message.max' => 'The follow-up message must not exceed 1,000 characters.',
        ]);

        $rating->passengerFollowUps()->create([
            'user_id' => $user->id,
            'message' => $data['message'],
            'sender_role' => 'passenger',
        ]);

        return redirect()
            ->route('passenger.dashboard')
            ->with('status', 'Your follow-up has been sent. It will be shown to the TFRB/superadmin for your complaint.')
            ->with('open_follow_up', $rating->id);
    }
}