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
        'demands.access',
        'demands.create',
        'demands.manage_catalog',
        'demands.manage_pipeline',
        'demands.validate',
        'demands.business_validate',
        'demands.close',
        'demands.unblock',
        'demands.view_all',
        'drive.access',
        'drive.upload',
        'drive.share',
        'drive.manage',
        'drive.manage_quota',
    ];

    /** @deprecated Use PROTECTED_ROLE_NAMES */
    public const PROTECTED_ROLE_NAME = 'admin';

    /** Role names that cannot be renamed or deleted from the UI. */
    public const PROTECTED_ROLE_NAMES = [
        'admin',
        'super_admin',
    ];

    public static function isProtectedRole(string $name): bool
    {
        return in_array($name, self::PROTECTED_ROLE_NAMES, true);
    }
}
