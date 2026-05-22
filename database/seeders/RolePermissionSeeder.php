<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent: keeps permissions/roles in sync with the migration that introduced Spatie.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = [
            'access_admin',
            'manage_settings',
            'manage_translations',
            'manage_roles',
            'impersonate_users',
        ];

        foreach ($permissionNames as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions(Permission::whereIn('name', $permissionNames)->get());

        $developer = Role::findOrCreate('developer', 'web');
        $developer->syncPermissions(
            Permission::whereIn('name', ['impersonate_users'])->get(),
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
