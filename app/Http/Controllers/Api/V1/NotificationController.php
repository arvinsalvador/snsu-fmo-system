<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notifications\NotificationIndexRequest;
use App\Http\Resources\Api\V1\DatabaseNotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(NotificationIndexRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', DatabaseNotification::class);
        $notifications = $this->notifications->paginate($request->user(), $request->integer('per_page', 20), $request->only(['unread', 'type', 'date_from', 'date_to']));

        return response()->json([
            'success' => true, 'message' => 'Notifications retrieved successfully.',
            'data' => DatabaseNotificationResource::collection($notifications),
            'meta' => ['current_page' => $notifications->currentPage(), 'per_page' => $notifications->perPage(), 'total' => $notifications->total(), 'last_page' => $notifications->lastPage()],
            'links' => ['first' => $notifications->url(1), 'last' => $notifications->url($notifications->lastPage()), 'prev' => $notifications->previousPageUrl(), 'next' => $notifications->nextPageUrl()],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Unread notification count retrieved successfully.', 'data' => ['count' => $request->user()->unreadNotifications()->count()], 'meta' => null]);
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $this->notifications->delete($request->user(), $notification);

        return response()->json(null, 204);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $record = $request->user()->notifications()->findOrFail($notification);
        Gate::authorize('update', $record);

        return response()->json(['success' => true, 'message' => 'Notification marked as read.', 'data' => new DatabaseNotificationResource($this->notifications->markRead($request->user(), $notification))]);
    }

    public function readAll(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', DatabaseNotification::class);
        $count = $this->notifications->markAllRead($request->user());

        return response()->json(['success' => true, 'message' => 'All notifications marked as read.', 'data' => ['updated' => $count]]);
    }
}
