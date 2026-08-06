<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperatorResponse extends Model
{
    protected $table = 'operator_responses';

    protected $fillable = [
        'rating_id',
        'message',
    ];

    public function rating()
    {
        return $this->belongsTo(Rating::class);
    }
}
