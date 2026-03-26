<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Create Spatie permissions/roles and move legacy is_admin flags into the admin role.
     */
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = [
            'access_admin',
            'manage_settings',
            'manage_translations',
        ];

        foreach ($permissionNames as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions(Permission::whereIn('name', $permissionNames)->get());

        if (Schema::hasColumn('users', 'is_admin')) {
            User::query()->where('is_admin', true)->each(fn (User $user) => $user->assignRole('admin'));

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('is_admin');
            });
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        if (! Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_admin')->default(false)->after('email');
            });
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
