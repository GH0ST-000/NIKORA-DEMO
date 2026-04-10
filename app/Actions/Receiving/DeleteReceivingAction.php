<?php

declare(strict_types=1);

namespace App\Actions\Receiving;

use App\Models\Receiving;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class DeleteReceivingAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    public function execute(Receiving $receiving): bool
    {
        $this->notificationService->notifyReceivingDeleted($receiving, ApiActor::id());
        $this->actionLogService->logModelDeleted($receiving);

        return (bool) $receiving->delete();
    }
}
