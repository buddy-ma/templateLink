<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * drive.manage is a global Drive admin bypass. It was incorrectly granted to
 * marketing roles; keep it only on protected admin roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findByName('drive.manage', 'web');

        Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', ['admin', 'super_admin'])
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Intentionally empty: do not re-grant global Drive admin access.
    }
};
