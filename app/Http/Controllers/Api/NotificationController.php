<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Get user notifications
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request)
    {
        $request->validate([
            'notification_id' => 'required|exists:notifications,id'
        ]);

        /** @var User $user */
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($request->notification_id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $count = $user->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }
} 