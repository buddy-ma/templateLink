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

        foreach ($demandPermissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $coreAdmin = [
            'access_admin',
            'manage_settings',
            'manage_translations',
            'manage_roles',
            'impersonate_users',
        ];

        $allAdminPerms = array_merge($coreAdmin, $demandPermissions);

        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $superAdmin->syncPermissions($allAdminPerms);

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions($allAdminPerms);

        $projectManager = Role::findOrCreate('project_manager', 'web');
        $projectManager->syncPermissions(['demands.access', 'demands.create']);

        $responsableMarketing = Role::findOrCreate('responsable_marketing', 'web');
        $responsableMarketing->syncPermissions(['demands.access', 'demands.create']);

        $validator = Role::findOrCreate('validator', 'web');
        $validator->syncPermissions(['demands.access', 'demands.validate']);

        $reglementaires = Role::findOrCreate('reglementaires', 'web');
        $reglementaires->syncPermissions(['demands.access', 'demands.validate']);

        $businessDev = Role::findOrCreate('business_dev', 'web');
        $businessDev->syncPermissions(['demands.access', 'demands.business_validate']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = [
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

        Permission::query()->whereIn('name', $names)->delete();

        foreach (['project_manager', 'responsable_marketing', 'validator', 'reglementaires', 'business_dev', 'super_admin'] as $role) {
            Role::findByName($role, 'web')?->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
