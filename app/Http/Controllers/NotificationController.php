<?php

namespace App\Http\Controllers;

use App\Models\NotificationModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get unread notifications count.
     */
    public function getUnreadCount()
    {
        try {
            $count = NotificationModel::where('user_id', Auth::id())
                ->unread()
                ->count();
            
            return response()->json([
                'success' => true,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all notifications for the user.
     */
    public function index()
    {
        try {
            $notifications = NotificationModel::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            
            return response()->json([
                'success' => true,
                'notifications' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent notifications for dropdown.
     */
    public function getRecent()
    {
        try {
            $notifications = NotificationModel::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $unreadCount = NotificationModel::where('user_id', Auth::id())
                ->unread()
                ->count();
            
            // Format the notifications to ensure all fields are present
            $formattedNotifications = $notifications->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'uuid' => $notification->uuid,
                    'user_id' => $notification->user_id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'data' => $notification->data,
                    'icon' => $notification->icon,
                    'color' => $notification->color,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->toDateTimeString(),
                    'updated_at' => $notification->updated_at->toDateTimeString(),
                    'time_ago' => $notification->created_at->diffForHumans(),
                    'formatted_date' => $notification->created_at->format('M d, Y h:i A'),
                    'is_unread' => is_null($notification->read_at)
                ];
            });
            
            return response()->json([
                'success' => true,
                'notifications' => $formattedNotifications,
                'unread_count' => $unreadCount,
                'debug' => [
                    'user_id' => Auth::id(),
                    'total_found' => $notifications->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'debug' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead($id)
    {
        try {
            $notification = NotificationModel::where('user_id', Auth::id())
                ->where('id', $id)
                ->firstOrFail();
            
            $notification->markAsRead();
            
            // Get updated unread count
            $unreadCount = NotificationModel::where('user_id', Auth::id())
                ->unread()
                ->count();
            
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'unread_count' => $unreadCount
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        try {
            NotificationModel::where('user_id', Auth::id())
                ->unread()
                ->update(['read_at' => now()]);
            
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
                'unread_count' => 0
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a notification.
     */
    public function destroy($id)
    {
        try {
            $notification = NotificationModel::where('user_id', Auth::id())
                ->where('id', $id)
                ->firstOrFail();
            
            $notification->delete();
            
            // Get updated unread count
            $unreadCount = NotificationModel::where('user_id', Auth::id())
                ->unread()
                ->count();
            
            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully',
                'unread_count' => $unreadCount
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification: ' . $e->getMessage()
            ], 500);
        }
    }
}
