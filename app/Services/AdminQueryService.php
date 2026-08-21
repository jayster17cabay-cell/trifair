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
        return Rating::isValid()->where('rating', '>', 2)
            ->with(['operator.user', 'proofs', 'response'])
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
            ->whereNull('operators.archived_at')
            ->select('operators.*', 'vr.valid_ratings_avg_rating', 'vr.valid_ratings_count')
            ->orderByDesc('valid_ratings_count')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Shared base query for the operators list, honoring search + status
     * filters. Status may also be "archived" to browse archived operators.
     */
    public function operatorsBaseQuery(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Operator::with('user', 'toda');

        if ($status === 'archived') {
            $query->archived();
        } elseif ($status && in_array($status, ['active', 'inactive', 'pending', 'rejected'])) {
            $query->notArchived()->where('status', $status);
        } else {
            $status = null;
            $query->notArchived();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%");
                })->orWhere('body_number', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function operatorsData(Request $request): array
    {
        $operators = $this->operatorsBaseQuery($request)->latest()->paginate(10)->withQueryString();

        $search = $request->query('search');
        $status = $request->query('status');
        $archivedCount = Operator::archived()->count();
        $activeOperatorsCount = Operator::notArchived()->where('status', 'active')->count();

        return compact('operators', 'search', 'status', 'archivedCount', 'activeOperatorsCount');
    }

    public function operatorsForExport(Request $request)
    {
        $query = $this->operatorsBaseQuery($request);

        $operatorId = $request->query('operator_id');
        if ($operatorId) {
            $query->where('operators.id', $operatorId);
        }

        return $query->latest()->get();
    }

    public function ratingsForExport(?int $operatorId = null): \Illuminate\Support\Collection
    {
        $query = Rating::isValid()->where('rating', '>', 2)->with(['operator.user', 'response']);
        if ($operatorId) {
            $query->where('operator_id', $operatorId);
        }

        return $query->latest()
            ->get()
            ->map(function ($rating) {
                return [
                    'id' => $rating->id,
                    'trip' => $rating->trip_id ?? '—',
                    'operator' => $rating->operator->user->name ?? 'Unknown',
                    'rating' => $rating->rating,
                    'comment' => $rating->comment ?? '',
                    'date' => $rating->created_at?->format('Y-m-d H:i'),
                ];
            });
    }

    public function complaintsForExport(Request $request): \Illuminate\Support\Collection
    {
        $filter = $request->query('filter', 'pending');
        $operatorId = $request->query('operator_id');
        $base = Rating::isValid()->where('rating', '<=', 2);
        if ($filter === 'reviewed') {
            $base->where('is_reviewed', true);
        } elseif ($filter !== 'all') {
            $base->where('is_reviewed', false);
        }
        if ($operatorId) {
            $base->where('operator_id', $operatorId);
        }

        return $base->with(['operator.user', 'response'])
            ->latest()
            ->get()
            ->map(function ($complaint) {
                return [
                    'id' => $complaint->id,
                    'trip' => $complaint->trip_id ?? '—',
                    'operator' => $complaint->operator->user->name ?? 'Unknown',
                    'rating' => $complaint->rating,
                    'complaint' => $complaint->comment ?? '',
                    'status' => $complaint->is_reviewed ? 'Reviewed' : 'Pending',
                    'date' => $complaint->created_at?->format('Y-m-d H:i'),
                ];
            });
    }

    public function reportsForExport(Request $request = null): \Illuminate\Support\Collection
    {
        $query = Operator::with('user', 'toda')
            ->withCount('validRatings')
            ->whereNotIn('status', ['pending', 'rejected'])
            ->whereNull('archived_at');

        if ($request) {
            $operatorId = $request->query('operator_id');
            if ($operatorId) {
                $query->where('operators.id', $operatorId);
            }
        }

        return $query->get()
            ->map(function ($operator) {
                return [
                    'name' => $operator->user->name ?? 'Unknown',
                    'toda' => $operator->toda?->name ?? 'Unassigned',
                    'body_number' => $operator->body_number ?? '—',
                    'plate_number' => $operator->plate_number ?? '—',
                    'total_trips' => $operator->valid_ratings_count,
                    'avg_rating' => number_format((float) $operator->validRatings()->avg('rating'), 2),
                    'status' => ucfirst($operator->status),
                ];
            });
    }

    public function activityLogsForExport(Request $request): \Illuminate\Support\Collection
    {
        $category = $request->query('category');
        $query = ActivityLog::with('user');
        if ($category && in_array($category, ['auth', 'tfrb_officer', 'operator', 'review', 'system'])) {
            $query->where('category', $category);
        }

        return $query->latestFirst()
            ->limit(5000)
            ->get()
            ->map(function ($log) {
                return [
                    'date' => $log->created_at?->format('Y-m-d H:i'),
                    'user' => $log->user->name ?? 'System',
                    'action' => $log->action,
                    'category' => $log->category,
                    'description' => $log->description ?? '',
                ];
            });
    }

    public function activeOperators(): \Illuminate\Database\Eloquent\Collection
    {
        return Operator::notArchived()
            ->where('status', 'active')
            ->with('user')
            ->orderBy('id')
            ->get();
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

        $logs = $query->latestFirst()->paginate(20)->withQueryString();

        return compact('logs', 'category');
    }

    public function todasData(?string $search = null): array
    {
        $todas = Toda::withCount([
            'operators' => function ($query) {
                $query->whereNull('archived_at');
            },
            'operators as active_operators_count' => function ($query) {
                $query->where('status', 'active')->whereNull('archived_at');
            },
        ])
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('area', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return compact('todas', 'search');
    }

    public function todaMembersData(Toda $toda): \Illuminate\Database\Eloquent\Collection
    {
        return $toda->operators()
            ->with('user')
            ->whereNull('archived_at')
            ->get();
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
