<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One message in a complaint's chat thread — the passenger adds context or a
 * status nudge, the TFRB/superadmin answers with an update. sender_role
 * records which side wrote it ('passenger' or 'staff').
 */
class PassengerFollowUp extends Model
{
    protected $table = 'passenger_follow_ups';

    protected $fillable = [
        'rating_id',
        'user_id',
        'message',
        'sender_role',
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

    public function isFromStaff(): bool
    {
        return $this->sender_role === 'staff';
    }
}
