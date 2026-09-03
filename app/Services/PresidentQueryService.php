<?php

namespace App\Services;

use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;

/**
 * All data exposed to an Operator President must be scoped to the TODA they
 * govern. Every query below forces WHERE toda_id = <president's toda> at the
 * database level, so a president can never read another TODA's members no
 * matter how the URL or request parameters are manipulated.
 */
class PresidentQueryService
{
    /**
     * Operators belonging to a given TODA (excludes archived records).
     */
    public function todaOperators(Toda $toda)
    {
        return Operator::query()
            ->where('toda_id', $toda->id)
            ->notArchived();
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
     * Summary stats for the president's TODA.
     */
    public function summary(Toda $toda, ?Operator $ownOperator): array
    {
        $memberQuery = $this->todaOperators($toda);

        $memberIds = (clone $memberQuery)->pluck('id');

        $totalMembers = $memberIds->count();

        $memberRatingAgg = Rating::query()
            ->whereIn('operator_id', $memberIds)
            ->isValid()
            ->selectRaw('COUNT(*) as total, AVG(rating) as avg, SUM(CASE WHEN complaint_type IS NOT NULL THEN 1 ELSE 0 END) as complaints')
            ->first();

        $avgMemberRating = $memberRatingAgg && $memberRatingAgg->total > 0
            ? round((float) $memberRatingAgg->avg, 1)
            : 0;

        $pendingComplaints = (int) ($memberRatingAgg->complaints ?? 0);

        $ownAvg = $ownOperator
            ? round((float) $ownOperator->ratings()->isValid()->avg('rating'), 1)
            : 0;
        $ownTotal = $ownOperator ? $ownOperator->ratings()->isValid()->count() : 0;

        return [
            'totalMembers' => $totalMembers,
            'avgMemberRating' => $avgMemberRating,
            'pendingComplaints' => $pendingComplaints,
            'ownAvg' => $ownAvg,
            'ownTotal' => $ownTotal,
        ];
    }

    /**
     * The president's own recent ratings.
     */
    public function ownRecentRatings(?Operator $ownOperator, int $limit = 8)
    {
        if (!$ownOperator) {
            return collect();
        }
        return $ownOperator->ratings()
            ->isValid()
            ->with('response')
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Paginated, searchable member list for the president's TODA.
     */
    public function members(Toda $toda, ?string $search, ?string $status)
    {
        $base = $this->todaOperators($toda)->with('user');

        if ($search) {
            $base->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })
                    ->orWhere('body_number', 'like', "%{$search}%")
                    ->orWhere('plate_number', 'like', "%{$search}%");
            });
        }

        if ($status && in_array($status, ['active', 'inactive', 'pending', 'rejected'], true)) {
            $base->where('status', $status);
        }

        return $base
            ->withCount([
                'ratings' => fn ($q) => $q->isValid(),
                'ratings as complaint_count' => fn ($q) => $q->isValid()->isComplaint(),
            ])
            ->latest('id')
            ->paginate(12)
            ->withQueryString();
    }

    /**
     * Per-member rating data for the breakdown chart (only their own TODA).
     */
    public function memberBreakdown(Toda $toda)
    {
        $memberIds = $this->todaOperators($toda)->pluck('id');

        $rows = Operator::query()
            ->whereIn('id', $memberIds)
            ->whereHas('user')
            ->with('user')
            ->withCount([
                'ratings' => fn ($q) => $q->isValid(),
                'ratings as complaint_count' => fn ($q) => $q->isValid()->isComplaint(),
            ])
            ->get();

        return $rows->map(function (Operator $op) {
            $avg = $op->ratings()->isValid()->avg('rating');
            return (object) [
                'id' => $op->id,
                'name' => $op->user->name,
                'body_number' => $op->body_number,
                'status' => $op->status,
                'average' => $avg ? round((float) $avg, 1) : 0,
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
        return $this->todaOperators($toda)
            ->with('user')
            ->find($id);
    }

    /**
     * A member's rating/complaint history, only reachable for own-TODA members.
     */
    public function memberRatings(Toda $toda, Operator $member)
    {
        if ((int) $member->toda_id !== (int) $toda->id) {
            return collect();
        }
        return $member->ratings()
            ->isValid()
            ->with('response')
            ->latest()
            ->paginate(10);
    }
}
