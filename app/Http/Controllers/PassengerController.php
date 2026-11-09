<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Rating;
use App\Models\SuspensionViolation;
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
            . ', SUM(CASE WHEN is_solved = false AND rejected_at IS NULL THEN 1 ELSE 0 END) as open_count'
            . ', SUM(CASE WHEN rejected_at IS NOT NULL THEN 1 ELSE 0 END) as rejected_count'
            . ', SUM(CASE WHEN is_solved = true THEN 1 ELSE 0 END) as solved_count'
        )->first();

        $totalCount = (int) $tally->total_count;
        $openCount = (int) $tally->open_count;
        $rejectedCount = (int) $tally->rejected_count;
        $solvedCount = (int) $tally->solved_count;

        $statusFilters = ['open', 'rejected', 'solved'];
        $tab = $request->query('tab');
        // Backward compat: ?status= maps to the matching tab.
        if ($tab === null && $request->has('status')) {
            $tab = $request->query('status');
        }
        $allowedTabs = ['open', 'rejected', 'solved', 'violations'];
        $tab = in_array($tab, $allowedTabs, true) ? $tab : 'open';

        $activeStatus = in_array($tab, $statusFilters, true) ? $tab : null;

        $query = (clone $base)
            ->with(['operator.user', 'response', 'proofs', 'resolutionProofs', 'reviewer', 'passengerFollowUps.user']);

        if ($activeStatus === 'open') {
            $query->where('is_solved', false)->whereNull('rejected_at');
        } elseif ($activeStatus === 'rejected') {
            $query->whereNotNull('rejected_at');
        } elseif ($activeStatus === 'solved') {
            $query->where('is_solved', true);
        }

        // When viewing violations tab, no complaints are listed.
        if ($tab === 'violations') {
            $query->whereRaw('1 = 0');
        }

        // Group: open tickets above closed ones (rejected, then solved) —
        // newest first inside each group.
        $query->orderByRaw(
            'CASE'
            . ' WHEN is_solved = true THEN 2'
            . ' WHEN rejected_at IS NOT NULL THEN 1'
            . ' ELSE 0 END'
        )->orderByDesc('created_at');

        $complaints = $query->paginate(15)->withQueryString();

        $notifications = Notification::where('user_id', $user->id)
            // Show both violation updates and complaint status updates. A
            // passenger who signed in through Google should see the solved
            // complaint notification in the same account dashboard.
            ->whereIn('type', ['violation', 'complaint_status'])
            ->with('rating')
            ->latest()
            ->limit(10)
            ->get();

        // Violation reports this passenger filed against suspended / lapsed
        // operators — monitored on this page alongside complaints.
        $violations = SuspensionViolation::with(['operator.user', 'toda', 'proofs', 'reviewer'])
            ->where('reported_by', $user->id)
            ->latest()
            ->limit(50)
            ->get();

        return view('passenger.dashboard', compact(
            'complaints',
            'totalCount',
            'openCount',
            'rejectedCount',
            'solvedCount',
            'notifications',
            'activeStatus',
            'violations',
            'tab',
        ));
    }

    /**
     * Show one complaint on its own screen. The list stays intentionally
     * lightweight; follow-ups and appeals belong to this focused view.
     */
    public function complaint(Request $request, Rating $rating)
    {
        abort_unless((int) $rating->passenger_user_id === (int) $request->user()->id, 404);
        abort_unless($rating->complaint_type !== null, 404);

        $rating->load(['operator.user', 'response', 'proofs', 'resolutionProofs', 'reviewer', 'passengerFollowUps.user']);

        return view('passenger.complaint', compact('rating'));
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

        $isAppeal = $request->boolean('appeal');

        if ($isAppeal && !$rating->canAppeal()) {
            return back()->withErrors([
                'follow_up' => 'This complaint was decided by the TFRB/superadmin and can no longer be appealed.',
            ]);
        }

        if (($rating->is_solved || $rating->rejected_at) && !$isAppeal) {
            return back()->withErrors([
                'follow_up' => 'This complaint is closed. Use the appeal form if you are not satisfied with the decision.',
            ]);
        }

        $data = $request->validate([
            'message' => 'required|string|min:3|max:1000',
            'appeal' => 'nullable|boolean',
        ], [
            'message.required' => 'Enter your follow-up message before submitting.',
            'message.min' => 'Enter at least 3 characters.',
            'message.max' => 'The follow-up message must not exceed 1,000 characters.',
        ]);

        if ($isAppeal) {
            // An appeal reopens the case and sends ownership back to the
            // TFRB/superadmin queue, even when a TODA president already solved
            // or rejected it. This makes it actionable, not just a message
            // hidden in a closed thread.
            $rating->update([
                'is_solved' => false,
                'is_reviewed' => false,
                'rejected_at' => null,
                'routed_to' => null,
                'forwarded_at' => null,
                'forwarded_by' => null,
                'solved_at' => null,
                'reviewed_by' => null,
                'resolution_note' => null,
                // The appeal gives the TFRB/superadmin a fresh decision cycle;
                // clearing the marker guarantees the next resolution emails
                // the passenger again.
                'complaint_status_email_sent_at' => null,
            ]);
        }

        $rating->passengerFollowUps()->create([
            'user_id' => $user->id,
            'message' => $data['message'],
            'sender_role' => 'passenger',
            'is_appeal' => $isAppeal,
        ]);

        return redirect()->route($isAppeal ? 'passenger.complaints.show' : 'passenger.dashboard', $isAppeal ? [$rating] : [])
            ->with('status', $isAppeal
                ? 'Your appeal has been sent to the TFRB/superadmin for review.'
                : 'Your follow-up has been sent. It will be shown to the TFRB/superadmin for your complaint.')
            ->with('open_follow_up', $rating->id);
    }
}
