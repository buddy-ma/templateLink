<?php

declare(strict_types=1);

namespace App\Support;

final class Acl
{
    /** Permissions that cannot be deleted from the UI (enforced in code / seeders). */
    public const CORE_PERMISSIONS = [
        'access_admin',
        'manage_settings',
        'manage_translations',
        'manage_roles',
    ];

    public const PROTECTED_ROLE_NAME = 'admin';
}
