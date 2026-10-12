<?php

namespace App\Http\ViewComposers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class NotificationComposer
{
    /**
     * Seconds the per-user unread badge is cached for. Without this the badge
     * runs a COUNT() on every single page render; against the remote database
     * that is an extra ~300ms round-trip on every request. The cache is
     * invalidated whenever a notification is created, read or deleted (see
     * Notification::booted()).
     */
    public const UNREAD_TTL = 60;

    public function compose(View $view)
    {
        $unreadCount = 0;
        if (Auth::check()) {
            $userId = Auth::id();
            $unreadCount = Cache::remember(
                'notif_unread:' . $userId,
                self::UNREAD_TTL,
                fn () => Notification::forUser($userId)->unread()->count()
            );
        }
        $view->with('unreadCount', $unreadCount);
    }
}
