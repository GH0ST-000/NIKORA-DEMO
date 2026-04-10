<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    case ChatNewMessage = 'chat.new_message';

    case DashboardAlert = 'dashboard.alert';

    case ManufacturerCreated = 'manufacturer.created';
    case ManufacturerUpdated = 'manufacturer.updated';
    case ManufacturerDeleted = 'manufacturer.deleted';

    case ProductCreated = 'product.created';
    case ProductUpdated = 'product.updated';
    case ProductDeleted = 'product.deleted';

    case BatchCreated = 'batch.created';
    case BatchUpdated = 'batch.updated';
    case BatchDeleted = 'batch.deleted';

    case WarehouseLocationCreated = 'warehouse_location.created';
    case WarehouseLocationUpdated = 'warehouse_location.updated';
    case WarehouseLocationDeleted = 'warehouse_location.deleted';

    case ReceivingCreated = 'receiving.created';
    case ReceivingUpdated = 'receiving.updated';
    case ReceivingDeleted = 'receiving.deleted';

    case TicketCreated = 'ticket.created';
    case TicketClosed = 'ticket.closed';
    case TicketReopened = 'ticket.reopened';
    case TicketDeleted = 'ticket.deleted';

    case TicketMessageCreated = 'ticket_message.created';

    case UserRoleAssigned = 'user_role.assigned';
    case UserRoleRemoved = 'user_role.removed';

    case ActionLogRecorded = 'action_log.recorded';

    case RoleChanged = 'role.changed';
    case PermissionChanged = 'permission.changed';

    case Custom = 'custom';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $c): string => $c->value, self::cases());
    }
}
