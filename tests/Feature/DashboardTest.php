<?php

use App\Enums\DemandStatus;
use App\Models\Demand;
use App\Models\Drive\DriveFile;
use App\Models\Drive\DriveFolder;
use App\Models\Drive\DriveShare;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->projectManager()->create();

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('dashboard.stats')
            ->has('dashboard.drive')
            ->has('dashboard.urgent')
            ->has('dashboard.charts')
            ->has('dashboard.recent_activity.data')
            ->has('dashboard.recent_activity.current_page')
            ->where('dashboard.welcome.name', $user->name));
});

test('dashboard drive metrics are personal for non-admin users', function () {
    $owner = User::factory()->projectManager()->create();
    $other = User::factory()->responsableMarketing()->create();

    DriveFolder::factory()->create([
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
    ]);
    DriveFile::factory()->create([
        'owner_id' => $owner->id,
        'uploaded_by' => $owner->id,
        'size' => 2048,
    ]);
    DriveFile::factory()->create([
        'owner_id' => $other->id,
        'uploaded_by' => $other->id,
        'size' => 9999,
    ]);

    $shared = DriveFile::factory()->create([
        'owner_id' => $other->id,
        'uploaded_by' => $other->id,
    ]);
    DriveShare::query()->create([
        'shareable_type' => DriveFile::class,
        'shareable_id' => $shared->id,
        'user_id' => $owner->id,
        'permission' => 'editor',
        'shared_by' => $other->id,
    ]);

    $this->withoutVite()
        ->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('dashboard.drive.scope', 'personal')
            ->where('dashboard.drive.files', 1)
            ->where('dashboard.drive.folders', 1)
            ->where('dashboard.drive.shared_with_me', 1)
            ->where('dashboard.drive.storage_used_bytes', 2048));
});

test('dashboard drive metrics are department-wide for admins', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->projectManager()->create();

    DriveFile::factory()->create([
        'owner_id' => $owner->id,
        'uploaded_by' => $owner->id,
        'size' => 1000,
    ]);
    DriveFile::factory()->create([
        'owner_id' => $admin->id,
        'uploaded_by' => $admin->id,
        'size' => 500,
    ]);

    $this->withoutVite()
        ->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('dashboard.drive.scope', 'department')
            ->where('dashboard.drive.files', 2)
            ->where('dashboard.drive.storage_used_bytes', 1500));
});

test('dashboard includes action-required demands for the current validator', function () {
    $pm = User::factory()->projectManager()->create();
    $validator = User::factory()->validator()->create();
    $other = User::factory()->validator()->create();

    $mine = Demand::factory()->create([
        'created_by' => $pm->id,
        'status' => DemandStatus::PendingValidation,
        'current_step' => 1,
    ]);
    $mine->validators()->create([
        'user_id' => $validator->id,
        'position' => 1,
        'status' => 'pending',
    ]);
    $mine->validators()->create([
        'user_id' => $other->id,
        'position' => 2,
        'status' => 'pending',
    ]);
    $mine->validators()->create([
        'user_id' => User::factory()->validator()->create()->id,
        'position' => 3,
        'status' => 'pending',
    ]);

    $this->withoutVite()
        ->actingAs($validator)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('dashboard.stats.awaiting_me', 1)
            ->has('dashboard.urgent', 1)
            ->where('dashboard.urgent.0.reference', $mine->reference));
});
