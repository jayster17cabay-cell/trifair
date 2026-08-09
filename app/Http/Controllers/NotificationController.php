<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'all');

        $base = Notification::forUser(Auth::id());

        $counts = [
            'all' => (clone $base)->count(),
            'unread' => (clone $base)->unread()->count(),
            'complaint' => (clone $base)->where('type', 'complaint')->count(),
            'new_rating' => (clone $base)->where('type', 'new_rating')->count(),
            'operator_response' => (clone $base)->where('type', 'operator_response')->count(),
        ];

        $invalidCount = Rating::where('is_valid', false)->count();

        $query = clone $base;

        if ($type === 'unread') {
            $query->unread();
        } elseif (in_array($type, ['complaint', 'new_rating', 'operator_response'])) {
            $query->where('type', $type);
        } else {
            $type = 'all';
        }

        $notifications = $query->with('rating.operator.user')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        if ($request->wantsJson()) {
            $signature = md5(
                $notifications->pluck('id')->implode(',') .
                ($notifications->hasMorePages() ? 'M' : 'E')
            );

            return response()->json([
                'html' => view('notifications.list', compact('notifications', 'type'))->render(),
                'signature' => $signature,
                'counts' => $counts,
                'invalidCount' => $invalidCount,
                'unreadCount' => $counts['unread'],
                'hasItems' => $notifications->count() > 0,
            ]);
        }

        return view('notifications.index', compact('notifications', 'type', 'counts', 'invalidCount'));
    }

    public function markAsRead(Notification $notification)
    {
        if ((int) $notification->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        $user = Auth::user();
        if ($notification->type === 'complaint') {
            $route = $user->isSuperadmin() ? 'superadmin.complaints' : 'tfrb-officer.complaints';
            return redirect()->route($route);
        }

        if ($notification->rating_id) {
            $route = $user->isSuperadmin() ? 'superadmin.ratings' : 'tfrb-officer.ratings';
            return redirect()->route($route);
        }

        if ($user->isSuperadmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        return redirect()->route('tfrb-officer.dashboard');
    }

    public function markReadAjax(Notification $notification)
    {
        if ((int) $notification->user_id !== (int) Auth::id()) {
            abort(403);
        }

        if (!$notification->is_read) {
            $notification->update(['is_read' => true]);
        }

        return response()->json([
            'ok' => true,
            'unread_count' => Notification::forUser(Auth::id())->unread()->count(),
        ]);
    }

    public function markAllAsRead()
    {
        Notification::forUser(Auth::id())->unread()->update(['is_read' => true]);
        return back()->with('success', 'All notifications marked as read.');
    }
}
