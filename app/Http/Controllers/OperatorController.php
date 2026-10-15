<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\OperatorResponse;
use App\Models\OperatorProof;
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

        $base = $this->applyPeriod($operator->ratings()->isValid(), $period);

        $agg = $this->applyPeriod($operator->countableRatings(), $period)
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
            'recentRatings', 'ratingCounts', 'period'
        ));
    }

    public function ratings(Request $request)
    {
        $operator = Auth::user()->operator;
        $period = $this->resolvePeriod($request);

        $query = $this->applyPeriod($operator->ratings()->isValid(), $period);

        $ratings = $query
            ->with('proofs', 'response', 'operatorProofs')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $agg = $this->applyPeriod($operator->countableRatings(), $period);
        $averageRating = (clone $agg)->avg('rating');
        $totalRatings = (clone $agg)->count();

        return view('operator.ratings', compact(
            'operator', 'ratings', 'averageRating', 'totalRatings', 'period'
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

        $countable = $this->applyPeriod($operator->countableRatings(), $period);
        $valid = $this->applyPeriod($operator->ratings()->isValid(), $period);

        $stats = [
            'totalRatings' => (clone $countable)->count(),
            'averageRating' => (clone $countable)->avg('rating'),
            'totalComplaints' => (clone $valid)->isComplaint()->count(),
            'responseRate' => (clone $valid)->whereHas('response')->count(),
        ];

        return view('operator.profile', compact('operator', 'stats', 'period'));
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
