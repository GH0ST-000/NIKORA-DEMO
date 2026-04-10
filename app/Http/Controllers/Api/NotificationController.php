<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\NotificationModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListAppNotificationsRequest;
use App\Http\Requests\Api\StoreAppNotificationRequest;
use App\Http\Resources\AppNotificationResource;
use App\Models\AppNotification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(ListAppNotificationsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AppNotification::class);

        /** @var User $user */
        $user = auth('api')->user();

        $paginator = $this->notificationService->paginateForUser(
            user: $user,
            perPage: $request->perPage(),
            module: $request->moduleFilter(),
            isRead: $request->isReadFilter(),
            type: $request->typeFilter(),
        );

        return AppNotificationResource::collection($paginator);
    }

    public function unreadCount(): JsonResponse
    {
        $this->authorize('viewAny', AppNotification::class);

        /** @var User $user */
        $user = auth('api')->user();

        return response()->json([
            'unread_count' => $this->notificationService->unreadCountForUser($user),
        ]);
    }

    public function store(StoreAppNotificationRequest $request): AppNotificationResource
    {
        $this->authorize('create', AppNotification::class);

        /** @var User $caller */
        $caller = auth('api')->user();

        $module = $request->module();
        if ($module !== NotificationModule::Chat) {
            $this->notificationService->assertCallerMayCreateNonChatNotification($caller);
        }

        $recipient = User::query()->findOrFail($request->integer('user_id'));

        $senderId = $request->has('sender_id') && $request->input('sender_id') !== null
            ? $request->integer('sender_id')
            : $caller->id;

        $rawData = $request->input('data');
        $data = [];
        if (is_array($rawData)) {
            foreach ($rawData as $key => $value) {
                $data[(string) $key] = $value;
            }
        }

        $notification = $this->notificationService->create(
            recipient: $recipient,
            module: $module,
            type: $request->type(),
            title: $request->string('title')->toString(),
            message: $request->string('message')->toString(),
            senderId: $senderId,
            entityType: $request->filled('entity_type') ? $request->string('entity_type')->toString() : null,
            entityId: $request->filled('entity_id') ? $request->integer('entity_id') : null,
            action: $request->filled('action') ? $request->string('action')->toString() : null,
            data: $data,
        );

        $notification->load('sender');

        return new AppNotificationResource($notification);
    }

    public function markAsRead(AppNotification $notification): AppNotificationResource
    {
        $this->authorize('update', $notification);

        /** @var User $user */
        $user = auth('api')->user();

        $updated = $this->notificationService->markAsRead($notification, $user);
        $updated->load('sender');

        return new AppNotificationResource($updated);
    }

    public function markAllAsRead(): JsonResponse
    {
        $this->authorize('viewAny', AppNotification::class);

        /** @var User $user */
        $user = auth('api')->user();

        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'updated_count' => $count,
        ]);
    }

    public function destroy(AppNotification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        /** @var User $user */
        $user = auth('api')->user();

        $this->notificationService->deleteForUser($notification, $user);

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }
}
