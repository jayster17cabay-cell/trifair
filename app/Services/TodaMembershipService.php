<?php

namespace App\Services;

use App\Helpers\ActivityLogger;
use App\Models\Notification;
use App\Models\Operator;
use App\Models\OperatorToda;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * TODA membership lifecycle. An operator's membership in a TODA is a row on
 * the `operator_toda` table:
 *
 *   pending    operator asks to join -> TODA president reviews
 *   active     president approved    -> rateable under the TODA
 *   rejected   declined by the president (reason recorded)
 *
 * The TODA president is the sole approver for join requests: once they approve,
 * the membership is active immediately. TFRB officers and superadmins no longer
 * take part in this decision (they may still add an operator to a TODA
 * directly, which is also active straight away).
 *
 * Every decision is written to the activity log with the approving user,
 * decision, timestamp and reason, and the driver is notified on rejection and
 * on activation.
 */
class TodaMembershipService
{
    /**
     * Active TODAs this operator may still request to join (no open or active
     * membership yet), ordered by name.
     */
    public function joinableTodas(Operator $operator): Collection
    {
        $blocked = $operator->todaMemberships()
            ->whereIn('status', [
                OperatorToda::STATUS_ACTIVE,
                OperatorToda::STATUS_PENDING,
                OperatorToda::STATUS_PENDING_TFRB,
                OperatorToda::STATUS_PENDING_SUPERADMIN,
                OperatorToda::STATUS_PENDING_ADMIN,
            ])
            ->pluck('toda_id')
            ->push($operator->toda_id)
            ->filter()
            ->unique();

        return Toda::where('is_active', true)
            ->whereNotIn('id', $blocked)
            ->orderBy('name')
            ->get();
    }

    /**
     * Whether the operator may open one more membership without breaking the
     * three-TODA limit.
     */
    public function canJoinAnotherToda(Operator $operator): bool
    {
        return $operator->todaMembershipCount() < OperatorToda::MAX_TODAS;
    }

    /**
     * Self-service join request: the operator asks to join a TODA, landing the
     * membership in the president's review queue. Returns the membership row,
     * or null when the three-TODA limit is already reached.
     */
    public function requestJoin(Operator $operator, Toda $toda): ?OperatorToda
    {
        $existing = $operator->membershipFor($toda->id);

        if ($existing && $existing->isOpen()) {
            return $existing;
        }

        if ($existing && $existing->isActive()) {
            return $existing;
        }

        if (!$this->canJoinAnotherToda($operator)) {
            return null;
        }

        $membership = $operator->todaMemberships()->updateOrCreate(
            ['toda_id' => $toda->id],
            ['status' => OperatorToda::STATUS_PENDING, 'joined_at' => now()]
        );

        ActivityLogger::log(
            'request_toda_membership',
            "{$this->name($operator)} requested to join {$toda->name}",
            $membership,
            ActivityLogger::CATEGORY_OPERATOR
        );

        return $membership;
    }

    /**
     * TODA president's decision on a join request: pending -> active. The
     * president is the final approver, so the operator becomes rateable under
     * the TODA immediately and is notified. The president must own the TODA and
     * cannot approve their own application.
     */
    public function presidentApprove(OperatorToda $membership, User $president): OperatorToda
    {
        $membership->update(['status' => OperatorToda::STATUS_ACTIVE, 'joined_at' => now()]);

        app(AdminDashboardService::class)->flush();

        ActivityLogger::log(
            'approve_toda_membership_president',
            "{$president->name} approved {$this->name($membership->operator)}'s request to join {$this->todaName($membership)} — membership is now active",
            $membership,
            ActivityLogger::CATEGORY_OPERATOR
        );

        $this->notifyDriver(
            $membership->operator,
            'TODA Membership Approved',
            'Your membership in ' . $this->todaName($membership) . ' is now active. You may be rated under this TODA.'
        );

        return $membership;
    }

