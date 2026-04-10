<?php

declare(strict_types=1);

namespace App\Actions\Ticket;

use App\Models\Ticket;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class ReopenTicketAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    public function execute(Ticket $ticket): Ticket
    {
        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'open',
            'closed_at' => null,
        ]);

        $this->actionLogService->logStatusChange($ticket, $oldStatus, 'open');

        $this->notificationService->notifyTicketReopened($ticket, ApiActor::id());

        return $ticket;
    }
}
