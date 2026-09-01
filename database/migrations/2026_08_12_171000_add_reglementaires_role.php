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

        Permission::findOrCreate('demands.access', 'web');
        Permission::findOrCreate('demands.validate', 'web');

        $role = Role::findOrCreate('reglementaires', 'web');
        $role->syncPermissions(['demands.access', 'demands.validate']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findByName('reglementaires', 'web')?->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
