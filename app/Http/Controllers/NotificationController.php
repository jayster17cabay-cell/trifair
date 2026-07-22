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

    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        $user = Auth::user();
        if ($notification->type === 'complaint') {
            $route = $user->isSuperadmin() ? 'superadmin.complaints' : 'admin.ratings';
            return redirect()->route($route);
        }

        if ($notification->rating_id) {
            $route = $user->isSuperadmin() ? 'superadmin.ratings' : 'admin.ratings';
            return redirect()->route($route);
        }

        if ($user->isSuperadmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        return redirect()->route('admin.dashboard');
    }

    public function markAllAsRead()
    {
        Notification::forUser(Auth::id())->unread()->update(['is_read' => true]);
        return back()->with('success', 'All notifications marked as read.');
    }
}
