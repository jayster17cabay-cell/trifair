<?php

namespace App\Models;

use App\Services\AdminDashboardService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'toda_id',
        'address',
        'contact_number',
        'qr_code',
        'qr_code_path',
        'status',
        'plate_number',
        'body_number',
        'motorcycle_model',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function toda()
    {
        return $this->belongsTo(Toda::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function validRatings()
    {
        return $this->hasMany(Rating::class)->where('is_valid', true);
    }

    /**
     * Only accepted complaints (plus every plain rating) influence the
     * operator's average; pending/rejected complaints are excluded.
     */
    public function countableRatings()
    {
        return $this->hasMany(Rating::class)->isValid()->countsTowardRating();
    }

    public function averageRating()
    {
        return $this->countableRatings()->avg('rating');
    }

    public function totalRatings()
    {
        return $this->countableRatings()->count();
    }

    /**
     * All franchise terms for this operator, newest first.
     */
    public function terms()
    {
        return $this->hasMany(FranchiseTerm::class)->latest('id');
    }

    /**
     * The operator's current franchise term. There is at most one active term
     * at any moment; latestOfMany() guards against stale duplicates.
     */
    public function currentTerm()
    {
        return $this->hasOne(FranchiseTerm::class)
            ->where('status', FranchiseTerm::STATUS_ACTIVE)
            ->latestOfMany();
    }

    /**
     * Current franchise health: 'valid', 'expired' or 'none' (no term yet).
     */
    public function franchiseStatus(): string
    {
        $term = $this->currentTerm;
        if (!$term) {
            return 'none';
        }

        return $term->isExpired() ? 'expired' : 'valid';
    }

    /**
     * True when the operator has a current term that has already lapsed.
     */
    public function hasExpiredFranchise(): bool
    {
        $term = $this->currentTerm;

        return $term !== null && $term->isExpired();
    }

    /**
     * Keep the approval status in step with the franchise: an active operator
     * whose current franchise has lapsed is switched to inactive. This never
     * auto-reactivates, so a deliberate admin deactivation is preserved —
     * issuing or renewing a term is what brings the operator back to active.
     *
     * @return bool true when the operator's status row was changed
     */
    public function syncFranchiseStatus(): bool
    {
        if ($this->status === 'active' && $this->hasExpiredFranchise()) {
            $this->update(['status' => 'inactive']);
            app(AdminDashboardService::class)->flush();

            return true;
        }

        return false;
    }

    /**
     * Bulk version of syncFranchiseStatus() for a whole set of operators in a
     * single UPDATE. Any operator whose current franchise term has lapsed is
     * marked inactive. Used by the daily command and before the admin list is
     * built so the status shown always agrees with the franchise expiry date.
     *
     * @return int number of operators deactivated
     */
    public static function deactivateExpiredFranchises(): int
    {
        $today = now()->toDateString();

        $deactivated = static::query()
            ->where('status', 'active')
            ->whereIn('id', function ($query) use ($today) {
                $query->from('franchise_terms')
                    ->where('status', FranchiseTerm::STATUS_ACTIVE)
                    ->whereNotNull('valid_until')
                    ->whereDate('valid_until', '<', $today)
                    ->select('operator_id');
            })
            ->update(['status' => 'inactive']);

        if ($deactivated > 0) {
            app(AdminDashboardService::class)->flush();
        }

        return $deactivated;
    }

    /**
     * All suspensions issued against this operator, newest first. Rows are
     * kept forever as a history of disciplinary actions.
     */
    public function suspensions()
    {
        return $this->hasMany(MemberSuspension::class)->latest('id');
    }

    /**
     * The operator's live suspension (no ended_at yet), if any. An expired but
     * not-yet-reconciled row is still returned here so callers can reason
     * about dates; use isSuspended() for the effective boolean.
     */
    public function currentSuspension()
    {
        return $this->hasOne(MemberSuspension::class)
            ->whereNull('ended_at')
            ->latestOfMany();
    }

    /**
     * True when the operator is under a suspension that has not yet lapsed.
     * An active row whose suspended_until day has passed no longer counts.
     */
    public function isSuspended(): bool
    {
        $suspension = $this->currentSuspension;

        return $suspension !== null && !$suspension->isExpired();
    }

    /**
     * Suspend the operator: record the resolution and switch the account to
     * the 'suspended' status so login and ratings are blocked.
     */
    public function suspend(array $attributes): MemberSuspension
    {
        $suspension = $this->suspensions()->create($attributes);

        if ($this->status !== 'suspended') {
            $this->update(['status' => 'suspended']);
            app(AdminDashboardService::class)->flush();
        }

        return $suspension;
    }

    /**
     * End the operator's live suspension early (or on reconciliation). The row
     * is kept and stamped ended_at; the account status is then recomputed.
     *
     * @return bool true when a suspension was actually closed
     */
    public function endSuspension(?User $endedBy = null): bool
    {
        $suspension = $this->currentSuspension;

        if (!$suspension) {
            return false;
        }

        $suspension->update([
            'ended_at' => now(),
            'ended_by' => $endedBy ? $endedBy->id : null,
        ]);

        // Reload relations so isSuspended() reflects the ended suspension.
        $this->load('currentSuspension');
        $this->syncSuspensionStatus();

        return true;
    }

    /**
     * The status the operator should return to once no suspension is live:
     * inactive when archived or when the franchise has lapsed, active
     * otherwise. Suspending never deactivates the franchise itself.
     */
    public function resolvedActiveStatus(): string
    {
        if ($this->isArchived() || $this->hasExpiredFranchise()) {
            return 'inactive';
        }

        return 'active';
    }

    /**
     * Bring the status back in line with the suspension: a suspended operator
     * whose latest suspension has lapsed becomes active again (or inactive if
     * the franchise has expired meanwhile). Never touches non-suspended rows.
     *
     * @return bool true when the operator's status row was changed
     */
    public function syncSuspensionStatus(): bool
    {
        if ($this->status !== 'suspended' || $this->isSuspended()) {
            return false;
        }

        $this->update(['status' => $this->resolvedActiveStatus()]);
        app(AdminDashboardService::class)->flush();

        return true;
    }

    /**
     * Bulk reconciliation: close every suspension whose suspended_until day has
     * passed and restore the affected operators' statuses. Used by the daily
     * command and before lists/dashboards are built so a lapsed suspension
     * never leaves a member locked out.
     *
     * @return int number of operators reactivated/deactivated
     */
    public static function endExpiredSuspensions(): int
    {
        $expired = MemberSuspension::query()
            ->active()
            ->whereDate('suspended_until', '<', now()->toDateString())
            ->with('operator')
            ->get();

        $changed = 0;

        foreach ($expired as $suspension) {
            $suspension->update(['ended_at' => now()]);

            $operator = $suspension->operator;
            if ($operator && $operator->status === 'suspended') {
                $operator->update(['status' => $operator->resolvedActiveStatus()]);
                $changed++;
            }
        }

        if ($changed > 0) {
            app(AdminDashboardService::class)->flush();
        }

        return $changed;
    }

    /**
     * Ratings belonging to a specific term. Defaults to the current active
     * term, so "current" statistics start at zero after a renewal while the
     * "All Time" figures (averageRating/totalRatings above) keep counting
     * every term.
     */
    public function termRatings(?FranchiseTerm $term = null)
    {
        $term = $term ?? $this->currentTerm;

        if (!$term) {
            return $this->hasMany(Rating::class)->whereRaw('1 = 0');
        }

        return $this->hasMany(Rating::class)->where('franchise_term_id', $term->id);
    }

    /**
     * Countable ratings (valid + accepted) within a term.
     */
    public function termCountableRatings(?FranchiseTerm $term = null)
    {
        return $this->termRatings($term)->isValid()->countsTowardRating();
    }
}
