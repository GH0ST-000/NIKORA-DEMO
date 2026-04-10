<?php

declare(strict_types=1);

namespace App\Actions\Receiving;

use App\Models\Receiving;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class CreateReceivingAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Receiving
    {
        $model = Receiving::query()->create($data);

        $this->actionLogService->logModelCreated($model);

        $this->notificationService->notifyReceivingCreated($model, ApiActor::id());

        return $model;
    }
}
