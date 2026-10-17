<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuspensionViolation extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'toda_id',
        'reported_by',
        'reviewed_by',
        'suspension_id',
        'violation_type',
        'details',
        'incident_at',
        'location',
        'status',
        'is_reviewed',
        'is_solved',
        'solved_at',
        'review_note',
        'resolution_note',
        'recommended_action',
        'penalty_imposed',
    ];

    protected $casts = [
        'operator_id' => 'integer',
        'toda_id' => 'integer',
        'reported_by' => 'integer',
        'reviewed_by' => 'integer',
        'suspension_id' => 'integer',
        'incident_at' => 'datetime',
        'is_reviewed' => 'boolean',
        'is_solved' => 'boolean',
        'solved_at' => 'datetime',
    ];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function toda()
    {
        return $this->belongsTo(Toda::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function suspension()
    {
        return $this->belongsTo(MemberSuspension::class);
    }

    public function proofs()
    {
        return $this->hasMany(SuspensionViolationProof::class, 'violation_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSolved($query)
    {
        return $query->where('is_solved', true);
    }
}
