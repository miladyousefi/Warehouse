<?php

namespace App\Http\Controllers\Warehouse;

use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends BaseController
{
    public function index()
    {
        $notifications = auth()->user()->notifications()
            ->with('notifiable')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Warehouse/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        // Verify user owns this notification
        if ($notification->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        auth()->user()->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    public function delete(Notification $notification)
    {
        // Verify user owns this notification
        if ($notification->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted',
        ]);
    }

    public function unreadCount()
    {
        $count = auth()->user()->notifications()
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }
}
