<?php

use App\Models\User;
use App\Services\AppSettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

it('admin can upload favicon', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $file = UploadedFile::fake()->image('favicon.png', 32, 32);

    $this->actingAs($admin)
        ->post('/admin/settings/favicon', [
            'favicon' => $file,
        ])
        ->assertRedirect();

    $url = app(AppSettingsService::class)->get('branding.favicon_url');
    expect($url)->toBeString()->not->toBeEmpty();
});
