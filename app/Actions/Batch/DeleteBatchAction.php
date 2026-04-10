<?php

declare(strict_types=1);

namespace App\Actions\Batch;

use App\Models\Batch;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class DeleteBatchAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    public function execute(Batch $batch): bool
    {
        $this->notificationService->notifyBatchDeleted($batch, ApiActor::id());
        $this->actionLogService->logModelDeleted($batch);

        return (bool) $batch->delete();
    }
}
