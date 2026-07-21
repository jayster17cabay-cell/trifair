<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'rating_id',
        'file_path',
        'file_type',
        'original_name',
    ];

    public function rating()
    {
        return $this->belongsTo(Rating::class);
    }
}
