<?php

namespace App\Services;

use App\Helpers\ActivityLogger;
use App\Models\Operator;
use App\Models\OperatorToda;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * TODA membership lifecycle. An operator's membership in a TODA is a row on
 * the `operator_toda` table and moves through two approval stages:
 *
 *   pending        operator asks to join          -> TODA president reviews
 *   pending_admin  president approved / added it  -> superadmin/TFRB final approval
 *   active         fully approved                 -> rateable under the TODA
 *   rejected       declined at either stage
 *
 * A President may add an *existing* operator straight to the TODA (no new
 * account) but that still lands as pending_admin for the final approval; only a
 * superadmin/TFRB (or the operator edit form, which an admin drives) activates
 * a membership directly. Every transition is written to the activity log so the
 * two-stage review is auditable.
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
     * Self-service join request: the operator asks to join a TODA, landing the
     * membership in the president's review queue. Returns the membership row
     * (unchanged when one is already open/active).
     */
    public function requestJoin(Operator $operator, Toda $toda): OperatorToda
    {
        $existing = $operator->membershipFor($toda->id);

        if ($existing && in_array($existing->status, [
            OperatorToda::STATUS_ACTIVE,
            OperatorToda::STATUS_PENDING,
            OperatorToda::STATUS_PENDING_ADMIN,
        ], true)) {
            return $existing;
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
     * TODA president's first-level approval: pending -> pending_admin.
     */
    public function presidentApprove(OperatorToda $membership, User $president): OperatorToda
    {
        $membership->update(['status' => OperatorToda::STATUS_PENDING_ADMIN]);

        ActivityLogger::log(
            'approve_toda_membership_president',
            "{$president->name} approved {$this->name($membership->operator)}'s request to join {$this->todaName($membership)} — awaiting final approval",
            $membership,
            ActivityLogger::CATEGORY_OPERATOR
        );

        return $membership;
    }

    /**
     * Superadmin/TFRB final approval: any open state -> active.
     */
    public function finalApprove(OperatorToda $membership, User $admin): OperatorToda
    {
        $membership->update(['status' => OperatorToda::STATUS_ACTIVE, 'joined_at' => now()]);

        app(AdminDashboardService::class)->flush();

        ActivityLogger::log(
            'approve_toda_membership_final',
            "{$admin->name} gave final approval for {$this->name($membership->operator)} in {$this->todaName($membership)}",
            $membership,
            ActivityLogger::CATEGORY_OPERATOR
        );

        return $membership;
    }

    /**
     * Decline a membership request at either stage.
     */
    public function reject(OperatorToda $membership, User $by, ?string $note = null): OperatorToda
    {
        $membership->update(['status' => OperatorToda::STATUS_REJECTED]);

        app(AdminDashboardService::class)->flush();

        ActivityLogger::log(
            'reject_toda_membership',
            "{$by->name} rejected {$this->name($membership->operator)}'s membership in {$this->todaName($membership)}"
                . ($note ? " — {$note}" : ''),
            $membership,
            ActivityLogger::CATEGORY_OPERATOR
        );

        return $membership;
    }

    /**
     * A president adds an existing operator to their TODA. The membership lands
     * as pending_admin — the president cannot self-approve the final step.
     */
    public function presidentAdd(Operator $operator, Toda $toda, User $president): OperatorToda
    {
        $existing = $operator->membershipFor($toda->id);

        if ($existing && $existing->status === OperatorToda::STATUS_ACTIVE) {
            return $existing;
        }

        $membership = $operator->todaMemberships()->updateOrCreate(
            ['toda_id' => $toda->id],
            ['status' => OperatorToda::STATUS_PENDING_ADMIN, 'joined_at' => now()]
        );

        // Adopting a TODA-less operator makes this TODA their home.
        if (!$operator->toda_id) {
            $operator->update(['toda_id' => $toda->id]);
            if ($operator->user) {
                $operator->user->forceFill(['toda_id' => $toda->id])->save();
            }
        }

        ActivityLogger::log(
            'add_toda_membership_president',
            "{$president->name} added {$this->name($operator)} to {$toda->name} — awaiting final approval",
            $membership,
            ActivityLogger::CATEGORY_OPERATOR
        );

        return $membership;
    }

    /**
     * A superadmin/TFRB adds an operator to a TODA directly (they are the final
     * approver, so the membership becomes active immediately).
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
     * is always kept. Memberships that fall outside the desired set are removed
     * so the operator stops being rateable under them.
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
     * Pending memberships for a TODA awaiting its president's review, newest
     * first, eager-loading the operator + user so a queue can be rendered
     * without N+1 queries.
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
     * Memberships awaiting the superadmin/TFRB's final approval, newest first.
     */
    public function adminQueue(): Collection
    {
        return OperatorToda::with(['operator.user', 'toda'])
            ->where('status', OperatorToda::STATUS_PENDING_ADMIN)
            ->latest('id')
            ->get();
    }

    /**
     * Operators that a president could add to their TODA: matching the search
     * term, not already an open/active member, and not the president's own row.
     */
    public function searchJoinableOperators(Toda $toda, ?string $search, int $limit = 20): Collection
    {
        $search = trim((string) $search);

        return Operator::query()
            ->with(['user', 'toda'])
            ->whereHas('user', fn ($q) => $q->whereIn('role', ['operator', 'operator_president']))
            ->whereNull('archived_at')
            ->where('toda_id', '!=', $toda->id)
            ->whereDoesntHave('todaMemberships', function ($q) use ($toda) {
                $q->where('toda_id', $toda->id)
                    ->whereIn('status', [
                        OperatorToda::STATUS_ACTIVE,
                        OperatorToda::STATUS_PENDING,
                        OperatorToda::STATUS_PENDING_ADMIN,
                    ]);
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('body_number', 'like', "%{$search}%")
                        ->orWhere('plate_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();
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
