<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => [
                'data' => NotificationResource::collection($notifications)->resolve(),
                'links' => $notifications->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'from' => $notifications->firstItem(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'to' => $notifications->lastItem(),
                    'total' => $notifications->total(),
                ],
            ],
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(string $notification): JsonResponse
    {
        $item = auth()->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return response()->json([
            'data' => new NotificationResource($item->fresh()),
        ]);
    }

    public function markAllRead(): JsonResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'Notifications marked as read.',
            'unread_count' => 0,
        ]);
    }
}
