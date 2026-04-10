<?php

declare(strict_types=1);

namespace App\Actions\Receiving;

use App\Models\Receiving;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class UpdateReceivingAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Receiving $receiving, array $data): Receiving
    {
        $receiving->update($data);

        $this->actionLogService->logModelUpdated($receiving, $receiving->getChanges());

        $result = $receiving->fresh();
        assert($result instanceof Receiving);

        $this->notificationService->notifyReceivingUpdated($result, ApiActor::id());

        return $result;
    }
}
