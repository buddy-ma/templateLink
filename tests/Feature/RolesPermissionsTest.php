<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

it('forbids guests from roles page', function () {
    $this->get('/admin/roles')->assertRedirect('/login');
});

it('forbids users without manage_roles from roles page', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
    $access = Permission::query()->where('name', 'access_admin')->where('guard_name', 'web')->firstOrFail();
    $role->syncPermissions([$access]);
    $user->assignRole($role);

    $this->actingAs($user)->get('/admin/roles')->assertForbidden();
});

it('allows admin with manage_roles to view roles page', function () {
    $admin = User::factory()->admin()->create();

    $this->withoutVite()
        ->actingAs($admin)
        ->get('/admin/roles')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/roles/Index'));
});

it('allows admin to create a custom role with permissions', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/roles', [
            'name' => 'editor',
            'permission_names' => ['manage_translations'],
        ])
        ->assertRedirect();

    expect(Role::query()->where('name', 'editor')->where('guard_name', 'web')->exists())->toBeTrue();
});

it('applies updated role permissions to users on the next request', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->projectManager()->create();
    $role = Role::query()->where('name', 'project_manager')->where('guard_name', 'web')->firstOrFail();

    $role->syncPermissions(['demands.access', 'demands.create']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($member->fresh()->can('drive.access'))->toBeFalse();

    $this->actingAs($admin)
        ->put("/admin/roles/{$role->id}", [
            'name' => 'project_manager',
            'permission_names' => [
                'demands.access',
                'demands.create',
                'drive.access',
                'drive.upload',
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $member = $member->fresh();
    $member->unsetRelation('roles');
    $member->unsetRelation('permissions');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($member->can('drive.access'))->toBeTrue()
        ->and($member->getAllPermissions()->pluck('name')->all())
        ->toContain('drive.access', 'drive.upload');

    $this->withoutVite()
        ->actingAs($member)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('auth.user.permissions')
            ->where(
                'auth.user.permissions',
                fn ($permissions): bool => collect($permissions)->contains('drive.access'),
            ));
});

it('forbids deleting the admin role', function () {
    $admin = User::factory()->admin()->create();
    $adminRole = Role::query()->where('name', 'admin')->where('guard_name', 'web')->firstOrFail();

    $this->actingAs($admin)
        ->delete("/admin/roles/{$adminRole->id}")
        ->assertSessionHasErrors('role');
});

it('allows admin to create a custom permission', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/permissions', [
            'name' => 'custom_feature',
        ])
        ->assertRedirect();

    expect(Permission::query()->where('name', 'custom_feature')->where('guard_name', 'web')->exists())->toBeTrue();
});

it('forbids deleting a core permission', function () {
    $admin = User::factory()->admin()->create();
    $perm = Permission::query()->where('name', 'access_admin')->where('guard_name', 'web')->firstOrFail();

    $this->actingAs($admin)
        ->delete("/admin/permissions/{$perm->id}")
        ->assertSessionHasErrors('permission');
});
