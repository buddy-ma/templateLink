<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $drivePermissions = [
            'drive.access',
            'drive.upload',
            'drive.share',
            'drive.manage',
            'drive.manage_quota',
        ];

        foreach ($drivePermissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $adminExtra = [...$drivePermissions];

        foreach (['super_admin', 'admin'] as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->givePermissionTo($adminExtra);
        }

        $marketingPerms = ['drive.access', 'drive.upload', 'drive.share'];

        foreach (['project_manager', 'responsable_marketing'] as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->givePermissionTo($marketingPerms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = [
            'drive.access',
            'drive.upload',
            'drive.share',
            'drive.manage',
            'drive.manage_quota',
        ];

        Permission::query()->whereIn('name', $names)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
