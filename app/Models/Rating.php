<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    public const COMPLAINT_TYPES = [
        'Reckless driving',
        'Overloading',
        'Overcharging',
        'Refusal to trip',
        'Drunk Driving',
        'Unsafe Overtaking',
        'Unprofessional Driver Behavior',
        'Rude Driver',
        'Smoking While Driving',
        'Unsafe Pick-up/Drop-off',
        'Passenger Harassment',
        'Use of Mobile Phone While Driving',
        'Others',
    ];

    protected $fillable = [
        'operator_id',
        'rating',
        'complaint_type',
        'complaint_details',
        'reason',
        'passenger_ip',
        'client_id',
        'passenger_contact',
        'passenger_name',
        'is_reviewed',
        'is_auto',
        'is_valid',
        'start_location',
        'end_location',
    ];

    protected $casts = [
        'is_reviewed' => 'boolean',
        'is_auto' => 'boolean',
        'is_valid' => 'boolean',
    ];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function proofs()
    {
        return $this->hasMany(RatingProof::class);
    }

    public function response()
    {
        return $this->hasOne(OperatorResponse::class);
    }

    public function scopeIsValid($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeIsInvalid($query)
    {
        return $query->where('is_valid', false);
    }

    public static function paddedComplaintStats($stats)
    {
        $map = collect($stats)->pluck('total', 'complaint_type');
        return collect(self::COMPLAINT_TYPES)->map(function ($type) use ($map) {
            return (object) [
                'complaint_type' => $type,
                'total' => (int) $map->get($type, 0),
            ];
        })->sortByDesc('total')->values();
    }

    public static function normalizeAddress($address)
    {
        if (!$address) return null;
        $skip = ['philippines', 'cagayan valley', 'region ii', 'luzon', 'valle de cagayan', 'isabela', 'northern luzon'];
        $parts = array_filter(array_map('trim', explode(',', $address)), function ($s) use ($skip) {
            if ($s === '') return false;
            if (preg_match('/^\d{4}$/', $s)) return false;
            if (in_array(strtolower($s), $skip)) return false;
            return true;
        });
        return implode(', ', array_slice($parts, 0, 5));
    }

    /**
     * Single source of truth for whether a rating is valid. A rating is valid
     * only when BOTH route locations are present AND, for low ratings (1-2),
     * at least one proof file is attached. Previously this rule was implemented
     * differently in submitRating(), restoreRating() and the reindex migration,
     * which caused ratings to flip between valid and invalid depending on the
     * code path that evaluated them.
     */
    public function evaluateValidity(): bool
    {
        $hasLocation = $this->start_location && $this->end_location;
        $needsProof = (int) $this->rating <= 2;
        $hasProofs = $this->relationLoaded('proofs')
            ? $this->proofs->count() > 0
            : $this->proofs()->exists();

        return $hasLocation && (!$needsProof || $hasProofs);
    }

    public function getInvalidReasonAttribute()
    {
        $reasons = [];
        if (!$this->start_location || !$this->end_location) {
            $reasons[] = 'No location/route data';
        }
        if ($this->rating <= 2 && $this->proofs->count() === 0) {
            $reasons[] = 'No proof attached for low rating';
        }
        return implode(' & ', $reasons);
    }

    public function getStartLocationAttribute($value)
    {
        return self::normalizeAddress($value);
    }

    public function getEndLocationAttribute($value)
    {
        return self::normalizeAddress($value);
    }
}
