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
        $ratingKey = $request->query('rating');
        $allowStar = ['1', '2'];
        $ratingFilter = in_array($ratingKey, $allowStar, true) ? (int) $ratingKey : null;
        $search = trim((string) $request->query('search'));

        $base = Rating::isValid()->isComplaint();

        // One round-trip instead of seven: the status/star tallies are all
        // derived from the same base query via conditional aggregation. On a
        // remote database each avoided count() saves a full network round-trip.
        $counts = (clone $base)->selectRaw(
            'COUNT(*) as total_count'
            . ', SUM(CASE WHEN is_accepted IS NULL AND is_solved = false THEN 1 ELSE 0 END) as pending_count'
            . ', SUM(CASE WHEN is_accepted = true AND is_solved = false THEN 1 ELSE 0 END) as accepted_count'
            . ', SUM(CASE WHEN is_accepted = false THEN 1 ELSE 0 END) as rejected_count'
            . ', SUM(CASE WHEN is_solved = true THEN 1 ELSE 0 END) as solved_count'
            . ', SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as star1_count'
            . ', SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as star2_count'
        )->first();

        $pendingCount = (int) ($counts->pending_count ?? 0);
        $acceptedCount = (int) ($counts->accepted_count ?? 0);
        $rejectedCount = (int) ($counts->rejected_count ?? 0);
        $solvedCount = (int) ($counts->solved_count ?? 0);
        $totalCount = (int) ($counts->total_count ?? 0);
        $star1Count = (int) ($counts->star1_count ?? 0);
        $star2Count = (int) ($counts->star2_count ?? 0);

        // Global count of proof files attached to valid complaints (not just the
        // current page) so the stats bar stays accurate across pagination.
        $proofsTotal = (int) DB::table('ratings')
            ->join('rating_proofs', 'rating_proofs.rating_id', '=', 'ratings.id')
            ->where('ratings.is_valid', true)
            ->whereNotNull('ratings.complaint_type')
            ->count();

        if ($filter === 'pending') {
            $base->whereNull('is_accepted')->where('is_solved', false);
        } elseif ($filter === 'accepted' || $filter === 'reviewed') {
            // 'reviewed' is kept as a backwards-compatible alias for accepted.
            $filter = 'accepted';
            $base->where('is_accepted', true)->where('is_solved', false);
        } elseif ($filter === 'rejected') {
            $base->where('is_accepted', false);
        } elseif ($filter === 'solved') {
            $base->isSolved();
        } elseif (!in_array($filter, ['', 'all'], true)) {
            $filter = 'pending';
            $base->whereNull('is_accepted')->where('is_solved', false);
        }

        if ($ratingFilter) {
            $base->where('rating', $ratingFilter);
        }

        if ($search !== '') {
            $this->applyReferenceSearch($base, $search);
        }

        $complaints = $base->with(['operator.user', 'proofs', 'response', 'operatorProofs', 'resolutionProofs', 'reviewer'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return compact('complaints', 'filter', 'ratingFilter', 'search', 'pendingCount', 'acceptedCount', 'rejectedCount', 'solvedCount', 'totalCount', 'star1Count', 'star2Count', 'proofsTotal');
    }

    /**
     * The reference number (TFR-2026-0042) is a computed accessor, not a real
     * column, so it cannot be searched with SQL directly. Instead the input is
     * resolved to the underlying rating id: a full reference, a bare ticket
     * number, or any string ending in a number all map to that row id.
     */
    private function applyReferenceSearch($query, string $search): void
    {
        if (preg_match('/(\d+)$/', $search, $m)) {
            $query->where('id', (int) $m[1]);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    public function ratingsData(Request $request): array
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $operatorId = $request->query('operator_id') ? (int) $request->query('operator_id') : null;
        $ratingKey = (string) $request->query('rating');
        $ratingFilter = in_array($ratingKey, ['1', '2', '3', '4', '5'], true) ? (int) $ratingKey : null;

        // Same base the dashboard "Ratings" KPI and the rating-distribution
        // chart use (plain feedback plus accepted complaints) so the tally on
        // this page always matches the dashboard numbers.
        $base = Rating::isValid()->countsTowardRating();
        $this->applyDateRange($base, $dateFrom, $dateTo);
        if ($operatorId) {
            $base->where('operator_id', $operatorId);
        }

        // One round-trip for every tally shown on the page: total plus the
        // per-star breakdown. Computed before the star filter is applied so the
        // counts always reflect the date/operator scope the officer can switch
        // between.
        $counts = (clone $base)->selectRaw(
            'COUNT(*) as total_count'
            . ', SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as star1_count'
            . ', SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as star2_count'
            . ', SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as star3_count'
            . ', SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as star4_count'
            . ', SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as star5_count'
            . ', SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as good_count'
            . ', SUM(CASE WHEN is_reviewed = true THEN 1 ELSE 0 END) as reviewed_count'
        )->first();

        $totalCount = (int) ($counts->total_count ?? 0);
        $starCounts = [
            1 => (int) ($counts->star1_count ?? 0),
            2 => (int) ($counts->star2_count ?? 0),
            3 => (int) ($counts->star3_count ?? 0),
            4 => (int) ($counts->star4_count ?? 0),
            5 => (int) ($counts->star5_count ?? 0),
        ];
        $goodCount = (int) ($counts->good_count ?? 0);
        $reviewedCount = (int) ($counts->reviewed_count ?? 0);
        $proofsCount = (clone $base)->has('proofs')->count();

        if ($ratingFilter) {
            $base->where('rating', $ratingFilter);
        }

        $ratings = (clone $base)
            ->with(['operator.user', 'proofs', 'response', 'operatorProofs'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return compact('ratings', 'goodCount', 'reviewedCount', 'proofsCount', 'dateFrom', 'dateTo', 'operatorId', 'ratingFilter', 'starCounts', 'totalCount');
    }

    public function applyDateRange($query, ?string $dateFrom, ?string $dateTo): void
    {
        if ($from = $this->parseDate($dateFrom)) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $this->parseDate($dateTo)) {
            $query->whereDate('created_at', '<=', $to);
        }
    }

    /**
     * Parse a user-supplied date, returning null for blank or malformed input so
     * a hand-edited URL can never trigger a 500 from the admin filters.
     */
    private function parseDate(?string $value): ?\Illuminate\Support\Carbon
    {
        $value = $value ? trim($value) : null;
        if (! $value) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function reportsData(Request $request): array
    {
        [$query, $filters] = $this->reportsBaseQuery($request);

        $todas = Toda::orderBy('name')->get();

        $operators = $query->orderByRaw('COALESCE(vr.valid_ratings_count, 0) DESC')
            ->orderBy('operators.id')
            ->paginate(25)
            ->withQueryString();

        return array_merge(compact('operators', 'todas'), $filters);
    }

    /**
     * Shared operator-performance query for the Reports page and its export, so
     * the downloaded file always matches the TODA / date range / minimum rating
     * the user selected on screen.
     *
     * @return array{0: \Illuminate\Database\Eloquent\Builder, 1: array<string, mixed>}
     */
    private function reportsBaseQuery(Request $request): array
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $todaId = $request->query('toda_id') ? (int) $request->query('toda_id') : null;
        $minRating = ($request->query('min_rating') !== null && $request->query('min_rating') !== '')
            ? (float) $request->query('min_rating')
            : null;

        $query = Operator::with('user', 'toda')
            ->whereHas('user', function ($u) {
                $u->where('role', 'operator');
            })
            ->whereNotIn('operators.status', ['pending', 'rejected'])
            ->whereNull('operators.archived_at')
            ->when($todaId, fn ($q) => $q->where('operators.toda_id', $todaId));

        // Average rating across the optional period. The subquery is filtered by
        // created_at so the computed average matches the selected date range.
        $from = $this->parseDate($dateFrom);
        $to = $this->parseDate($dateTo);
        $periodWhere = '';
        if ($from) {
            $periodWhere .= " and created_at >= " . DB::connection()->getPdo()->quote($from->copy()->startOfDay());
        }
        if ($to) {
            $periodWhere .= " and created_at <= " . DB::connection()->getPdo()->quote($to->copy()->endOfDay());
        }
        // Only accepted complaints influence an operator's average; pending and
        // rejected complaints never count.
        $sub = '(select operator_id, avg(rating) as valid_ratings_avg_rating, count(*) as valid_ratings_count from ratings where is_valid = true and (complaint_type is null or is_accepted = true)' . $periodWhere . ' group by operator_id)';

        $query->leftJoin(DB::raw($sub . ' as vr'), 'vr.operator_id', '=', 'operators.id')
            ->select('operators.*', 'vr.valid_ratings_avg_rating', 'vr.valid_ratings_count');

        if ($minRating !== null) {
            $query->whereRaw('COALESCE(vr.valid_ratings_avg_rating, 0) >= ?', [$minRating]);
        }

        return [$query, compact('dateFrom', 'dateTo', 'todaId', 'minRating')];
    }

    /**
     * Shared base query for the operators list, honoring search + status
     * filters. Status may also be "archived" to browse archived operators.
     */
    public function operatorsBaseQuery(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $account = $request->query('account', 'all');

        // A TODA president is also an operator (they keep their own Operator row
        // so they can carry a rating), so they still appear here by name — they
        // are additionally managed under the Presidents section.
        $query = Operator::with('user', 'toda.president', 'currentTerm', 'currentSuspension.suspender')
            ->whereHas('user', function ($u) {
                $u->whereIn('role', ['operator', 'operator_president']);
            });

        if ($status === 'archived') {
            $query->archived();
        } elseif ($status && in_array($status, ['active', 'inactive', 'suspended', 'pending', 'rejected'])) {
            $query->notArchived()->where('status', $status);
        } else {
            $status = null;
            $query->notArchived();
        }

        if ($account && $account !== 'all') {
            $accountActive = $account === 'active';
            $query->whereHas('user', function ($u) use ($accountActive) {
                $u->where('is_active', $accountActive);
            });
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
        // Keep the visible status honest: any operator whose franchise has
        // lapsed is flipped to inactive, and any lapsed TODA suspension is
        // closed, before the list and its tallies are read, so the admin never
        // sees a stale status.
        Operator::deactivateExpiredFranchises();
        Operator::endExpiredSuspensions();

        $operators = $this->operatorsBaseQuery($request)->latest()->paginate(10)->withQueryString();

        $search = $request->query('search');
        $status = $request->query('status');
        $account = $request->query('account', 'all');

        $archivedCount = Operator::archived()->count();

        // One round-trip instead of four: the status/account tallies share the
        // same not-archived operator set, so a single join against users plus
        // conditional aggregation returns them all at once.
        $tally = Operator::notArchived()
            ->join('users', 'users.id', '=', 'operators.user_id')
            ->whereIn('users.role', ['operator', 'operator_president'])
            ->selectRaw(
                "SUM(CASE WHEN operators.status = 'pending' THEN 1 ELSE 0 END) as pending_count"
                . ", SUM(CASE WHEN operators.status = 'suspended' THEN 1 ELSE 0 END) as suspended_count"
                . ", SUM(CASE WHEN operators.status = 'active' THEN 1 ELSE 0 END) as active_status_count"
                . ", SUM(CASE WHEN operators.status = 'inactive' THEN 1 ELSE 0 END) as inactive_status_count"
                . ', SUM(CASE WHEN users.is_active = true THEN 1 ELSE 0 END) as accounts_active_count'
            )
            ->first();

        $pendingCount = (int) $tally->pending_count;
        $suspendedCount = (int) $tally->suspended_count;
        $activeStatusCount = (int) $tally->active_status_count;
        $inactiveStatusCount = (int) $tally->inactive_status_count;
        $accountsActiveCount = (int) $tally->accounts_active_count;

        return compact('operators', 'search', 'status', 'account', 'archivedCount', 'pendingCount', 'suspendedCount', 'activeStatusCount', 'inactiveStatusCount', 'accountsActiveCount');
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

    public function ratingsForExport(Request $request): \Illuminate\Support\Collection
    {
        $query = Rating::isValid()->countsTowardRating()->with(['operator.user', 'response']);
        $this->applyDateRange($query, $request->query('date_from'), $request->query('date_to'));
        $operatorId = $request->query('operator_id') ? (int) $request->query('operator_id') : null;
        if ($operatorId) {
            $query->where('operator_id', $operatorId);
        }
        $ratingKey = (string) $request->query('rating');
        if (in_array($ratingKey, ['1', '2', '3', '4', '5'], true)) {
            $query->where('rating', (int) $ratingKey);
        }

        return $query->latest()
            ->get()
            ->map(function ($rating) {
                return [
                    'id' => $rating->id,
                    'operator' => $rating->operator->user->name ?? 'Unknown',
                    'rating' => $rating->rating,
                    'comment' => $rating->reason ?? '',
                    'date' => $rating->created_at?->format('Y-m-d H:i'),
                ];
            });
    }

    public function complaintsForExport(Request $request): \Illuminate\Support\Collection
    {
        // "All" is submitted as an empty string by the dropdown, which must NOT
        // fall through to the pending filter (that would export pending only).
        $filter = trim((string) $request->query('filter', 'pending'));
        $operatorId = $request->query('operator_id');
        $ratingKey = $request->query('rating');
        $search = trim((string) $request->query('search'));
        $base = Rating::isValid()->isComplaint();
        if (in_array($ratingKey, ['1', '2'], true)) {
            $base->where('rating', (int) $ratingKey);
        }
        if ($filter === 'accepted' || $filter === 'reviewed') {
            $filter = 'accepted';
            $base->where('is_accepted', true)->where('is_solved', false);
        } elseif ($filter === 'rejected') {
            $base->where('is_accepted', false);
        } elseif ($filter === 'solved') {
            $base->isSolved();
        } elseif (!in_array($filter, ['', 'all'], true)) {
            $base->whereNull('is_accepted')->where('is_solved', false);
        }
        if ($operatorId) {
            $base->where('operator_id', $operatorId);
        }
        if ($search !== '') {
            $this->applyReferenceSearch($base, $search);
        }
        $this->applyDateRange($base, $request->query('date_from'), $request->query('date_to'));

        return $base->with(['operator.user', 'response'])
            ->latest()
            ->get()
            ->map(function ($complaint) {
                return [
                    'id' => $complaint->id,
                    'reference' => $complaint->reference_number,
                    'operator' => $complaint->operator->user->name ?? 'Unknown',
                    'body_number' => $complaint->operator->body_number ?? '',
                    'plate_number' => $complaint->operator->plate_number ?? '',
                    'rating' => $complaint->rating,
                    'type' => $complaint->complaint_type ?? '',
                    'details' => $complaint->complaint_details ?? '',
                    'status' => $complaint->status_label,
                    'date' => $complaint->created_at?->format('Y-m-d H:i'),
                ];
            });
    }

    public function reportsForExport(Request $request = null): \Illuminate\Support\Collection
    {
        $request = $request ?? request();

        [$query, ] = $this->reportsBaseQuery($request);

        if ($request->query('operator_id')) {
            $query->where('operators.id', (int) $request->query('operator_id'));
        }

        return $query->orderByRaw('COALESCE(vr.valid_ratings_count, 0) DESC')
            ->orderBy('operators.id')
            ->get()
            ->map(function ($operator) {
                return [
                    'name' => $operator->user->name ?? 'Unknown',
                    'toda' => $operator->toda?->name ?? 'Unassigned',
                    'body_number' => $operator->body_number ?? '—',
                    'plate_number' => $operator->plate_number ?? '—',
                    'total_trips' => $operator->valid_ratings_count ?? 0,
                    'avg_rating' => number_format((float) ($operator->valid_ratings_avg_rating ?? 0), 2),
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
                $query->whereNull('archived_at')
                    ->whereHas('user', fn ($u) => $u->where('role', 'operator'));
            },
            'operators as active_operators_count' => function ($query) {
                $query->where('status', 'active')
                    ->whereNull('archived_at')
                    ->whereHas('user', fn ($u) => $u->where('role', 'operator'));
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
            ->withCount([
                'ratings' => fn ($q) => $q->isValid(),
                'ratings as complaint_count' => fn ($q) => $q->isValid()->isComplaint(),
            ])
            ->withAvg(['ratings as valid_rating_avg' => fn ($q) => $q->isValid()], 'rating')
            // Presidents are also operators (they keep their own ratable row),
            // but they are managed under the Presidents section — never from the
            // TODA members modal, where they could be edited/deleted/rejected.
            ->whereHas('user', fn ($u) => $u->where('role', 'operator'))
            ->whereNull('archived_at')
            ->get();
    }

    /**
     * AJAX endpoint data for the reports page: trip history for a single
     * operator, capped at 200 trips so the page stays fast at scale.
     */
    public function reportTripsData(Operator $operator, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        abort_unless(
            in_array($operator->status, ['active', 'inactive'], true)
            && $operator->user?->role === 'operator'
            && !$operator->isArchived(),
            404
        );

        // Match the reports table row exactly: a trip counts when the rating is
        // valid AND (it has no complaint OR the complaint was accepted), within
        // the same date period shown on that row (see reportsData()).
        $query = $operator->ratings()->isValid()->countsTowardRating();

        if ($from = $this->parseDate($dateFrom)) {
            $query->where('created_at', '>=', $from->copy()->startOfDay());
        }
        if ($to = $this->parseDate($dateTo)) {
            $query->where('created_at', '<=', $to->copy()->endOfDay());
        }

        $totalTrips = (clone $query)->count();
        $trips = $query->latest()->limit(200)->get();

        return compact('operator', 'trips', 'totalTrips');
    }
}
