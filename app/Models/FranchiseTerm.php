<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FranchiseTerm extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ENDED = 'ended';

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

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeEnded($query)
    {
        return $query->where('status', self::STATUS_ENDED);
    }
}
