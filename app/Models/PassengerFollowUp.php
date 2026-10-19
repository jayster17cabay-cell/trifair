<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One message a passenger adds to their own complaint after filing it — the
 * "follow-up" that keeps a ticket moving (extra context, a status nudge).
 */
class PassengerFollowUp extends Model
{
    protected $table = 'passenger_follow_ups';

    protected $fillable = [
        'rating_id',
        'user_id',
        'message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function rating()
    {
        return $this->belongsTo(Rating::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
