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

    /**
     * Complaint severity lookup used to color-code complaint cards. Keys are
     * matched case-insensitively (full match first, then substring fallback)
     * so free-text types like "Attitude" still get a sensible level. Adjust the
     * map below to change the colors without touching the views.
     */
    public const COMPLAINT_SEVERITY = [
        'smoking while driving' => 'danger',
        'reckless driving' => 'danger',
        'drunk driving' => 'danger',
        'passenger harassment' => 'danger',
        'unsafe overtaking' => 'danger',
        'use of mobile phone while driving' => 'danger',
        'overloading' => 'warning',
        'overcharging' => 'warning',
        'refusal to trip' => 'warning',
        'rude driver' => 'warning',
        'unprofessional driver behavior' => 'warning',
        'unsafe pick-up/drop-off' => 'warning',
        'harassment' => 'danger',
        'attitude' => 'warning',
        'others' => 'warning',
    ];

    /**
     * Star rating -> card left-border color used to color-code rating cards
     * by severity (1-star danger, 2-3 warning, 4-5 good). Adjust the values
     * here to change the colors without touching the views.
     */
    public const RATING_BORDER = [
        1 => 'border-l-red-500',
        2 => 'border-l-amber-400',
        3 => 'border-l-amber-400',
        4 => 'border-l-emerald-500',
        5 => 'border-l-emerald-500',
    ];

    /**
     * Map a star rating to its severity border utility class.
     */
    public static function ratingBorderClass(int $stars): string
    {
        return self::RATING_BORDER[$stars] ?? 'border-l-slate-300';
    }

    protected $fillable = [
        'operator_id',
        'rating',
        'complaint_type',
        'complaint_details',
        'reason',
        'passenger_ip',
        'client_id',
        'passenger_contact',
        'passenger_email',
        'passenger_user_id',
        'passenger_name',
        'is_reviewed',
        'is_solved',
        'solved_at',
        'is_auto',
        'is_valid',
        'is_accepted',
        'start_location',
        'end_location',
    ];

    protected $casts = [
        'is_reviewed' => 'boolean',
        'is_solved' => 'boolean',
        'solved_at' => 'datetime',
        'is_auto' => 'boolean',
        'is_valid' => 'boolean',
        'is_accepted' => 'boolean',
    ];

    /**
     * The complaint form always posts both fields, even when the complaint box
     * is hidden (star ratings 3-5). Convert the empty strings to real nulls so
     * the scopes below keep working: a positive rating must never come back as
     * a "complaint" just because the select submitted an empty value.
     */
    public function setComplaintTypeAttribute($value)
    {
        $this->attributes['complaint_type'] = $value === '' ? null : $value;
    }

    public function setComplaintDetailsAttribute($value)
    {
        $this->attributes['complaint_details'] = $value === '' ? null : $value;
    }

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

    public function operatorProofs()
    {
        return $this->hasMany(OperatorProof::class);
    }

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_user_id');
    }

    /**
     * Human-friendly reference number for a rating/complaint ticket, derived
     * from the row id so it stays stable and unique without a migration.
     * Example: TFR-2026-0042.
     */
    public function getReferenceNumberAttribute(): string
    {
        return sprintf('TFR-%d-%04d', $this->created_at ? (int) $this->created_at->format('Y') : (int) date('Y'), (int) $this->id);
    }

    public function scopeIsValid($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeIsInvalid($query)
    {
        return $query->where('is_valid', false);
    }

    /**
     * A "real complaint" is any rating that carries an actual complaint
     * type/details, regardless of star count. This drives the Complaints
     * page so it only lists genuine complaints (not every low rating).
     */
    public function scopeIsComplaint($query)
    {
        return $query->whereNotNull('complaint_type');
    }

    public function scopeIsSolved($query)
    {
        return $query->where('is_solved', true);
    }

    /**
     * The inverse: feedback without a complaint attached. Used by the
     * Ratings page so it lists all 1-5 star feedback that isn't itself a
     * complaint entry.
     */
    public function scopeNotComplaint($query)
    {
        return $query->whereNull('complaint_type');
    }

    /**
     * Rows allowed to influence an operator's star average. A plain rating
     * (no complaint type) always counts; a complaint counts only once an
     * officer has explicitly accepted it. Pending and rejected complaints
     * are ignored so fake complaints can never drag an operator's rating
     * down before they are reviewed.
     */
    public function scopeCountsTowardRating($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('complaint_type')->orWhere('is_accepted', true);
        });
    }

    /**
     * Combined review status shown on complaint badges, filters and exports:
     * Solved > Accepted > Rejected > Pending.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->is_solved) {
            return 'Solved';
        }
        if ($this->is_accepted === true) {
            return 'Accepted';
        }
        if ($this->is_accepted === false) {
            return 'Rejected';
        }

        return 'Pending';
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

    /**
     * Map a complaint type to a severity level ('danger' | 'warning' | 'neutral').
     */
    public static function complaintSeverity(?string $type): string
    {
        $key = strtolower(trim((string) $type));
        if ($key === '') {
            return 'neutral';
        }

        if (isset(self::COMPLAINT_SEVERITY[$key])) {
            return self::COMPLAINT_SEVERITY[$key];
        }

        $needles = array_keys(self::COMPLAINT_SEVERITY);
        usort($needles, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($needles as $needle) {
            if (str_contains($key, $needle)) {
                return self::COMPLAINT_SEVERITY[$needle];
            }
        }

        return 'neutral';
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
