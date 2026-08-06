<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\OperatorResponse;
use App\Models\User;
use App\Models\Notification;
use App\Helpers\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function dashboard()
    {
        $operator = Auth::user()->operator;
        $base = $operator->ratings()->isValid();

        $agg = (clone $base)
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

        if (request()->wantsJson()) {
            return response()->json([
                'totalRatings' => $totalRatings,
                'averageRating' => round((float) $averageRating, 1),
                'breakdownHtml' => view('partials.dashboard-rating-breakdown', compact('ratingCounts', 'totalRatings'))->render(),
            ]);
        }

        return view('operator.dashboard', compact(
            'operator', 'averageRating', 'totalRatings',
            'recentRatings', 'ratingCounts'
        ));
    }

    public function ratings()
    {
        $operator = Auth::user()->operator;
        $ratings = $operator->ratings()->isValid()
            ->with('proofs', 'response')
            ->latest()
            ->paginate(10);

        $averageRating = $operator->ratings()->isValid()->avg('rating');
        $totalRatings = $operator->ratings()->isValid()->count();

        return view('operator.ratings', compact(
            'operator', 'ratings', 'averageRating', 'totalRatings'
        ));
    }

    public function respond(Request $request, Rating $rating)
    {
        $operator = Auth::user()->operator;

        if ($rating->operator_id !== $operator->id) {
            return back()->with('error', 'Unauthorized.');
        }

        $data = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $response = OperatorResponse::updateOrCreate(
            ['rating_id' => $rating->id],
            ['message' => $data['message']]
        );

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
