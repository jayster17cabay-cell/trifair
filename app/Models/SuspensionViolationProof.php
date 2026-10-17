<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuspensionViolationProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'violation_id',
        'file_path',
        'file_type',
        'original_name',
    ];

    public function violation()
    {
        return $this->belongsTo(SuspensionViolation::class);
    }
}
