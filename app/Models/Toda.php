<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Toda extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'area',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    public function activeDrivers()
    {
        return $this->hasMany(Driver::class)->where('status', 'active');
    }

    public function totalRatings()
    {
        return Rating::whereIn('driver_id', $this->drivers->pluck('id'));
    }
}
