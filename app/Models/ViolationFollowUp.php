<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One message in a violation report's chat thread — the passenger adds context
 * or a status nudge, the TFRB/superadmin answers with an update.
 */
class ViolationFollowUp extends Model
{
    protected $table = 'violation_follow_ups';

    protected $fillable = [
        'violation_id',
        'user_id',
        'message',
        'sender_role',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function violation()
    {
        return $this->belongsTo(SuspensionViolation::class, 'violation_id');
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