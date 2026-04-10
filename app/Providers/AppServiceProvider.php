<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::bind('notification', function (string $value): AppNotification {
            $user = auth('api')->user();
            if (! $user instanceof User) {
                abort(401);
            }

            return AppNotification::query()
                ->whereKey($value)
                ->where('user_id', $user->id)
                ->firstOrFail();
        });
    }
}
