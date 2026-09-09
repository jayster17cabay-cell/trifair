<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorProof extends Model
{
    use HasFactory;

    protected $table = 'operator_response_proofs';

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