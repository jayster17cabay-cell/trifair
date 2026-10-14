<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberSuspension extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'toda_id',
        'suspended_by',
        'reason',
        'suspended_at',
        'suspended_until',
        'ended_at',
        'ended_by',
    ];

    protected $casts = [
        'operator_id' => 'integer',
        'toda_id' => 'integer',
        'suspended_by' => 'integer',
        'ended_by' => 'integer',
        'suspended_at' => 'date',
        'suspended_until' => 'date',
        'ended_at' => 'datetime',
    ];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function toda()
    {
        return $this->belongsTo(Toda::class);
    }

    public function suspender()
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function ender()
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * A suspension is over once its suspended_until day has fully passed.
     * Mirrors FranchiseTerm::isExpired() so the "end of day" boundary is
     * consistent across the app.
     */
    public function isExpired(): bool
    {
        return $this->suspended_until !== null
            && $this->suspended_until->copy()->endOfDay()->isPast();
    }

    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }

    public function scopeEnded($query)
    {
        return $query->whereNotNull('ended_at');
    }
}
