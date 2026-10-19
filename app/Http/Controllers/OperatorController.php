<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\OperatorResponse;
use App\Models\OperatorProof;
use App\Models\Toda;
use App\Models\User;
use App\Models\Notification;
use App\Helpers\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function dashboard(Request $request)
    {
        $operator = Auth::user()->operator;
        $period = $this->resolvePeriod($request);
        $todas = $operator->getRateableTodas();
        $selectedToda = $this->resolveToda($request, $todas);

        // A suspended TODA can still be opened — it simply reports the
        // suspension instead of any dashboard data.
        if ($this->isSuspendedInScope($operator, $todas, $selectedToda)) {
            if ($request->wantsJson()) {
                return response()->json(['suspended' => true], 423);
            }

            return view('operator.suspended', ['operator' => $operator, 'toda' => $selectedToda]);
        }

        $base = $this->applyPeriod($this->scopeToda($operator->ratings()->isValid(), $selectedToda), $period);

        $agg = $this->applyPeriod($this->scopeToda($operator->countableRatings(), $selectedToda), $period)
            ->selectRaw('COUNT(*) as total_ratings, AVG(rating) as avg_rating')
            ->selectRaw('SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as r1')
            ->selectRaw('SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as r2')
            ->selectRaw('SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as r3')
            ->selectRaw('SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as r4')
            ->selectRaw('SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as r5')
            ->first();

        $totalRatings = (int) ($agg->total_ratings ?? 0);
        $averageRating = $agg->avg_rating;
        $ratingCounts = [
            1 => (int) ($agg->r1 ?? 0),
            2 => (int) ($agg->r2 ?? 0),
            3 => (int) ($agg->r3 ?? 0),
            4 => (int) ($agg->r4 ?? 0),
            5 => (int) ($agg->r5 ?? 0),
        ];

        // Combined average across every TODA the operator belongs to.
        $overallAgg = $this->applyPeriod($operator->countableRatings(), $period)
            ->selectRaw('COUNT(*) as total, AVG(rating) as avg')
            ->first();
        $overallTotal = (int) ($overallAgg->total ?? 0);
        $overallAvg = $overallTotal > 0 ? round((float) $overallAgg->avg, 1) : null;

        $recentRatings = (clone $base)
            ->with('proofs', 'response')
            ->latest()
            ->take(5)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'totalRatings' => $totalRatings,
                'averageRating' => round((float) $averageRating, 1),
                'breakdownHtml' => view('partials.dashboard-rating-breakdown', compact('ratingCounts', 'totalRatings'))->render(),
            ]);
        }

        return view('operator.dashboard', compact(
            'operator', 'averageRating', 'totalRatings',
            'recentRatings', 'ratingCounts', 'period',
            'todas', 'selectedToda', 'overallAvg', 'overallTotal'
        ));
    }

    public function ratings(Request $request)
    {
        $operator = Auth::user()->operator;
        $period = $this->resolvePeriod($request);
        $todas = $operator->getRateableTodas();
        $selectedToda = $this->resolveToda($request, $todas);

        $query = $this->applyPeriod($this->scopeToda($operator->ratings()->isValid(), $selectedToda), $period);

        $ratings = $query
            ->with('proofs', 'response', 'operatorProofs')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $agg = $this->applyPeriod($this->scopeToda($operator->countableRatings(), $selectedToda), $period);
        $averageRating = (clone $agg)->avg('rating');
        $totalRatings = (clone $agg)->count();

        return view('operator.ratings', compact(
            'operator', 'ratings', 'averageRating', 'totalRatings', 'period',
            'todas', 'selectedToda'
        ));
    }

    /**
     * Resolve which reporting period the operator's own dashboard should show.
     * "period" is one of all|year|month|week and defaults to "all", so a
     * franchise renewal never hides the operator's earlier ratings or
     * complaints — every record stays visible until a narrower period is
     * deliberately chosen.
     */
    private function resolvePeriod(Request $request): string
    {
        $period = (string) $request->query('period', 'all');

        return in_array($period, ['all', 'year', 'month', 'week'], true) ? $period : 'all';
    }

    /**
     * Resolve which TODA the operator wants to view. "all"/missing => null
     * (every TODA combined); an unknown id is ignored (falls back to all).
     */
    private function resolveToda(Request $request, $todas): ?Toda
    {
        $todaId = $request->query('toda');

        // An explicit "all" keeps the combined view, and a supplied id wins
        // outright. With neither, fall back to the TODA the account picked when
        // it signed in, so the numbers on screen follow that choice.
        if ($todaId === 'all') {
            return null;
        }

        if ($todaId) {
            return $todas->firstWhere('id', (int) $todaId);
        }

        $sessionTodaId = (int) session(\App\Support\TodaContext::SESSION_KEY);

        return $sessionTodaId ? $todas->firstWhere('id', $sessionTodaId) : null;
    }

    /**
     * True when the scope currently on screen is one the member is suspended
     * in: a named TODA is checked directly, while the combined "All TODAs" view
     * only counts as suspended when there is no active TODA left to use.
     *
     * @param  \Illuminate\Support\Collection  $todas
     */
    private function isSuspendedInScope($operator, $todas, ?Toda $selectedToda): bool
    {
        if ($selectedToda) {
            return $operator->isSuspendedForToda((int) $selectedToda->id);
        }

        if ($todas->isEmpty()) {
            return $operator->isSuspended();
        }

        return $todas->every(fn (Toda $toda) => $operator->isSuspendedForToda((int) $toda->id));
    }

    /**
     * Narrow a ratings query to a single TODA when one is selected.
     */
    private function scopeToda($query, ?Toda $toda)
    {
        return $toda ? $query->where('toda_id', $toda->id) : $query;
    }

    /**
     * Narrow a ratings query to the chosen reporting period (relative to now).
     */
    private function applyPeriod($query, string $period)
    {
        return match ($period) {
            'year' => $query->where('created_at', '>=', now()->startOfYear()),
            'month' => $query->where('created_at', '>=', now()->startOfMonth()),
            'week' => $query->where('created_at', '>=', now()->startOfWeek()),
            default => $query,
        };
    }

    public function respond(Request $request, Rating $rating)
    {
        $operator = Auth::user()->operator;

        if ((int) $rating->operator_id !== (int) $operator->id) {
            return back()->with('error', 'Unauthorized.');
        }

        // Operators may only respond to their own valid low-rated (<=2 star)
        // complaints — matching the UI, which only offers the response form there.
        // This blocks a crafted POST that tries to respond to any other rating.
        if (!$rating->is_valid || $rating->rating > 2) {
            return back()->with('error', 'You can only respond to low-rated complaints.');
        }

        $data = $request->validate([
            'message' => 'required|string|max:2000',
            'files' => 'nullable|array|max:5',
            'files.*' => 'file|mimes:jpg,jpeg,png,webp,gif,pdf|max:15360',
        ]);

        $response = OperatorResponse::updateOrCreate(
            ['rating_id' => $rating->id],
            ['message' => $data['message']]
        );

        // Upload any new proof attachments shared by the operator. Mirror the
        // passenger flow: store on Supabase when configured so the files are
        // reachable from the public app (the local 'public' disk is ephemeral
        // on the Render host), falling back to local storage when not available.
        if ($request->hasFile('files')) {
            $rating->operatorProofs()->delete();
            foreach ($request->file('files') as $file) {
                if (!$file || !$file->isValid()) continue;
                    $filename = time() . '_' . uniqid() . '.' . $file->extension();
                $remotePath = 'operator-proofs/' . $rating->id . '/' . $filename;

                $saved = false;
                $path = $remotePath;
                if (class_exists(\App\Helpers\SupabaseStorage::class)) {
                    $result = \App\Helpers\SupabaseStorage::upload($file, $remotePath);
                    $saved = (bool) $result;
                }

                if (!$saved) {
                    $file->storeAs('operator-proofs/' . $rating->id, $filename, 'public');
                    $path = 'operator-proofs/' . $rating->id . '/' . $filename;
                }

                OperatorProof::create([
                    'rating_id' => $rating->id,
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        $isNew = $response->wasRecentlyCreated;
        ActivityLogger::log(
            $isNew ? 'operator_respond' : 'update_operator_response',
            ($isNew ? 'Responded to' : 'Updated response on') . " rating #{$rating->id}",
            $rating,
            'operator'
        );

        if ($isNew) {
            $officers = User::whereIn('role', ['tfrb_officer', 'superadmin'])->get();
            foreach ($officers as $officer) {
                Notification::create([
                    'user_id' => $officer->id,
                    'rating_id' => $rating->id,
                    'type' => 'operator_response',
                    'title' => 'Operator Responded',
                    'message' => "Operator {$operator->user->name} responded to a {$rating->rating}-star rating: " . substr($data['message'], 0, 100),
                ]);
            }
        }

        return back()->with('success', 'Your response has been submitted.');
    }

    public function profile(Request $request)
    {
        $operator = Auth::user()->operator->load('user', 'toda');
        $period = $this->resolvePeriod($request);
        $todas = $operator->getRateableTodas();
        $selectedToda = $this->resolveToda($request, $todas);

        $countable = $this->applyPeriod($this->scopeToda($operator->countableRatings(), $selectedToda), $period);
        $valid = $this->applyPeriod($this->scopeToda($operator->ratings()->isValid(), $selectedToda), $period);

        $stats = [
            'totalRatings' => (clone $countable)->count(),
            'averageRating' => (clone $countable)->avg('rating'),
            'totalComplaints' => (clone $valid)->isComplaint()->count(),
            'responseRate' => (clone $valid)->whereHas('response')->count(),
        ];

        // TODA memberships (active + pending) and the TODAs the operator can
        // still request to join (empty once the three-TODA limit is reached).
        $memberships = $operator->todaMemberships()
            ->with('toda')
            ->orderByDesc('id')
            ->get();
        $membershipService = app(\App\Services\TodaMembershipService::class);
        $canJoinAnother = $membershipService->canJoinAnotherToda($operator);
        $joinableTodas = $canJoinAnother ? $membershipService->joinableTodas($operator) : collect();

        return view('operator.profile', compact(
            'operator', 'stats', 'period', 'todas', 'selectedToda',
            'memberships', 'joinableTodas', 'canJoinAnother'
        ));
    }

    /**
     * Self-service request to join another TODA. The membership lands in the
     * target TODA president's queue (pending), then goes to superadmin/TFRB for
     * final approval before the operator is rateable under it.
     */
    public function joinToda(Request $request)
    {
        $operator = Auth::user()->operator;

        $data = $request->validate([
            'toda_id' => ['required', 'integer', 'exists:todas,id'],
        ]);

        $toda = Toda::where('is_active', true)->find($data['toda_id']);

        if (!$toda) {
            return back()->with('error', 'That TODA is not available for membership.');
        }

        if ($operator->hasOpenOrActiveMembership($toda->id)
            || (int) $operator->toda_id === (int) $toda->id) {
            return back()->with('error', 'You already have a membership or a pending request for this TODA.');
        }

        $service = app(\App\Services\TodaMembershipService::class);

        if (!$service->canJoinAnotherToda($operator)) {
            return back()->with('error', 'You can be a member of at most ' . \App\Models\OperatorToda::MAX_TODAS . ' TODAs.');
        }

        $service->requestJoin($operator, $toda);

        return back()->with(
            'success',
            "Your request to join {$toda->name} was submitted. It will be reviewed by the TODA president, then either the TFRB officer or the superadmin will give the final approval."
        );
    }

    public function showSettings()
    {
        $operator = Auth::user()->operator;
        return view('operator.settings', compact('operator'));
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($data['new_password']);
        $user->save();

        ActivityLogger::log(
            'operator_update_password',
            "Updated own password",
            $user,
            'operator'
        );

        return back()->with('success', 'Password updated successfully.');
    }
}
