<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverResponse extends Model
{
    protected $fillable = [
        'rating_id',
        'message',
    ];

    public function rating()
    {
        return $this->belongsTo(Rating::class);
    }
}
