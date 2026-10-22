<?php

namespace App\Services;

use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;

/**
 * All data exposed to an Operator President must be scoped to the TODA they
 * govern. Every query below forces WHERE toda_id = <president's toda> at the
 * database level, so a president can never read another TODA's members or
 * ratings no matter how the URL or request parameters are manipulated. A
 * member of several TODAs therefore shows up only under the ratings filed
 * against the president's own TODA.
 */
class PresidentQueryService
{
    /**
     * Operators belonging to a given TODA (excludes archived records). A
     * member is anyone whose home TODA is this TODA, or who holds an active
     * operator_toda membership for it. The current TODA president's own
     * operator record is excluded from the members list/count so they do not
     * appear as a "member" of the TODA they preside over.
     */
    public function todaOperators(Toda $toda, ?User $user = null)
    {
        $query = Operator::query()
            ->belongsToToda($toda->id)
            ->notArchived();

        if ($user && method_exists($user, 'isOperatorPresident') && $user->isOperatorPresident()) {
            if (method_exists($user, 'isPresidentOf') && $user->isPresidentOf($toda->id)) {
                $own = $user->operator;
                if ($own) {
                    $query->where('operators.id', '!=', $own->id);
                }
            }
        }

        return $query;
    }

    /**
     * The president's own operator record (a president is also an operator and
     * may carry a personal rating). Null if none exists.
     */
    public function presidentOperator(User $user): ?Operator
    {
        return $user->operator;
    }

    /**
     * Summary stats for the president's TODA, all scoped to that TODA.
     */
    public function summary(Toda $toda, ?Operator $ownOperator): array
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $memberQuery = $this->todaOperators($toda, $user);

        $memberIdsForList = (clone $memberQuery)->pluck('id');
        $totalMembers = $memberIdsForList->count();

        // The average must still count the president's own operator record —
        // they are excluded from the members list only because they preside
        // over the TODA, not because their ratings stopped mattering.
        $ratingIds = $memberIdsForList->all();
        if ($ownOperator
            && ($ownOperator->isMemberOfToda($toda->id) || (int) $ownOperator->toda_id === (int) $toda->id)
            && !in_array($ownOperator->id, $ratingIds)
        ) {
            $ratingIds[] = $ownOperator->id;
        }

        $memberRatingAgg = Rating::query()
            ->whereIn('operator_id', $ratingIds)
            ->where('toda_id', $toda->id)
            ->countsTowardRating()
            ->selectRaw('COUNT(*) as total, AVG(rating) as avg')
            ->first();

        $avgMemberRating = $memberRatingAgg && $memberRatingAgg->total > 0
            ? round((float) $memberRatingAgg->avg, 1)
            : 0;

        $pendingComplaints = Rating::query()
            ->whereIn('operator_id', $memberIdsForList)
            ->where('toda_id', $toda->id)
            ->isValid()
            ->isComplaint()
            ->whereNull('is_accepted')
            ->where('is_solved', false)
            ->count();

        $totalComplaints = Rating::query()
            ->whereIn('operator_id', $memberIdsForList)
            ->where('toda_id', $toda->id)
            ->isValid()
            ->isComplaint()
            ->count();

        // The president's own averages/count are scoped to this TODA too, so
        // their multi-TODA rating is never mixed across organizations.
        $ownStats = $ownOperator
            ? $ownOperator->countableRatings()
                ->where('toda_id', $toda->id)
                ->selectRaw('COUNT(*) as total, AVG(rating) as avg')
                ->first()
            : null;

        $ownAvg = $ownStats && $ownStats->total > 0 ? round((float) $ownStats->avg, 1) : 0;
        $ownTotal = $ownStats ? (int) $ownStats->total : 0;

