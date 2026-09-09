<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyAlert extends Model
{
    use HasFactory;

    public const CATEGORIES = ['general', 'accident', 'theft_harassment', 'other'];

    public const STATUSES = ['active', 'responding', 'resolved', 'false_alarm'];

    /**
     * Category -> display label / icon / color (admin views).
     */
    public const CATEGORY_LABELS = [
        'general' => 'General Emergency',
        'accident' => 'Accident',
        'theft_harassment' => 'Theft / Harassment',
        'other' => 'Other',
    ];

    public const CATEGORY_ICONS = [
        'general' => 'bi-exclamation-triangle',
        'accident' => 'bi-truck-front',
        'theft_harassment' => 'bi-shield-exclamation',
        'other' => 'bi-three-dots',
    ];

    /**
     * Tailwind badge classes, keyed by category (matches Section 1 colors:
     * red / orange / maroon / gray).
     */
    public const CATEGORY_BADGES = [
        'general' => 'tw-badge tw-badge-red',
        'accident' => 'tw-badge tw-badge-orange',
        'theft_harassment' => 'tw-badge tw-badge-maroon',
        'other' => 'tw-badge tw-badge-gray',
    ];

    public const STATUS_LABELS = [
        'active' => 'Active',
        'responding' => 'Responding',
        'resolved' => 'Resolved',
        'false_alarm' => 'False Alarm',
    ];

    /**
     * Tailwind badge classes, keyed by status.
     */
    public const STATUS_BADGES = [
        'active' => 'tw-badge tw-badge-red',
        'responding' => 'tw-badge tw-badge-amber',
        'resolved' => 'tw-badge tw-badge-green',
        'false_alarm' => 'tw-badge tw-badge-gray',
    ];

    protected $fillable = [
        'passenger_id',
        'passenger_name',
        'passenger_contact',
        'trip_id',
        'operator_id',
        'toda_id',
        'category',
        'note',
        'location_lat',
        'location_lng',
        'status',
        'resolved_at',
        'resolved_by',
        'resolution_note',
    ];

    protected $casts = [
        'location_lat' => 'decimal:7',
        'location_lng' => 'decimal:7',
        'resolved_at' => 'datetime',
    ];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function toda()
    {
        return $this->belongsTo(Toda::class);
    }

    public function trip()
    {
        return $this->belongsTo(Rating::class, 'trip_id');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? ucfirst($this->category);
    }

    public function getCategoryBadgeAttribute(): string
    {
        return self::CATEGORY_BADGES[$this->category] ?? 'tw-badge';
    }

    public function getCategoryIconAttribute(): string
    {
        return self::CATEGORY_ICONS[$this->category] ?? 'bi-exclamation-triangle';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'tw-badge';
    }

    /**
     * Google Maps query link for the captured coordinates (or null when the
     * passenger's location could not be obtained).
     */
    public function getMapLinkAttribute(): ?string
    {
        if ($this->location_lat === null || $this->location_lng === null) {
            return null;
        }
        return 'https://www.google.com/maps?q=' . $this->location_lat . ',' . $this->location_lng;
    }

    /**
     * Category-specific guidance shown to responders (Section 4).
     */
    public function getGuidanceAttribute(): ?string
    {
        return match ($this->category) {
            'accident' => 'Consider dispatching medical assistance.',
            'theft_harassment' => 'Consider contacting barangay/police for immediate response.',
            default => null,
        };
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInScopeFor($query, User $user)
    {
        if ($user->isSuperadmin() || $user->isTfrbOfficer()) {
            return $query;
        }
        if ($user->isOperatorPresident()) {
            return $query->where('toda_id', $user->toda_id);
        }
        return $query->whereRaw('1 = 0');
    }
}