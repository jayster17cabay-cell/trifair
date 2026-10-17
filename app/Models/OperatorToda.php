<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A single operator's membership in a single TODA. An operator holds one LGU
 * franchise but may be a member of several TODAs, so rateability is derived
 * from these rows rather than from the franchise.
 */
class OperatorToda extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
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

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
