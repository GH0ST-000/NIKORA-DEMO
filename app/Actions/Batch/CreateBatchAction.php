<?php

declare(strict_types=1);

namespace App\Actions\Batch;

use App\Models\Batch;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class CreateBatchAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Batch
    {
        $data['remaining_quantity'] = $data['quantity'];

        $model = Batch::query()->create($data);

        $this->actionLogService->logModelCreated($model);

        $this->notificationService->notifyBatchCreated($model, ApiActor::id());

        return $model;
    }
}
