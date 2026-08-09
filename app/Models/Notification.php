<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rating_id',
        'type',
        'title',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rating()
    {
        return $this->belongsTo(Rating::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getDateGroupAttribute(): string
    {
        if ($this->created_at->isToday()) {
            return 'Today';
        }
        if ($this->created_at->isYesterday()) {
            return 'Yesterday';
        }
        if ($this->created_at->greaterThanOrEqualTo(now()->startOfWeek())) {
            return 'This Week';
        }
        return 'Earlier';
    }

    public function getShortTimeAttribute(): string
    {
        $minutes = (int) $this->created_at->diffInMinutes(now());

        if ($minutes < 1) {
            return 'Just now';
        }
        if ($minutes < 60) {
            return $minutes . 'm ago';
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return $hours . 'h ago';
        }

        $days = intdiv($hours, 24);
        if ($days < 7) {
            return $days . 'd ago';
        }

        return $this->created_at->format('M j');
    }
}
