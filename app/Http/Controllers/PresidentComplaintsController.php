<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Toda;
use App\Services\AdminQueryService;
use App\Services\ComplaintRoutingService;
use App\Services\RatingAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * TODA Presidents manage passenger complaints routed to them. A president
 * can review, solve, reject or forward a complaint to TFRB/superadmin (a
 * one-way escalation). The TFRB/superadmin cases queue shows every
 * complaint too, but the acting routes re-check the assignment before
 * letting anyone act.
 */
class PresidentComplaintsController extends Controller
{
    private function toda(): Toda
    {
        $toda = Auth::user()->presidentToda();

        if (!$toda) {
            abort(403, 'Your account is not assigned to a TODA.');
        }

        return $toda;
    }

    /**
     * Complaints assigned to this president (routed_to = president, still
     * open, not yet forwarded) that the president may act on right now.
     *
     * The president dashboard, the Complaints Queue and the member-detail
     * modal all share this scope: only these cases show the Review/Solved/
     * Reject/Forward actions. Forwarded cases stay visible but locked.
     */
    public function assignableComplaintsFor(Toda $toda, int $presidentId)
    {
        return Rating::query()
            ->where('toda_id', $toda->id)
            ->where('routed_to', $presidentId)
            ->whereNotNull('complaint_type')
            ->where('is_valid', true)
            ->where('is_solved', false)
            ->whereNull('rejected_at')
            ->whereNull('forwarded_at')
            ->with(['operator.user', 'proofs'])
            ->latest()
            ->limit(25)
            ->get();
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $toda = $this->toda();

        $data = app(AdminQueryService::class)->complaintsData($request, $user);
        $todaIds = $user->presidentTodas()->pluck('id')->map(fn ($id) => (int) $id);
        $actionableComplaints = $this->assignableComplaintsFor($toda, (int) $user->id);
        $actionableIds = $actionableComplaints->pluck('id')->map(fn ($id) => (int) $id)->all();
        // How many of this president's complaints they already escalated to
        // the TFRB — shown as a hand-off summary so the queue always answers
        // "where did my cases go".
        $forwardedForTfrb = Rating::query()
            ->where('routed_to', $user->id)
            ->whereNotNull('forwarded_at')
            ->whereNull('rejected_at')
            ->where('is_solved', false)
            ->whereNotNull('complaint_type')
            ->where('is_valid', true)
            ->count();

        return view('president.complaints', $data + [
            'toda' => $toda,
            'presidentMode' => true,
            'todaIds' => $todaIds,
            'actionableComplaintIds' => $actionableIds,
            'actionableCount' => $actionableComplaints->count(),
            'forwardedForTfrbCount' => (int) $forwardedForTfrb,
            'canForward' => $actionableComplaints->isNotEmpty(),
        ]);
    }

    public function complaint(Rating $rating)
    {
        $user = Auth::user();

        // Presidents may open only complaints assigned to their own queue.
        // Forwarded cases remain readable here, but no longer actionable.
        if ((int) $rating->routed_to !== (int) $user->id) {
            abort(403, 'This complaint is not assigned to your TODA queue.');
        }

        $rating->load(['operator.user', 'proofs', 'response', 'operatorProofs', 'resolutionProofs', 'reviewer', 'passengerFollowUps.user', 'forwardedBy', 'routedToPresident']);

        return view('president.complaint', compact('rating'));
    }

    public function markReviewed(Rating $rating)
    {
        return app(RatingAdminService::class)->complaintsMarkReviewed($rating);
    }

    public function markSolved(Request $request, Rating $rating)
    {
        $request->validate([
            'resolution_note' => 'nullable|string|max:2000',
            'resolution_proofs' => 'nullable|array|max:5',
            'resolution_proofs.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,mov,avi,pdf,doc,docx|max:10240',
            'escalation_checklist' => 'nullable|array',
            'escalation_checklist.*' => 'nullable|string|max:255',
        ]);

        return app(RatingAdminService::class)->complaintsMarkSolved(
            $rating,
            $request->input('resolution_note'),
            $request->file('resolution_proofs', []),
            $request->input('escalation_checklist')
        );
    }

    public function reject(Request $request, Rating $rating)
    {
        $request->validate([
            'resolution_note' => 'nullable|string|max:2000',
        ]);

        return app(RatingAdminService::class)->complaintsReject($rating, $request->input('resolution_note'));
    }

    public function reset(Rating $rating)
    {
        return app(RatingAdminService::class)->complaintsResetReview($rating);
    }

    public function reopen(Rating $rating)
    {
        return app(RatingAdminService::class)->complaintsReopen($rating);
    }

    public function forward(Request $request, Rating $rating)
    {
        // The panel follow-up checklist rides along on the forward (which is
        // the one-way escalation path for presidents) so the TFRB / superadmin
        // re-review sees which follow-ups were deemed warranted.
        $request->validate([
            'escalation_checklist' => 'nullable|array',
            'escalation_checklist.*' => 'nullable|string|max:255',
        ]);

        return app(RatingAdminService::class)->complaintsForward($rating, $request->input('escalation_checklist'));
    }

    public function reply(Request $request, Rating $rating)
    {
        return app(RatingAdminService::class)->complaintsReply($request, $rating);
    }
}
