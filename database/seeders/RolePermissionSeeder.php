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

        $corePermissions = [
            'access_admin',
            'manage_settings',
            'manage_translations',
            'manage_roles',
            'impersonate_users',
        ];

        $demandPermissions = [
            'demands.access',
            'demands.create',
            'demands.manage_catalog',
            'demands.manage_pipeline',
            'demands.validate',
            'demands.business_validate',
            'demands.close',
            'demands.unblock',
            'demands.view_all',
        ];

        $drivePermissions = [
            'drive.access',
            'drive.upload',
            'drive.share',
            'drive.manage',
            'drive.manage_quota',
        ];

        foreach ([...$corePermissions, ...$demandPermissions, ...$drivePermissions] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $allAdminPerms = [...$corePermissions, ...$demandPermissions, ...$drivePermissions];

        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $superAdmin->syncPermissions($allAdminPerms);

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions($allAdminPerms);

        // Standard marketing Drive access — NOT drive.manage (global browse/edit bypass).
        $standardDrivePermissions = [
            'drive.access',
            'drive.upload',
            'drive.share',
        ];

        $developer = Role::findOrCreate('developer', 'web');
        $developer->syncPermissions([
            'impersonate_users',
            ...$standardDrivePermissions,
        ]);

        Role::findOrCreate('project_manager', 'web')
            ->syncPermissions([
                'demands.access',
                'demands.create',
                ...$standardDrivePermissions,
            ]);

        Role::findOrCreate('responsable_marketing', 'web')
            ->syncPermissions([
                'demands.access',
                'demands.create',
                ...$standardDrivePermissions,
            ]);

        Role::findOrCreate('validator', 'web')
            ->syncPermissions([
                'demands.access',
                'demands.validate',
                ...$standardDrivePermissions,
            ]);

        Role::findOrCreate('reglementaires', 'web')
            ->syncPermissions([
                'demands.access',
                'demands.validate',
                ...$standardDrivePermissions,
            ]);

        Role::findOrCreate('business_dev', 'web')
            ->syncPermissions([
                'demands.access',
                'demands.business_validate',
                ...$standardDrivePermissions,
            ]);

        // Strip accidental global Drive admin from non-admin roles (idempotent).
        $manage = Permission::findByName('drive.manage', 'web');
        Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', ['admin', 'super_admin'])
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($manage));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
