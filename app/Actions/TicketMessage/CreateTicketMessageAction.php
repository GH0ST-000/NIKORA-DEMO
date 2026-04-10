<?php

declare(strict_types=1);

namespace App\Actions\TicketMessage;

use App\Models\TicketMessage;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class CreateTicketMessageAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): TicketMessage
    {
        $model = TicketMessage::query()->create($data);

        $this->actionLogService->logModelCreated($model);

        $this->notificationService->notifyTicketMessageCreated($model, ApiActor::id());

        return $model;
    }
}
