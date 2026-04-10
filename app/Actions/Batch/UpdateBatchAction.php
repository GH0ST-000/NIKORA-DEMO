<?php

declare(strict_types=1);

namespace App\Actions\Batch;

use App\Models\Batch;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class UpdateBatchAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Batch $batch, array $data): Batch
    {
        $batch->update($data);

        $this->actionLogService->logModelUpdated($batch, $batch->getChanges());

        $this->notificationService->notifyBatchUpdated($batch, ApiActor::id());

        return $batch;
    }
}
