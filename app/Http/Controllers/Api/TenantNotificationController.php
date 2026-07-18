<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TenantNotificationController extends Controller
{
    /**
     * Get the notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get latest 20 notifications
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $formatted = $notifications->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? $n->data['body'] ?? 'New update available.',
                'url' => $n->data['url'] ?? null,
                'read_at' => $n->read_at ? $n->read_at->toIso8601String() : null,
                'created_at' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a specific notification or all notifications as read.
     */
    public function markAsRead(Request $request, $id = null): JsonResponse
    {
        $user = $request->user();

        if ($id) {
            $notification = $user->unreadNotifications()->where('id', $id)->first();
            if ($notification) {
                $notification->markAsRead();
            }
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read.',
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }
}
