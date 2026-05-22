<?php

declare(strict_types=1);

use App\Models\User;

it('forbids guests from users directory', function () {
    $this->get(route('admin.users.index'))->assertRedirect('/login');
});

it('forbids users without impersonate permission from users directory', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('allows admin to view users directory', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create();

    $this->withoutVite()
        ->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/users/Index')->has('users'));
});

it('allows developer to view users directory', function () {
    $developer = User::factory()->developer()->create();
    User::factory()->create();

    $this->withoutVite()
        ->actingAs($developer)
        ->get(route('admin.users.index'))
        ->assertOk();
});

it('allows admin to impersonate and stop', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.impersonate', $target))
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($target->id);

    $this->post(route('impersonate.stop'))
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($admin->id);
});

it('prevents impersonating yourself', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.impersonate', $admin))
        ->assertForbidden();
});
