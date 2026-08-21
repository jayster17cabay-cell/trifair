<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    private const CACHE_TTL = 60;

    /**
     * Compute the shared dashboard metrics for the Superadmin and
     * TFRB Officer dashboards.
     *
     * Results are cached for self::CACHE_TTL seconds; every new rating
     * flushes the cache so aggregates stay fresh without hammering the
     * database on the 30s polling loop.
     *
     * @param array $options keys: recentLimit, includeOfficers, includePendingReview
     */
    public function stats(array $options = []): array
    {
        $roleKey = ($options['includeOfficers'] ?? false) ? 'superadmin' : 'tfrb';

        return Cache::remember('admin_dashboard:' . $roleKey, self::CACHE_TTL, function () use ($options) {
            return $this->computeStats($options);
        });
    }

    /**
     * Drop the cached dashboard metrics (called after a rating/complaint
     * is submitted so the next poll reflects it immediately).
     */
    public function flush(): void
    {
        Cache::forget('admin_dashboard:superadmin');
        Cache::forget('admin_dashboard:tfrb');
    }

    private function computeStats(array $options = []): array
    {
        $recentLimit = $options['recentLimit'] ?? 5;

        $data = [
            'totalOperators' => Operator::notArchived()->count(),
            'activeOperators' => Operator::notArchived()->where('status', 'active')->count(),
            'totalRatings' => Rating::isValid()->count(),
            'averageRating' => Rating::isValid()->avg('rating'),
            'totalComplaints' => Rating::isValid()->where('rating', '<=', 2)->count(),
            'totalTodas' => Toda::count(),
        ];

        if ($options['includeOfficers'] ?? false) {
            $data['totalOfficers'] = User::where('role', 'tfrb_officer')->count();
        }

        if ($options['includePendingReview'] ?? false) {
            $data['pendingReview'] = Rating::isValid()->where('is_reviewed', false)->count();
        }

        $data['recentRatings'] = Rating::isValid()->with(['operator.user', 'operator.toda'])
            ->latest()
            ->take($recentLimit)
            ->get();

        $data['recentComplaints'] = Rating::isValid()->with(['operator.user', 'proofs'])
            ->where('rating', '<=', 2)
            ->latest()
            ->take(20)
            ->get()
            ->unique(function ($r) {
                return $r->operator_id . '|' . ($r->complaint_type ?? 'none');
            })
            ->take(5)
            ->values();

        $data['complaintStats'] = Rating::paddedComplaintStats(
            Rating::isValid()
                ->where('rating', '<=', 2)
                ->select(DB::raw("COALESCE(complaint_type, 'Others') as complaint_type"), DB::raw('count(*) as total'))
                ->groupBy('complaint_type')
                ->orderByDesc('total')
                ->get()
        );

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach (
            Rating::isValid()
                ->whereBetween('rating', [1, 5])
                ->select('rating', DB::raw('count(*) as total'))
                ->groupBy('rating')
                ->get() as $row
        ) {
            $distribution[(int) $row->rating] = (int) $row->total;
        }
        $data['ratingDistribution'] = $distribution;

        $data['topOperators'] = Operator::with('user', 'toda')
            ->whereNull('operators.archived_at')
            ->leftJoin(
                DB::raw('(select operator_id, avg(rating) as valid_ratings_avg_rating, count(*) as valid_ratings_count from ratings where is_valid = true group by operator_id) as vr'),
                'vr.operator_id',
                '=',
                'operators.id'
            )
            ->select('operators.*', 'vr.valid_ratings_avg_rating', 'vr.valid_ratings_count')
            ->whereNotNull('vr.valid_ratings_count')
            ->orderByDesc('valid_ratings_avg_rating')
            ->take(5)
            ->get();

        $todaStats = Toda::withCount([
            'operators' => function ($query) {
                $query->whereNull('archived_at');
            },
            'operators as active_operators_count' => function ($query) {
                $query->where('status', 'active')->whereNull('archived_at');
            },
        ])->get();

        $todaAverages = Rating::isValid()
            ->join('operators', 'ratings.operator_id', '=', 'operators.id')
            ->select('operators.toda_id', DB::raw('avg(ratings.rating) as avg_rating'))
            ->whereIn('operators.toda_id', $todaStats->pluck('id'))
            ->groupBy('operators.toda_id')
            ->pluck('avg_rating', 'toda_id');

        foreach ($todaStats as $toda) {
            $toda->avg_rating = $todaAverages[$toda->id] ?? null;
        }

        $data['todaStats'] = $todaStats;

        return $data;
    }

    /**
     * Build the live JSON payload used by the dashboard polling endpoint.
     */
    public function liveJson(array $stats): array
    {
        return [
            'totalOperators' => $stats['totalOperators'],
            'activeOperators' => $stats['activeOperators'],
            'totalRatings' => $stats['totalRatings'],
            'averageRating' => round((float) $stats['averageRating'], 1),
            'totalComplaints' => $stats['totalComplaints'],
            'totalOfficers' => $stats['totalOfficers'] ?? null,
            'totalTodas' => $stats['totalTodas'],
            'pendingReview' => $stats['pendingReview'] ?? null,
            'unreadCount' => Notification::forUser(Auth::id())->unread()->count(),
            'complaintStats' => $stats['complaintStats']->map(function ($c) {
                return ['complaint_type' => $c->complaint_type, 'total' => (int) $c->total];
            }),
            'ratingDistribution' => $stats['ratingDistribution'],
            'complaintsHtml' => view('partials.dashboard-list-complaints', ['recentComplaints' => $stats['recentComplaints']])->render(),
            'topHtml' => view('partials.dashboard-list-top', ['topOperators' => $stats['topOperators']])->render(),
            'ratingsHtml' => view('partials.dashboard-list-ratings', ['recentRatings' => $stats['recentRatings']])->render(),
        ];
    }
}
