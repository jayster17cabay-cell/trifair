<?php

namespace App\Http\ViewComposers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationComposer
{
    public function compose(View $view)
    {
        $unreadCount = 0;
        if (Auth::check()) {
            $unreadCount = Notification::forUser(Auth::id())->unread()->count();
        }
        $view->with('unreadCount', $unreadCount);
    }
}
