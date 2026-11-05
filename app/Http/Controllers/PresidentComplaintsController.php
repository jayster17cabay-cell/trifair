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

    public function index(Request $request)
    {
        $user = Auth::user();
        $toda = $this->toda();

        $data = app(AdminQueryService::class)->complaintsData($request, $user);
        $todaIds = $user->presidentTodas()->pluck('id');

        return view('president.complaints', $data + [
            'toda' => $toda,
            'presidentMode' => true,
            'todaIds' => $todaIds,
        ]);
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
            'resolution_proofs.*' => 'nullable|file|max:10240',
        ]);

        return app(RatingAdminService::class)->complaintsMarkSolved(
            $rating,
            $request->input('resolution_note'),
            $request->file('resolution_proofs', [])
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

    public function forward(Rating $rating)
    {
        return app(RatingAdminService::class)->complaintsForward($rating);
    }

    public function reply(Request $request, Rating $rating)
    {
        return app(RatingAdminService::class)->complaintsReply($request, $rating);
    }
}
