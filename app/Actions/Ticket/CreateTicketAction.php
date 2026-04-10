<?php

declare(strict_types=1);

namespace App\Actions\Ticket;

use App\Models\Ticket;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class CreateTicketAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Ticket
    {
        $model = Ticket::query()->create(array_merge([
            'status' => 'open',
            'priority' => 'medium',
        ], $data));

        $this->actionLogService->logModelCreated($model);

        $this->notificationService->notifyTicketCreated($model, ApiActor::id());

        return $model;
    }
}
