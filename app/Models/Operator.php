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

    /**
     * All franchise terms for this operator, newest first.
     */
    public function terms()
    {
        return $this->hasMany(FranchiseTerm::class)->latest('id');
    }

    /**
     * The operator's current franchise term. There is at most one active term
     * at any moment; latestOfMany() guards against stale duplicates.
     */
    public function currentTerm()
    {
        return $this->hasOne(FranchiseTerm::class)
            ->where('status', FranchiseTerm::STATUS_ACTIVE)
            ->latestOfMany();
    }

    /**
     * Current franchise health: 'valid', 'expired' or 'none' (no term yet).
     */
    public function franchiseStatus(): string
    {
        $term = $this->currentTerm;
        if (!$term) {
            return 'none';
        }

        return $term->isExpired() ? 'expired' : 'valid';
    }

    /**
     * Ratings belonging to a specific term. Defaults to the current active
     * term, so "current" statistics start at zero after a renewal while the
     * "All Time" figures (averageRating/totalRatings above) keep counting
     * every term.
     */
    public function termRatings(?FranchiseTerm $term = null)
    {
        $term = $term ?? $this->currentTerm;

        if (!$term) {
            return $this->hasMany(Rating::class)->whereRaw('1 = 0');
        }

        return $this->hasMany(Rating::class)->where('franchise_term_id', $term->id);
    }

    /**
     * Countable ratings (valid + accepted) within a term.
     */
    public function termCountableRatings(?FranchiseTerm $term = null)
    {
        return $this->termRatings($term)->isValid()->countsTowardRating();
    }
}
