<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Auth;

final class ApiActor
{
    public static function id(): ?int
    {
        $id = Auth::guard('api')->id();

        return $id === null ? null : (int) $id;
    }
}
