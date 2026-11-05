<?php

namespace App\Services;

use App\Models\Operator;
use App\Models\OperatorToda;
use App\Models\Rating;
use App\Models\User;

/**
 * Decides who owns a passenger complaint and who may act on it.
 *
 * Routing, evaluated once (at creation and in the legacy backfill) and
 * stored on the complaint as routed_to / forwarded_at:
 *
 *  1. Complaint filed against an operator who is themselves a TODA president
 *     → TFRB / superadmin (routed_to stays NULL).
 *  2. Operator is unassigned (no active TODA membership and no legacy home
 *     TODA) → TFRB / superadmin.
 *  3. Otherwise → the president of the TODA the ride was rated under
 *     (ratings.toda_id, falling back to the operator's legacy home TODA).
 *     If that TODA has no president → TFRB / superadmin.
 *
 * Escalation is one-way: a president who cannot resolve the complaint
 * forwards it to TFRB/superadmin (forwarded_at is stamped). From then on
 * only TFRB/superadmin may act; the president's action rights are sealed
 * for good (they keep read access only). Until a complaint is forwarded,
 * TFRB/superadmin see it as read-only with an "Awaiting TODA president"
 * badge — never as an actionable case.
 */
class ComplaintRoutingService
{
    /**
     * User id of the responsible TODA president, or null when the complaint
     * belongs to TFRB / superadmin.
     */
    public function assigneeId(Operator $operator, ?int $todaId): ?int
    {
        // A TODA president never judges complaints filed against themselves
        // (or a fellow president) — those go straight to TFRB / superadmin.
        if (($operator->user->role ?? null) === 'operator_president') {
            return null;
        }

        $todaId = $todaId ?? $this->legacyHomeTodaId($operator);

        if (!$todaId) {
            return null;
        }

        return $this->presidentIdFor((int) $todaId);
    }

    /**
     * Re-derive the assignee for an existing complaint row (legacy backfill).
     */
    public function assigneeIdForRating(Rating $complaint): ?int
    {
        return $this->assigneeId($complaint->operator, $complaint->toda_id ? (int) $complaint->toda_id : null);
    }

    /**
     * Which queue's perspective the user may act from on this complaint:
     * 'tfrb' (tfrb_officer / superadmin), 'president', or null for read-only.
     */
    public function actingAs(User $user, Rating $complaint): ?string
    {
        if ($complaint->complaint_type === null) {
            return null;
        }

        if (in_array($user->role, ['tfrb_officer', 'superadmin'], true)) {
            return $this->isTfrbActionable($complaint) ? 'tfrb' : null;
        }

        if ($user->role === 'operator_president') {
            return (int) ($complaint->routed_to ?? 0) === (int) $user->id && $complaint->forwarded_at === null
                ? 'president'
                : null;
        }

        return null;
    }

    /**
     * A complaint is TFRB-actionable when it was never routed to a president
     * (direct cases) or the owning president has already forwarded it.
     */
    public function isTfrbActionable(Rating $complaint): bool
    {
        return $complaint->routed_to === null || $complaint->forwarded_at !== null;
    }

    /**
     * Small badge describing who owns the case, safe for either queue view.
     *
     * @return array{label: string, icon: string, class: string}
     */
    public function assigneeBadge(Rating $complaint): array
    {
        if ($complaint->routed_to === null) {
            return ['label' => 'TFRB / Superadmin', 'icon' => 'bi-shield-check', 'class' => 'tw-badge-navy'];
        }

        if ($complaint->forwarded_at !== null) {
            return ['label' => 'Forwarded to TFRB', 'icon' => 'bi-send-fill', 'class' => 'tw-badge-blue'];
        }

        return ['label' => 'Awaiting TODA president', 'icon' => 'bi-person-badge', 'class' => 'tw-badge-amber'];
    }

    /**
     * One-line note shown in the locked state so each role understands why
     * the action buttons are missing.
     */
    public function assignmentNote(Rating $complaint): string
    {
        if ($complaint->routed_to === null) {
            return 'Handled by the TFRB / superadmin.';
        }

        if ($complaint->forwarded_at !== null) {
            return 'Forwarded to the TFRB on ' . $complaint->forwarded_at->format('M j, Y')
                . ' — only TFRB / superadmin can act on it now.';
        }

        $president = $complaint->routedToPresident?->name ?? 'the TODA president';

        return 'With ' . $president . ' (TODA president) — they can solve it or forward it to the TFRB.';
    }

    /**
     * President for a TODA — explicit presidency pivots win, with the
     * legacy user.toda_id key as a fallback for single-TODA presidencies
     * created before the is_president pivot flag existed. Newest presidency
     * pivot wins (mirrors the president switcher). Null when the TODA has
     * no assigned president.
     */
    private function presidentIdFor(int $todaId): ?int
    {
        $pivotPresident = OperatorToda::query()
            ->where('operator_toda.toda_id', $todaId)
            ->where('operator_toda.is_president', true)
            ->where('operator_toda.status', OperatorToda::STATUS_ACTIVE)
            ->join('operators', 'operators.id', '=', 'operator_toda.operator_id')
            ->orderByDesc('operator_toda.id')
            ->value('operators.user_id');

        if ($pivotPresident) {
            return (int) $pivotPresident;
        }

        $legacyPresident = User::where('role', 'operator_president')
            ->where('toda_id', $todaId)
            ->value('id');

        return $legacyPresident ? (int) $legacyPresident : null;
    }

    private function legacyHomeTodaId(Operator $operator): ?int
    {
        // Explicit but non-active membership must keep the operator on the
        // TFRB/superadmin side, matching the rating flow's legacy fallback.
        if ($operator->todaMemberships()->exists()) {
            return null;
        }

        return $operator->toda_id ?: null;
    }
}
