<?php

declare(strict_types=1);

namespace App\Actions\WarehouseLocation;

use App\Models\WarehouseLocation;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class DeleteWarehouseLocationAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    public function execute(WarehouseLocation $location): bool
    {
        $this->notificationService->notifyWarehouseLocationDeleted($location, ApiActor::id());
        $this->actionLogService->logModelDeleted($location);

        return (bool) $location->delete();
    }
}
