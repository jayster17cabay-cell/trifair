<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FranchiseTerm extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ENDED = 'ended';

    /**
     * How many days before valid_until a term is flagged "Expiring Soon" on the
     * operator dashboard and admin lists. Adjust here to change the window.
     */
    public const EXPIRING_SOON_DAYS = 30;

    protected $fillable = [
        'operator_id',
        'toda_id',
        'franchise_no',
        'issued_at',
        'valid_until',
        'status',
        'renewed_from_id',
        'notes',
        'processed_by',
    ];

    protected $casts = [
        'operator_id' => 'integer',
        'toda_id' => 'integer',
        'renewed_from_id' => 'integer',
        'processed_by' => 'integer',
        'issued_at' => 'date',
        'valid_until' => 'date',
    ];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function toda()
    {
        return $this->belongsTo(Toda::class);
    }

    public function renewedFrom()
    {
        return $this->belongsTo(FranchiseTerm::class, 'renewed_from_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * A term is expired once its valid_until day has fully passed. Terms with
     * no valid_until (legacy/backfilled or perpetual) never expire.
     */
    public function isExpired(): bool
    {
        return $this->valid_until !== null
            && $this->valid_until->copy()->endOfDay()->isPast();
    }

    /**
     * The term may still receive new ratings: it is the active term and has
     * not lapsed.
     */
    public function isValid(): bool
    {
        return $this->isActive() && !$this->isExpired();
    }

    /**
     * Whole days until valid_until (negative once lapsed, null when the term
     * has no expiry). Used for the "N days left" hint.
     */
    public function daysUntilExpiry(): ?int
    {
        if ($this->valid_until === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->valid_until->copy()->startOfDay(), false);
    }

    /**
     * True when the term is still valid but lapses within EXPIRING_SOON_DAYS,
     * so the dashboard can warn the operator before it actually expires.
     */
    public function isExpiringSoon(): bool
    {
        if ($this->valid_until === null || $this->isExpired()) {
            return false;
        }

        return $this->daysUntilExpiry() <= self::EXPIRING_SOON_DAYS;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeEnded($query)
    {
        return $query->where('status', self::STATUS_ENDED);
    }
}
