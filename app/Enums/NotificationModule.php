<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationModule: string
{
    case Chat = 'chat';
    case Dashboard = 'dashboard';
    case Manufacturers = 'manufacturers';
    case Products = 'products';
    case Batches = 'batches';
    case WarehouseLocations = 'warehouse_locations';
    case Receivings = 'receivings';
    case Tickets = 'tickets';
    case TicketMessages = 'ticket_messages';
    case ActionLogs = 'action_logs';
    case UserRoles = 'user_roles';
    case Roles = 'roles';
    case Permissions = 'permissions';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $c): string => $c->value, self::cases());
    }
}
