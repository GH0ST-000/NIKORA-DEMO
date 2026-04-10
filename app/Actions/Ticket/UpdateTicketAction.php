<?php

declare(strict_types=1);

namespace App\Actions\Ticket;

use App\Models\Ticket;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class UpdateTicketAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Ticket $ticket, array $data): Ticket
    {
        $statusBefore = $ticket->status;

        $ticket->update($data);

        $this->actionLogService->logModelUpdated($ticket, $ticket->getChanges());

        if ($ticket->status === 'closed' && $statusBefore !== 'closed') {
            $this->notificationService->notifyTicketClosed($ticket, ApiActor::id());
        } elseif ($ticket->status !== 'closed' && $statusBefore === 'closed') {
            $this->notificationService->notifyTicketReopened($ticket, ApiActor::id());
        }

        return $ticket;
    }
}
