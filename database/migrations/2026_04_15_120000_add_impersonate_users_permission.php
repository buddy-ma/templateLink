<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate('impersonate_users', 'web');

        $admin = Role::query()->where('name', 'admin')->where('guard_name', 'web')->first();
        if ($admin instanceof Role && ! $admin->hasPermissionTo($permission)) {
            $admin->givePermissionTo($permission);
        }

        $developer = Role::findOrCreate('developer', 'web');
        $developer->syncPermissions([$permission]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::query()->where('name', 'impersonate_users')->where('guard_name', 'web')->first();
        if ($permission) {
            $permission->delete();
        }

        $developer = Role::query()->where('name', 'developer')->where('guard_name', 'web')->first();
        if ($developer) {
            $developer->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
