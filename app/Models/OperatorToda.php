<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A single operator's membership in a single TODA. An operator holds one LGU
 * franchise but may be a member of several TODAs (up to MAX_TODAS), so
 * rateability is derived from these rows rather than from the franchise.
 *
 * Status lifecycle (three approvals required before a membership is Active):
 *   pending            -> the operator asked to join; the TODA president reviews it first
 *   pending_tfrb       -> the TODA president approved; the TFRB officer verifies it next
 *   pending_superadmin -> the TFRB officer approved; the superadmin gives the final approval
 *   active             -> all three approvals done; the operator is rateable under this TODA
 *   rejected           -> declined at any stage (reason recorded)
 *   inactive           -> withdrawn but kept for history (not rateable)
 *
 * `pending_admin` is a legacy value from the earlier two-stage flow: a
 * president-approved row that was simply waiting for the last approver. It is
 * treated as awaiting the TFRB so in-flight memberships keep progressing.
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
     * Waiting on the TFRB officer (includes the legacy pending_admin value).
     */
    public function isPendingTfrb(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_TFRB, self::STATUS_PENDING_ADMIN], true);
    }

    /**
     * Waiting on the superadmin for the final approval.
     */
    public function isPendingSuperadmin(): bool
    {
        return $this->status === self::STATUS_PENDING_SUPERADMIN;
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

    public function scopePendingTfrb($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING_TFRB, self::STATUS_PENDING_ADMIN]);
    }

    public function scopePendingSuperadmin($query)
    {
        return $query->where('status', self::STATUS_PENDING_SUPERADMIN);
    }

    /**
     * Human-readable label for the status badge.
     */
    public function statusLabel(): string
    {
        return [
            self::STATUS_PENDING => 'Pending President',
            self::STATUS_PENDING_TFRB => 'Pending TFRB',
            self::STATUS_PENDING_ADMIN => 'Pending TFRB',
            self::STATUS_PENDING_SUPERADMIN => 'Pending Superadmin',
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
