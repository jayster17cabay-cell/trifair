<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A single operator's membership in a single TODA. An operator holds one LGU
 * franchise but may be a member of several TODAs (up to MAX_TODAS), so
 * rateability is derived from these rows rather than from the franchise.
 *
 * Status lifecycle (two approvals required before a membership is Active):
 *   pending            -> the operator asked to join; the TODA president reviews it first
 *   pending_tfrb       -> the TODA president approved; the TFRB officer OR the
 *                         superadmin gives the final approval (whichever gets
 *                         to it first — it does not take both)
 *   active             -> both approvals done; the operator is rateable under this TODA
 *   rejected           -> declined at any stage (reason recorded)
 *   inactive           -> withdrawn but kept for history (not rateable)
 *
 * `pending_superadmin` (in-flight rows from the old three-step chain) and the
 * legacy `pending_admin` value are both treated as awaiting the same single
 * final approval, so nothing already moving keeps progressing.
 */
class OperatorToda extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PENDING_TFRB = 'pending_tfrb';
    public const STATUS_PENDING_SUPERADMIN = 'pending_superadmin';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_INACTIVE = 'inactive';

    /** Legacy: president-approved, waiting on the last approver (pre-TFRB stage). */
    public const STATUS_PENDING_ADMIN = 'pending_admin';

    /** A driver may hold at most this many TODA memberships. */
    public const MAX_TODAS = 3;

    protected $table = 'operator_toda';

    protected $fillable = [
        'operator_id',
        'toda_id',
        'status',
        'is_president',
        'joined_at',
    ];

    protected $casts = [
        'operator_id' => 'integer',
        'toda_id' => 'integer',
        'is_president' => 'boolean',
        'joined_at' => 'datetime',
    ];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function toda()
    {
        return $this->belongsTo(Toda::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * True when this membership also carries the TODA presidency. Presidency
     * only counts while the membership is active.
     */
    public function isPresident(): bool
    {
        return (bool) $this->is_president && $this->isActive();
    }

    /**
     * Active memberships that carry the presidency.
     */
    public function scopePresident($query)
    {
        return $query->where('is_president', true)->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Waiting on the TODA president (self-service requests).
     */
    public function isPendingPresident(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Awaiting the single final approval — the TFRB officer or the superadmin,
     * whichever signs off first. Includes the legacy pending_admin value and
     * in-flight pending_superadmin rows from the old three-step chain.
     */
    public function isAwaitingFinalApproval(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_TFRB,
            self::STATUS_PENDING_SUPERADMIN,
            self::STATUS_PENDING_ADMIN,
        ], true);
    }

    /**
     * Still awaiting a decision at some level (not yet active/rejected).
     */
    public function isOpen(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PENDING_TFRB,
            self::STATUS_PENDING_SUPERADMIN,
            self::STATUS_PENDING_ADMIN,
        ], true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Every status that still counts as an open application (awaiting a
     * decision at any stage).
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_PENDING_TFRB,
            self::STATUS_PENDING_SUPERADMIN,
            self::STATUS_PENDING_ADMIN,
        ]);
    }

    public function scopePendingPresident($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Everything past the president stage that still needs the one final
     * approval (TFRB officer or superadmin).
     */
    public function scopeAwaitingFinalApproval($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING_TFRB,
            self::STATUS_PENDING_SUPERADMIN,
            self::STATUS_PENDING_ADMIN,
        ]);
    }

    /**
     * Human-readable label for the status badge.
     */
    public function statusLabel(): string
    {
        return [
            self::STATUS_PENDING => 'Pending President',
            self::STATUS_PENDING_TFRB => 'Pending Final Approval',
            self::STATUS_PENDING_ADMIN => 'Pending Final Approval',
            self::STATUS_PENDING_SUPERADMIN => 'Pending Final Approval',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_INACTIVE => 'Inactive',
        ][$this->status] ?? ucfirst((string) $this->status);
    }

    /**
     * The reason recorded when this membership was rejected, if any. Stored in
     * the existing activity log (no schema change) so the driver can be shown
     * why their application was declined.
     */
    public function rejectionReason(): ?string
    {
        if ($this->status !== self::STATUS_REJECTED) {
            return null;
        }

        return ActivityLog::query()
            ->where('model_type', self::class)
            ->where('model_id', $this->id)
            ->where('action', 'reject_toda_membership')
            ->latest('id')
            ->value('description');
    }
}
