<?php

declare(strict_types=1);

namespace App\Actions\WarehouseLocation;

use App\Models\WarehouseLocation;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class UpdateWarehouseLocationAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(WarehouseLocation $location, array $data): WarehouseLocation
    {
        $location->update($data);

        $this->actionLogService->logModelUpdated($location, $location->getChanges());

        $this->notificationService->notifyWarehouseLocationUpdated($location, ApiActor::id());

        return $location;
    }
}
