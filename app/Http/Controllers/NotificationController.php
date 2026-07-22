<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::forUser(Auth::id())
            ->with('rating.driver.user')
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $notification->update(['is_read' => true]);

        if ($notification->type === 'complaint' && $notification->rating_id) {
            $user = Auth::user();
            $route = $user->isSuperadmin() ? 'superadmin.complaints' : 'admin.ratings';
            return redirect()->route($route);
        }

        return back();
    }

    public function markAllAsRead()
    {
        Notification::forUser(Auth::id())->unread()->update(['is_read' => true]);
        return back()->with('success', 'All notifications marked as read.');
    }
}
