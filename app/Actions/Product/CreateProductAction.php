<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Product;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class CreateProductAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Product
    {
        $model = Product::query()->create($data);

        $this->actionLogService->logModelCreated($model);

        $this->notificationService->notifyProductCreated($model, ApiActor::id());

        return $model;
    }
}
