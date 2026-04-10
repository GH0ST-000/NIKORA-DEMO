<?php

declare(strict_types=1);

namespace App\Actions\Manufacturer;

use App\Models\Manufacturer;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class DeleteManufacturerAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    public function execute(Manufacturer $manufacturer): bool
    {
        $this->notificationService->notifyManufacturerDeleted($manufacturer, ApiActor::id());
        $this->actionLogService->logModelDeleted($manufacturer);

        return (bool) $manufacturer->delete();
    }
}