        return [
            'totalMembers' => $totalMembers,
            'avgMemberRating' => $avgMemberRating,
            'pendingComplaints' => $pendingComplaints,
            'totalComplaints' => $totalComplaints,
            'ownAvg' => $ownAvg,
            'ownTotal' => $ownTotal,
        ];
    }

    /**
     * The president's own recent ratings within their TODA.
     */
    public function ownRecentRatings(Toda $toda, ?Operator $ownOperator, int $limit = 8)
    {
        if (!$ownOperator) {
            return collect();
        }
        return $ownOperator->countableRatings()
            ->where('toda_id', $toda->id)
            ->with('response')
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Paginated, searchable member list for the president's TODA. Rating and
     * complaint counts are scoped to the TODA's own ratings.
     */
    public function members(Toda $toda, ?string $search, ?string $status)
    {
        // A lapsed suspension must never leave a member stuck in the suspended
        // bucket, so reconcile before the list (and its counts) are built.
        Operator::endExpiredSuspensions();

        $user = \Illuminate\Support\Facades\Auth::user();
        $base = $this->todaOperators($toda, $user)->with('user')->whereHas('user');

        if ($search) {
            $base->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })
                    ->orWhere('body_number', 'like', "%{$search}%")
                    ->orWhere('plate_number', 'like', "%{$search}%");
            });
        }

        if ($status && in_array($status, ['active', 'inactive', 'suspended', 'pending', 'rejected'], true)) {
            $base->where('status', $status);
        }

        return $base
            ->withCount([
                'ratings' => fn ($q) => $q->isValid()->where('toda_id', $toda->id),
                'ratings as complaint_count' => fn ($q) => $q->isValid()->isComplaint()->where('toda_id', $toda->id),
            ])
            ->withAvg(['ratings as valid_rating_avg' => fn ($q) => $q->isValid()->where('toda_id', $toda->id)], 'rating')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();
    }

    /**
     * Per-member rating data for the breakdown chart (only their own TODA's
     * ratings).
     */
    public function memberBreakdown(Toda $toda)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $memberIds = $this->todaOperators($toda, $user)->pluck('id');

        $members = Operator::query()
            ->whereIn('id', $memberIds)
            ->whereHas('user')
            ->with('user')
            ->withCount([
                'ratings' => fn ($q) => $q->isValid()->where('toda_id', $toda->id),
                'ratings as complaint_count' => fn ($q) => $q->isValid()->isComplaint()->where('toda_id', $toda->id),
            ])
            ->get();

        $avgs = Rating::query()
            ->whereIn('operator_id', $memberIds)
            ->where('toda_id', $toda->id)
            ->countsTowardRating()
            ->selectRaw('operator_id, AVG(rating) as avg_rating')
            ->groupBy('operator_id')
            ->pluck('avg_rating', 'operator_id');

        return $members->map(function (Operator $op) use ($avgs) {
            $avg = (float) ($avgs[$op->id] ?? 0);
            return (object) [
                'id' => $op->id,
                'name' => $op->user ? $op->user->name : 'Unknown',
                'body_number' => $op->body_number,
                'status' => $op->status,
                'average' => $avg ? round($avg, 1) : 0,
                'total_ratings' => $op->ratings_count,
                'complaints' => $op->complaint_count,
            ];
        })
            ->sortByDesc('average')
            ->values();
    }

    /**
     * A single member of the president's TODA, or null if the id does not
     * belong to the president's TODA (enforces the org boundary).
     */
    public function member(Toda $toda, int $id): ?Operator
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        return $this->todaOperators($toda, $user)
            ->with('user')
            ->find($id);
    }

    /**
     * A member's rating/complaint history under the president's TODA only.
     */
    public function memberRatings(Toda $toda, Operator $member)
    {
        if (!$member->isMemberOfToda($toda->id)) {
            return collect();
        }
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && method_exists($user, 'isOperatorPresident') && $user->isPresidentOf($toda->id)) {
            if ($user->operator && (int) $user->operator->id === (int) $member->id) {
                abort(403);
            }
        }
        return $member->ratings()
            ->where('toda_id', $toda->id)
            ->isValid()
            ->with('response', 'proofs')
            ->latest()
            ->paginate(10);
    }

    /**
     * Single aggregate (average, total, complaint count) for a member's valid
     * ratings under the president's TODA — one round-trip instead of three for
     * the member detail modal.
     */
    public function memberStats(Toda $toda, Operator $member)
    {
        return $member->validRatings()
            ->where('toda_id', $toda->id)
            ->selectRaw(
                'COUNT(*) as total'
                . ', AVG(rating) as avg'
                . ', SUM(CASE WHEN complaint_type IS NOT NULL THEN 1 ELSE 0 END) as complaints'
            )
            ->first();
    }
}
