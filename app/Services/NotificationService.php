<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationModule;
use App\Enums\NotificationType;
use App\Events\AppNotificationCreated;
use App\Models\AppNotification;
use App\Models\Batch;
use App\Models\ConversationParticipant;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Receiving;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\WarehouseLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class NotificationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(
        User $recipient,
        NotificationModule $module,
        NotificationType $type,
        string $title,
        string $message,
        ?int $senderId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $action = null,
        array $data = [],
    ): AppNotification {
        $this->assertRecipientAcceptsModule($recipient, $module);

        /** @var AppNotification $notification */
        $notification = AppNotification::query()->create([
            'user_id' => $recipient->id,
            'type' => $type->value,
            'title' => $title,
            'message' => $message,
            'module' => $module->value,
            'data' => $data !== [] ? $data : null,
            'is_read' => false,
            'read_at' => null,
            'sender_id' => $senderId,
            'entity_id' => $entityId,
            'entity_type' => $entityType,
            'action' => $action,
        ]);

        event(new AppNotificationCreated($notification));

        return $notification;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function notifyAllAdministrators(
        NotificationModule $module,
        NotificationType $type,
        string $title,
        string $message,
        ?int $senderId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $action = null,
        array $data = [],
    ): void {
        $this->adminRecipientsQuery()->chunkById(100, function (Collection $users) use (
            $module,
            $type,
            $title,
            $message,
            $senderId,
            $entityType,
            $entityId,
            $action,
            $data,
        ): void {
            $users->each(function (User $user) use (
                $module,
                $type,
                $title,
                $message,
                $senderId,
                $entityType,
                $entityId,
                $action,
                $data,
            ): void {
                $this->create(
                    recipient: $user,
                    module: $module,
                    type: $type,
                    title: $title,
                    message: $message,
                    senderId: $senderId,
                    entityType: $entityType,
                    entityId: $entityId,
                    action: $action,
                    data: $data,
                );
            });
        });
    }

    public function notifyChatParticipantsExceptSender(
        int $conversationId,
        User $sender,
        string $messagePreview,
        int $messageId,
    ): void {
        $participantUserIds = ConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', '!=', $sender->id)
            ->pluck('user_id');

        $participantUserIds->each(function (mixed $recipientId) use (
            $sender,
            $conversationId,
            $messagePreview,
            $messageId,
        ): void {
            $id = filter_var($recipientId, FILTER_VALIDATE_INT);
            if ($id === false) {
                return; // @codeCoverageIgnore
            }

            $recipient = User::query()->findOrFail($id);

            $this->create(
                recipient: $recipient,
                module: NotificationModule::Chat,
                type: NotificationType::ChatNewMessage,
                title: sprintf('New message from %s', $sender->name),
                message: $messagePreview,
                senderId: $sender->id,
                entityType: 'chat_message',
                entityId: $messageId,
                action: 'sent',
                data: [
                    'conversation_id' => $conversationId,
                    'message_id' => $messageId,
                    'sender_id' => $sender->id,
                ],
            );
        });
    }

    public function notifyManufacturerCreated(Manufacturer $manufacturer, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Manufacturers,
            type: NotificationType::ManufacturerCreated,
            title: 'Manufacturer created',
            message: sprintf('%s was created.', $manufacturer->full_name),
            senderId: $senderId,
            entityType: 'manufacturer',
            entityId: $manufacturer->id,
            action: 'created',
            data: ['manufacturer_id' => $manufacturer->id],
        );
    }

    public function notifyManufacturerUpdated(Manufacturer $manufacturer, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Manufacturers,
            type: NotificationType::ManufacturerUpdated,
            title: 'Manufacturer updated',
            message: sprintf('%s was updated.', $manufacturer->full_name),
            senderId: $senderId,
            entityType: 'manufacturer',
            entityId: $manufacturer->id,
            action: 'updated',
            data: ['manufacturer_id' => $manufacturer->id],
        );
    }

    public function notifyManufacturerDeleted(Manufacturer $manufacturer, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Manufacturers,
            type: NotificationType::ManufacturerDeleted,
            title: 'Manufacturer deleted',
            message: sprintf('%s was deleted.', $manufacturer->full_name),
            senderId: $senderId,
            entityType: 'manufacturer',
            entityId: $manufacturer->id,
            action: 'deleted',
            data: ['manufacturer_id' => $manufacturer->id],
        );
    }

    public function notifyProductCreated(Product $product, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Products,
            type: NotificationType::ProductCreated,
            title: 'Product created',
            message: sprintf('%s (%s) was created.', $product->name, $product->sku),
            senderId: $senderId,
            entityType: 'product',
            entityId: $product->id,
            action: 'created',
            data: ['product_id' => $product->id],
        );
    }

    public function notifyProductUpdated(Product $product, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Products,
            type: NotificationType::ProductUpdated,
            title: 'Product updated',
            message: sprintf('%s (%s) was updated.', $product->name, $product->sku),
            senderId: $senderId,
            entityType: 'product',
            entityId: $product->id,
            action: 'updated',
            data: ['product_id' => $product->id],
        );
    }

    public function notifyProductDeleted(Product $product, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Products,
            type: NotificationType::ProductDeleted,
            title: 'Product deleted',
            message: sprintf('%s (%s) was deleted.', $product->name, $product->sku),
            senderId: $senderId,
            entityType: 'product',
            entityId: $product->id,
            action: 'deleted',
            data: ['product_id' => $product->id],
        );
    }

    public function notifyBatchCreated(Batch $batch, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Batches,
            type: NotificationType::BatchCreated,
            title: 'Batch created',
            message: sprintf('Batch #%d was created.', $batch->id),
            senderId: $senderId,
            entityType: 'batch',
            entityId: $batch->id,
            action: 'created',
            data: ['batch_id' => $batch->id],
        );
    }

    public function notifyBatchUpdated(Batch $batch, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Batches,
            type: NotificationType::BatchUpdated,
            title: 'Batch updated',
            message: sprintf('Batch #%d was updated.', $batch->id),
            senderId: $senderId,
            entityType: 'batch',
            entityId: $batch->id,
            action: 'updated',
            data: ['batch_id' => $batch->id],
        );
    }

    public function notifyBatchDeleted(Batch $batch, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Batches,
            type: NotificationType::BatchDeleted,
            title: 'Batch deleted',
            message: sprintf('Batch #%d was deleted.', $batch->id),
            senderId: $senderId,
            entityType: 'batch',
            entityId: $batch->id,
            action: 'deleted',
            data: ['batch_id' => $batch->id],
        );
    }

    public function notifyWarehouseLocationCreated(WarehouseLocation $location, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::WarehouseLocations,
            type: NotificationType::WarehouseLocationCreated,
            title: 'Warehouse location created',
            message: sprintf('Location "%s" was created.', $location->name),
            senderId: $senderId,
            entityType: 'warehouse_location',
            entityId: $location->id,
            action: 'created',
            data: ['warehouse_location_id' => $location->id],
        );
    }

    public function notifyWarehouseLocationUpdated(WarehouseLocation $location, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::WarehouseLocations,
            type: NotificationType::WarehouseLocationUpdated,
            title: 'Warehouse location updated',
            message: sprintf('Location "%s" was updated.', $location->name),
            senderId: $senderId,
            entityType: 'warehouse_location',
            entityId: $location->id,
            action: 'updated',
            data: ['warehouse_location_id' => $location->id],
        );
    }

    public function notifyWarehouseLocationDeleted(WarehouseLocation $location, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::WarehouseLocations,
            type: NotificationType::WarehouseLocationDeleted,
            title: 'Warehouse location deleted',
            message: sprintf('Location "%s" was deleted.', $location->name),
            senderId: $senderId,
            entityType: 'warehouse_location',
            entityId: $location->id,
            action: 'deleted',
            data: ['warehouse_location_id' => $location->id],
        );
    }

    public function notifyReceivingCreated(Receiving $receiving, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Receivings,
            type: NotificationType::ReceivingCreated,
            title: 'Receiving created',
            message: sprintf('Receiving #%d was created.', $receiving->id),
            senderId: $senderId,
            entityType: 'receiving',
            entityId: $receiving->id,
            action: 'created',
            data: ['receiving_id' => $receiving->id],
        );
    }

    public function notifyReceivingUpdated(Receiving $receiving, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Receivings,
            type: NotificationType::ReceivingUpdated,
            title: 'Receiving updated',
            message: sprintf('Receiving #%d was updated.', $receiving->id),
            senderId: $senderId,
            entityType: 'receiving',
            entityId: $receiving->id,
            action: 'updated',
            data: ['receiving_id' => $receiving->id],
        );
    }

    public function notifyReceivingDeleted(Receiving $receiving, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Receivings,
            type: NotificationType::ReceivingDeleted,
            title: 'Receiving deleted',
            message: sprintf('Receiving #%d was deleted.', $receiving->id),
            senderId: $senderId,
            entityType: 'receiving',
            entityId: $receiving->id,
            action: 'deleted',
            data: ['receiving_id' => $receiving->id],
        );
    }

    public function notifyTicketCreated(Ticket $ticket, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Tickets,
            type: NotificationType::TicketCreated,
            title: 'New ticket',
            message: sprintf('Ticket #%d: %s', $ticket->id, $ticket->title),
            senderId: $senderId,
            entityType: 'ticket',
            entityId: $ticket->id,
            action: 'created',
            data: ['ticket_id' => $ticket->id],
        );
    }

    public function notifyTicketClosed(Ticket $ticket, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Tickets,
            type: NotificationType::TicketClosed,
            title: 'Ticket closed',
            message: sprintf('Ticket #%d: %s was closed.', $ticket->id, $ticket->title),
            senderId: $senderId,
            entityType: 'ticket',
            entityId: $ticket->id,
            action: 'closed',
            data: ['ticket_id' => $ticket->id],
        );
    }

    public function notifyTicketReopened(Ticket $ticket, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Tickets,
            type: NotificationType::TicketReopened,
            title: 'Ticket reopened',
            message: sprintf('Ticket #%d: %s was reopened.', $ticket->id, $ticket->title),
            senderId: $senderId,
            entityType: 'ticket',
            entityId: $ticket->id,
            action: 'reopened',
            data: ['ticket_id' => $ticket->id],
        );
    }

    public function notifyTicketDeleted(Ticket $ticket, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::Tickets,
            type: NotificationType::TicketDeleted,
            title: 'Ticket deleted',
            message: sprintf('Ticket #%d: %s was deleted.', $ticket->id, $ticket->title),
            senderId: $senderId,
            entityType: 'ticket',
            entityId: $ticket->id,
            action: 'deleted',
            data: ['ticket_id' => $ticket->id],
        );
    }

    public function notifyTicketMessageCreated(TicketMessage $message, ?int $senderId): void
    {
        $message->loadMissing('ticket');
        $ticket = $message->ticket;

        $this->notifyAllAdministrators(
            module: NotificationModule::TicketMessages,
            type: NotificationType::TicketMessageCreated,
            title: 'New ticket message',
            message: sprintf('New reply on ticket #%d: %s', $ticket->id, $ticket->title),
            senderId: $senderId,
            entityType: 'ticket_message',
            entityId: $message->id,
            action: 'created',
            data: [
                'ticket_id' => $ticket->id,
                'ticket_message_id' => $message->id,
            ],
        );
    }

    public function notifyUserRoleAssigned(User $targetUser, string $roleName, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::UserRoles,
            type: NotificationType::UserRoleAssigned,
            title: 'Role assigned',
            message: sprintf("Role '%s' was assigned to %s.", $roleName, $targetUser->name),
            senderId: $senderId,
            entityType: 'user',
            entityId: $targetUser->id,
            action: 'role_assigned',
            data: [
                'user_id' => $targetUser->id,
                'role' => $roleName,
            ],
        );
    }

    public function notifyUserRoleRemoved(User $targetUser, string $roleName, ?int $senderId): void
    {
        $this->notifyAllAdministrators(
            module: NotificationModule::UserRoles,
            type: NotificationType::UserRoleRemoved,
            title: 'Role removed',
            message: sprintf("Role '%s' was removed from %s.", $roleName, $targetUser->name),
            senderId: $senderId,
            entityType: 'user',
            entityId: $targetUser->id,
            action: 'role_removed',
            data: [
                'user_id' => $targetUser->id,
                'role' => $roleName,
            ],
        );
    }

    /**
     * Optional admin-only audit-style notification (e.g. sensitive action_log events).
     *
     * @param  array<string, mixed>  $data
     */
    public function notifyAdministratorsActionLog(
        string $title,
        string $message,
        ?int $senderId,
        ?string $entityType,
        ?int $entityId,
        ?string $action,
        array $data = [],
    ): void {
        $this->notifyAllAdministrators(
            module: NotificationModule::ActionLogs,
            type: NotificationType::ActionLogRecorded,
            title: $title,
            message: $message,
            senderId: $senderId,
            entityType: $entityType,
            entityId: $entityId,
            action: $action,
            data: $data,
        );
    }

    /**
     * @return LengthAwarePaginator<int, AppNotification>
     */
    public function paginateForUser(
        User $user,
        int $perPage,
        ?NotificationModule $module,
        ?bool $isRead,
        ?string $type,
    ): LengthAwarePaginator {
        $query = AppNotification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id');

        if ($module instanceof NotificationModule) {
            $query->where('module', $module->value);
        }

        if ($isRead !== null) {
            $query->where('is_read', $isRead);
        }

        if (is_string($type) && $type !== '') {
            $query->where('type', $type);
        }

        return $query->paginate($perPage);
    }

    public function unreadCountForUser(User $user): int
    {
        return (int) AppNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    public function markAsRead(AppNotification $notification, User $user): AppNotification
    {
        if ($notification->user_id !== $user->id) {
            abort(404);
        }

        if (! $notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return $notification->fresh() ?? $notification;
    }

    public function markAllAsRead(User $user): int
    {
        return (int) AppNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function deleteForUser(AppNotification $notification, User $user): void
    {
        if ($notification->user_id !== $user->id) {
            abort(404);
        }

        $notification->delete();
    }

    public function assertCallerMayCreateNonChatNotification(?User $caller): void
    {
        if (! $caller instanceof User || ! $caller->receivesBroadSystemNotifications()) {
            abort(403, 'Only administrators may create non-chat notifications.');
        }
    }

    /**
     * @return Builder<User>
     */
    private function adminRecipientsQuery(): Builder
    {
        /** @var list<string> $roles */
        $roles = config('notifications.admin_roles', []);

        return User::query()->whereHas('roles', function (Builder $query) use ($roles): void {
            $query->where('guard_name', 'web')
                ->whereIn('name', $roles);
        });
    }

    private function assertRecipientAcceptsModule(User $recipient, NotificationModule $module): void
    {
        if ($recipient->receivesBroadSystemNotifications() || $module === NotificationModule::Chat) {
            return;
        }

        throw ValidationException::withMessages([
            'module' => ['This recipient may only receive chat notifications.'],
        ]);
    }
}
