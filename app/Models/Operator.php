<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'toda_id',
        'license_number',
        'address',
        'contact_number',
        'qr_code',
        'qr_code_path',
        'status',
        'plate_number',
        'body_number',
        'motorcycle_model',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function toda()
    {
        return $this->belongsTo(Toda::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function validRatings()
    {
        return $this->hasMany(Rating::class)->where('is_valid', true);
    }

    /**
     * Only accepted complaints (plus every plain rating) influence the
     * operator's average; pending/rejected complaints are excluded.
     */
    public function countableRatings()
    {
        return $this->hasMany(Rating::class)->isValid()->countsTowardRating();
    }

    public function averageRating()
    {
        return $this->countableRatings()->avg('rating');
    }

    public function totalRatings()
    {
        return $this->countableRatings()->count();
    }
}
