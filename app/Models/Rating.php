<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'rating',
        'reason',
        'passenger_ip',
        'passenger_contact',
        'passenger_name',
        'is_reviewed',
        'is_auto',
        'start_location',
        'end_location',
    ];

    protected $casts = [
        'is_reviewed' => 'boolean',
        'is_auto' => 'boolean',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function proofs()
    {
        return $this->hasMany(RatingProof::class);
    }

    public function response()
    {
        return $this->hasOne(DriverResponse::class);
    }

    public function scopeValid($query)
    {
        return $query->whereNotNull('start_location')->whereNotNull('end_location');
    }
}
