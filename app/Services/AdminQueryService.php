<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Read-side queries shared by the Superadmin and TFRB Officer dashboards.
 * Keeping them in one place guarantees the two roles see identical data and
 * removes ~150 lines of duplication between the controllers.
 */
class AdminQueryService
{
    public function complaintsData(Request $request): array
    {
        $filter = $request->query('filter', 'pending');

        $base = Rating::isValid()->where('rating', '<=', 2);

        $pendingCount = (clone $base)->where('is_reviewed', false)->count();
        $reviewedCount = (clone $base)->where('is_reviewed', true)->count();
        $totalCount = (clone $base)->count();

        if ($filter === 'pending') {
            $base->where('is_reviewed', false);
        } elseif ($filter === 'reviewed') {
            $base->where('is_reviewed', true);
        } elseif ($filter !== 'all') {
            $filter = 'pending';
            $base->where('is_reviewed', false);
        }

        $complaints = $base->with(['operator.user', 'proofs', 'response'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return compact('complaints', 'filter', 'pendingCount', 'reviewedCount', 'totalCount');
    }

    public function ratingsData(): LengthAwarePaginator
    {
        return Rating::isValid()->with(['operator.user', 'proofs', 'response'])
            ->latest()
            ->paginate(15);
    }

    public function reportsData(): LengthAwarePaginator
    {
        return Operator::with('user')
            ->leftJoin(
                DB::raw('(select operator_id, avg(rating) as valid_ratings_avg_rating, count(*) as valid_ratings_count from ratings where is_valid = true group by operator_id) as vr'),
                'vr.operator_id',
                '=',
                'operators.id'
            )
            ->whereNotIn('operators.status', ['pending', 'rejected'])
            ->select('operators.*', 'vr.valid_ratings_avg_rating', 'vr.valid_ratings_count')
            ->orderByDesc('valid_ratings_count')
            ->paginate(25)
            ->withQueryString();
    }

    public function operatorsData(Request $request): array
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $query = Operator::with('user', 'toda');
        if ($status && in_array($status, ['active', 'inactive', 'pending', 'rejected'])) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['active', 'inactive']);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%");
                })->orWhere('body_number', 'like', "%{$search}%");
            });
        }
        $operators = $query->latest()->paginate(10);

        return compact('operators', 'search', 'status');
    }

    public function invalidRatingsData(): LengthAwarePaginator
    {
        return Rating::with(['operator.user', 'proofs'])
            ->where('is_valid', false)
            ->latest()
            ->paginate(15);
    }

    public function activityLogsData(Request $request): array
    {
        $category = $request->query('category');

        $query = ActivityLog::with('user');

        if ($category && in_array($category, ['auth', 'tfrb_officer', 'operator', 'review', 'system'])) {
            $query->where('category', $category);
        }

        $logs = $query->latestFirst()->paginate(20);

        return compact('logs', 'category');
    }

    public function todasData(): LengthAwarePaginator
    {
        return Toda::withCount([
            'operators',
            'operators as active_operators_count' => function ($query) {
                $query->where('status', 'active');
            },
        ])->latest()->paginate(20);
    }

    public function todaMembersData(Toda $toda): array
    {
        return $toda->operators()->with('user')->get()->map(function ($operator) {
            return [
                'name' => $operator->user->name ?? 'Unknown',
                'body_number' => $operator->body_number,
                'plate_number' => $operator->plate_number,
                'status' => $operator->status,
            ];
        })->all();
    }

    /**
     * AJAX endpoint data for the reports page: trip history for a single
     * operator, capped at 200 trips so the page stays fast at scale.
     */
    public function reportTripsData(Operator $operator): array
    {
        abort_unless(in_array($operator->status, ['active', 'inactive'], true), 404);

        $totalTrips = $operator->validRatings()->count();
        $operator->load(['validRatings' => function ($query) {
            $query->latest()->limit(200);
        }]);

        return compact('operator', 'totalTrips');
    }
}
