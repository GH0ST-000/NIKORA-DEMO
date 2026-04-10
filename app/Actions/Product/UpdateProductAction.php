<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Product;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class UpdateProductAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Product $product, array $data): Product
    {
        $product->update($data);

        $this->actionLogService->logModelUpdated($product, $product->getChanges());

        $this->notificationService->notifyProductUpdated($product, ApiActor::id());

        return $product;
    }
}
