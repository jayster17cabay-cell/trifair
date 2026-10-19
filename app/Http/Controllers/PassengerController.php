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
            ->with(['operator.user', 'response', 'proofs', 'resolutionProofs', 'reviewer', 'passengerFollowUps'])
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
                'follow_up' => 'Nasolusyunan na ang complaint na ito. Kung may bagong isyu, mag-file ng bagong complaint.',
            ]);
        }

        $data = $request->validate([
            'message' => 'required|string|min:3|max:1000',
        ], [
            'message.required' => 'Ilagay ang iyong follow-up message bago mag-submit.',
            'message.min' => 'Pasokin mo ang hindi bababa sa 3 characters.',
            'message.max' => 'Ang follow-up message ay hindi dapat lumagpas sa 1,000 characters.',
        ]);

        $rating->passengerFollowUps()->create([
            'user_id' => $user->id,
            'message' => $data['message'],
        ]);

        return redirect()
            ->route('passenger.dashboard')
            ->with('status', 'Naipadala ang iyong follow-up. Ipapakita ito sa TFRB/superadmin para sa iyong complaint.');
    }
}