<?php

declare(strict_types=1);

namespace App\Actions\Ticket;

use App\Models\Ticket;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class DeleteTicketAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    public function execute(Ticket $ticket): bool
    {
        $this->notificationService->notifyTicketDeleted($ticket, ApiActor::id());
        $this->actionLogService->logModelDeleted($ticket);

        return (bool) $ticket->delete();
    }
}
