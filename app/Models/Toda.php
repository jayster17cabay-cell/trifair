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

    public function operators()
    {
        return $this->hasMany(Operator::class);
    }

    public function activeOperators()
    {
        return $this->hasMany(Operator::class)->where('status', 'active');
    }

    public function totalRatings()
    {
        return Rating::whereIn('operator_id', $this->operators()->pluck('id'));
    }
}
