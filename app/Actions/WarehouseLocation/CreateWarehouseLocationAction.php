<?php

declare(strict_types=1);

namespace App\Actions\WarehouseLocation;

use App\Models\WarehouseLocation;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class CreateWarehouseLocationAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): WarehouseLocation
    {
        $model = WarehouseLocation::query()->create($data);

        $this->actionLogService->logModelCreated($model);

        $this->notificationService->notifyWarehouseLocationCreated($model, ApiActor::id());

        return $model;
    }
}