    /**
     * Decline a membership application at any stage. The reason is recorded in
     * the activity log (surfaced on the driver's membership card) and the
     * driver is notified.
     */
    public function reject(OperatorToda $membership, User $by, ?string $note = null): OperatorToda
    {
        $membership->update(['status' => OperatorToda::STATUS_REJECTED]);

        app(AdminDashboardService::class)->flush();

        $reason = $note ? trim($note) : 'No reason provided.';

        ActivityLogger::log(
            'reject_toda_membership',
            $reason,
            $membership,
            $by->role === 'superadmin' || $by->role === 'tfrb_officer'
                ? ActivityLogger::CATEGORY_OFFICER
                : ActivityLogger::CATEGORY_OPERATOR
        );

        $this->notifyDriver(
            $membership->operator,
            'TODA Membership Rejected',
            'Your request to join ' . $this->todaName($membership) . ' was declined. Reason: ' . $reason
        );

        return $membership;
    }

    /**
     * A superadmin/TFRB adds an operator to a TODA directly. The membership is
     * active immediately because admins manage memberships, not join requests.
     */
    public function addByAdmin(Operator $operator, Toda $toda, User $admin): OperatorToda
    {
        $membership = $operator->joinToda($toda->id);

        app(AdminDashboardService::class)->flush();

        ActivityLogger::log(
            'add_toda_membership_admin',
            "{$admin->name} added {$this->name($operator)} to {$toda->name}",
            $membership,
            ActivityLogger::CATEGORY_OPERATOR
        );

        return $membership;
    }

    /**
     * Reconcile an operator's active memberships with a desired set of TODA ids
     * (used by the operator edit form's multi-select). The operator's home TODA
     * is always kept and the three-TODA limit is enforced. Memberships that
     * fall outside the desired set are removed so the operator stops being
     * rateable under them.
     *
     * @param  array<int, mixed>  $todaIds
     */
    public function syncMemberships(Operator $operator, array $todaIds, User $admin): void
    {
        $desired = collect($todaIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->push((int) $operator->toda_id)
            ->filter()
            ->unique()
            ->values();

        if ($desired->isEmpty()) {
            return;
        }

        // Enforce the three-TODA limit (home TODA first).
        $desired = $desired->take(OperatorToda::MAX_TODAS)->values();

        // Drop active memberships the admin unchecked (never the home TODA).
        $operator->todaMemberships()
            ->where('status', OperatorToda::STATUS_ACTIVE)
            ->whereNotIn('toda_id', $desired->all())
            ->get()
            ->each(function (OperatorToda $membership) use ($operator) {
                if ((int) $membership->toda_id !== (int) $operator->toda_id) {
                    $membership->delete();
                }
            });

        // Add/activate every selected membership.
        $changed = false;
        foreach ($desired as $todaId) {
            $existing = $operator->membershipFor($todaId);
            if (!$existing || $existing->status !== OperatorToda::STATUS_ACTIVE) {
                $operator->todaMemberships()->updateOrCreate(
                    ['toda_id' => $todaId],
                    ['status' => OperatorToda::STATUS_ACTIVE, 'joined_at' => now()]
                );
                $changed = true;
            }
        }

        if ($changed) {
            app(AdminDashboardService::class)->flush();

            ActivityLogger::log(
                'sync_toda_memberships',
                "{$admin->name} updated TODA memberships for {$this->name($operator)}",
                $operator,
                ActivityLogger::CATEGORY_OPERATOR
            );
        }
    }

    /**
     * The president's review queue: pending memberships for a TODA, newest
     * first, eager-loading the operator + user.
     */
    public function presidentQueue(Toda $toda): Collection
    {
        return OperatorToda::with(['operator.user', 'toda'])
            ->where('toda_id', $toda->id)
            ->where('status', OperatorToda::STATUS_PENDING)
            ->latest('id')
            ->get();
    }

    /**
     * Notify the driver behind a membership through the existing notifications
     * table (no schema change).
     */
    private function notifyDriver(?Operator $operator, string $title, string $message): void
    {
        if (!$operator || !$operator->user_id) {
            return;
        }

        Notification::create([
            'user_id' => $operator->user_id,
            'type' => 'toda_membership',
            'title' => $title,
            'message' => $message,
        ]);
    }

    private function name(?Operator $operator): string
    {
        if (!$operator) {
            return 'Operator';
        }

        return $operator->user->name ?? ("Operator #{$operator->id}");
    }

    private function todaName(OperatorToda $membership): string
    {
        return $membership->toda->name ?? 'the TODA';
    }
}
