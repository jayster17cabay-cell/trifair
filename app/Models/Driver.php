<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
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
        'tricycle_color',
    ];

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
        return $this->hasMany(Rating::class)->whereNotNull('start_location')->whereNotNull('end_location');
    }

    public function averageRating()
    {
        return $this->ratings()->avg('rating');
    }

    public function totalRatings()
    {
        return $this->ratings()->count();
    }
}
