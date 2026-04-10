<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Roles that receive non-chat in-app notifications
    |--------------------------------------------------------------------------
    |
    | Users without any of these roles only receive notifications for the
    | "chat" module. Aligns with operational "admin" visibility in this app.
    |
    */

    'admin_roles' => [
        'Super Admin',
        'Recall Admin',
    ],

];
