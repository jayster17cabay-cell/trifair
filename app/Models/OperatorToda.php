<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A single operator's membership in a single TODA. An operator holds one LGU
 * franchise but may be a member of several TODAs, so rateability is derived
 * from these rows rather than from the franchise.
 *
 * Status lifecycle:
 *   pending        -> the operator asked to join; the TODA's president reviews it first
 *   pending_admin  -> approved by the TODA president (or added by a president);
 *                     still needs the superadmin/TFRB's final approval
 *   active         -> fully approved; the operator is rateable under this TODA
 *   rejected       -> declined at either level
 *   inactive       -> withdrawn but kept for history (not rateable)
 */
class OperatorToda extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PENDING_ADMIN = 'pending_admin';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'operator_toda';

    protected $fillable = [
        'operator_id',
        'toda_id',
        'status',
        'joined_at',
    ];

    protected $casts = [
        'operator_id' => 'integer',
        'toda_id' => 'integer',
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
     * Waiting on the TODA president (self-service requests).
     */
    public function isPendingPresident(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Waiting on the superadmin/TFRB for final approval.
     */
    public function isPendingAdmin(): bool
    {
        return $this->status === self::STATUS_PENDING_ADMIN;
    }

    /**
     * Still awaiting a decision at some level (not yet active/rejected).
     */
    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PENDING_ADMIN], true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePendingPresident($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePendingAdmin($query)
    {
        return $query->where('status', self::STATUS_PENDING_ADMIN);
    }

    /**
     * Human-readable label for the status badge.
     */
    public function statusLabel(): string
    {
        return [
            self::STATUS_PENDING => 'Pending President',
            self::STATUS_PENDING_ADMIN => 'Pending Approval',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_INACTIVE => 'Inactive',
        ][$this->status] ?? ucfirst((string) $this->status);
    }
}
