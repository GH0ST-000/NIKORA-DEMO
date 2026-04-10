<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Product;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class DeleteProductAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    public function execute(Product $product): bool
    {
        $this->notificationService->notifyProductDeleted($product, ApiActor::id());
        $this->actionLogService->logModelDeleted($product);

        return (bool) $product->delete();
    }
}
