<?php

use App\Enums\DriveSharePermission;
use App\Models\Drive\DriveFile;
use App\Models\Drive\DriveFolder;
use App\Models\Drive\DriveShare;
use App\Models\Drive\DriveShareLink;
use App\Models\User;
use App\Services\AppSettingsService;
use App\Services\Drive\DriveQuotaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    app(AppSettingsService::class)->set('drive.quota_bytes', 10_000_000);
});

it('allows a project manager to browse the drive', function () {
    $user = User::factory()->projectManager()->create();

    $this->withoutVite()
        ->actingAs($user)
        ->get('/drive')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('drive/Index')
            ->has('storage')
            ->where('can.upload', true));
});

it('creates nested folders', function () {
    $user = User::factory()->projectManager()->create();

    $this->actingAs($user)
        ->post('/drive/folders', ['name' => 'Campaigns'])
        ->assertRedirect();

    $parent = DriveFolder::query()->where('name', 'Campaigns')->firstOrFail();

    $this->actingAs($user)
        ->post('/drive/folders', [
            'name' => 'Q1',
            'parent_id' => $parent->id,
        ])
        ->assertRedirect();

    expect(DriveFolder::query()->where('parent_id', $parent->id)->where('name', 'Q1')->exists())->toBeTrue();
});

it('uploads a file when clamav validation is skipped', function () {
    $user = User::factory()->projectManager()->create();
    $file = UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->post('/drive/files', [
            'file' => $file,
            'name' => 'brief.pdf',
        ])
        ->assertRedirect();

    $stored = DriveFile::query()->first();
    expect($stored)->not->toBeNull()
        ->and($stored->name)->toBe('brief.pdf')
        ->and($stored->size)->toBeGreaterThan(0);

    Storage::disk('local')->assertExists($stored->path);
});

it('blocks upload when department quota is exceeded', function () {
    $user = User::factory()->projectManager()->create();
    app(DriveQuotaService::class)->setQuotaBytes(100);

    DriveFile::factory()->create([
        'owner_id' => $user->id,
        'uploaded_by' => $user->id,
        'size' => 90,
    ]);

    $file = UploadedFile::fake()->create('big.pdf', 50, 'application/pdf');

    $this->actingAs($user)
        ->post('/drive/files', ['file' => $file])
        ->assertSessionHasErrors('file');
});

it('lets drive admins browse all documents and department storage', function () {
    $owner = User::factory()->projectManager()->create();
    $admin = User::factory()->admin()->create();

    $folder = DriveFolder::factory()->create([
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
        'name' => 'Team Root',
    ]);

    DriveFile::factory()->create([
        'folder_id' => $folder->id,
        'owner_id' => $owner->id,
        'uploaded_by' => $owner->id,
        'size' => 1_000,
        'name' => 'team.pdf',
    ]);

    DriveFile::factory()->create([
        'owner_id' => $admin->id,
        'uploaded_by' => $admin->id,
        'size' => 250,
        'name' => 'admin-only.pdf',
    ]);

    expect($admin->can('drive.manage'))->toBeTrue();

    $this->withoutVite()
        ->actingAs($admin)
        ->get('/drive')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('folders', fn ($folders): bool => collect($folders)->contains(fn ($f) => ($f['id'] ?? null) === $folder->id))
            ->where('storage.scope', 'department')
            ->where('storage.used_bytes', 1_250));
});

it('shows personal storage usage for non-admin users', function () {
    $owner = User::factory()->projectManager()->create();
    $other = User::factory()->responsableMarketing()->create();

    DriveFile::factory()->create([
        'owner_id' => $owner->id,
        'uploaded_by' => $owner->id,
        'size' => 400,
    ]);

    DriveFile::factory()->create([
        'owner_id' => $other->id,
        'uploaded_by' => $other->id,
        'size' => 900,
    ]);

    $this->withoutVite()
        ->actingAs($owner)
        ->get('/drive')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('storage.scope', 'personal')
            ->where('storage.used_bytes', 400));
});

it('hides unshared drive items from other users', function () {
    $owner = User::factory()->projectManager()->create();
    $outsider = User::factory()->responsableMarketing()->create();

    $folder = DriveFolder::factory()->create([
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
        'name' => 'Private Campaigns',
    ]);

    $file = DriveFile::factory()->create([
        'folder_id' => $folder->id,
        'owner_id' => $owner->id,
        'uploaded_by' => $owner->id,
        'name' => 'secret.pdf',
    ]);

    expect($outsider->can('drive.manage'))->toBeFalse();

    $this->withoutVite()
        ->actingAs($outsider)
        ->get('/drive')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('folders', fn ($folders): bool => collect($folders)->where('id', $folder->id)->isEmpty())
            ->where('files', fn ($files): bool => collect($files)->where('id', $file->id)->isEmpty()));

    $this->actingAs($outsider)
        ->get("/drive?folder={$folder->id}")
        ->assertForbidden();

    $this->actingAs($outsider)
        ->get("/drive/files/{$file->id}/download")
        ->assertForbidden();
});

it('shows directly shared files in shared-with-me even when nested in a folder', function () {
    $owner = User::factory()->projectManager()->create();
    $colleague = User::factory()->responsableMarketing()->create();

    $folder = DriveFolder::factory()->create([
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
        'name' => 'Campaigns',
    ]);

    $file = DriveFile::factory()->create([
        'folder_id' => $folder->id,
        'owner_id' => $owner->id,
        'uploaded_by' => $owner->id,
        'name' => 'shared-image.jpg',
    ]);

    $this->actingAs($owner)
        ->post("/drive/files/{$file->id}/shares", [
            'user_id' => $colleague->id,
        ])
        ->assertRedirect();

    $this->withoutVite()
        ->actingAs($colleague)
        ->get('/drive?scope=shared')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('files', fn ($files): bool => collect($files)->contains(fn ($f) => ($f['id'] ?? null) === $file->id))
            ->where('folders', fn ($folders): bool => collect($folders)->where('id', $folder->id)->isEmpty()));
});

it('shares a folder with another user and cascades visibility', function () {
    $owner = User::factory()->projectManager()->create();
    $colleague = User::factory()->responsableMarketing()->create();

    $folder = DriveFolder::factory()->create([
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
        'name' => 'Shared Root',
    ]);

    $child = DriveFolder::factory()->create([
        'parent_id' => $folder->id,
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
        'name' => 'Nested',
    ]);

    $privateSibling = DriveFolder::factory()->create([
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
        'name' => 'Not Shared',
    ]);

    $file = DriveFile::factory()->create([
        'folder_id' => $child->id,
        'owner_id' => $owner->id,
        'uploaded_by' => $owner->id,
        'name' => 'nested.pdf',
    ]);

    $this->actingAs($owner)
        ->post("/drive/folders/{$folder->id}/shares", [
            'user_id' => $colleague->id,
        ])
        ->assertRedirect();

    expect(DriveShare::query()->where('user_id', $colleague->id)->exists())->toBeTrue();

    $this->withoutVite()
        ->actingAs($colleague)
        ->get('/drive')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('folders', fn ($folders): bool => collect($folders)->where('id', $folder->id)->isEmpty())
            ->where('folders', fn ($folders): bool => collect($folders)->where('id', $privateSibling->id)->isEmpty()));

    $this->withoutVite()
        ->actingAs($colleague)
        ->get('/drive?scope=shared')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('folders', fn ($folders): bool => collect($folders)->contains(fn ($f) => ($f['id'] ?? null) === $folder->id))
            ->where('folders', fn ($folders): bool => collect($folders)->where('id', $privateSibling->id)->isEmpty()));

    $this->actingAs($colleague)
        ->get("/drive/files/{$file->id}/download")
        ->assertOk();
});

it('creates a public share link and rejects expired ones', function () {
    $owner = User::factory()->projectManager()->create();
    $file = DriveFile::factory()->create([
        'owner_id' => $owner->id,
        'uploaded_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->post("/drive/files/{$file->id}/links", [
            'expires_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertRedirect();

    $link = DriveShareLink::query()->firstOrFail();

    $this->withoutVite()
        ->get("/drive/s/{$link->token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('drive/shared/Show')
            ->where('requiresPassword', false));

    $link->forceFill(['expires_at' => now()->subMinute()])->save();

    $this->get("/drive/s/{$link->token}")->assertStatus(410);
});

it('rejects revoked share links', function () {
    $owner = User::factory()->projectManager()->create();
    $file = DriveFile::factory()->create([
        'owner_id' => $owner->id,
        'uploaded_by' => $owner->id,
    ]);

    $link = DriveShareLink::query()->create([
        'shareable_type' => DriveFile::class,
        'shareable_id' => $file->id,
        'token' => DriveShareLink::generateToken(),
        'permission' => DriveSharePermission::Viewer,
        'created_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->delete("/drive/links/{$link->id}")
        ->assertRedirect();

    $this->get("/drive/s/{$link->token}")->assertStatus(410);
});

it('requires a password for protected share links', function () {
    $owner = User::factory()->projectManager()->create();
    $file = DriveFile::factory()->create([
        'owner_id' => $owner->id,
        'uploaded_by' => $owner->id,
    ]);

    $link = DriveShareLink::query()->create([
        'shareable_type' => DriveFile::class,
        'shareable_id' => $file->id,
        'token' => DriveShareLink::generateToken(),
        'password' => 'secret123',
        'permission' => DriveSharePermission::Viewer,
        'created_by' => $owner->id,
    ]);

    $this->withoutVite()
        ->get("/drive/s/{$link->token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('requiresPassword', true));

    $this->post("/drive/s/{$link->token}/unlock", ['password' => 'wrong'])
        ->assertSessionHasErrors('password');

    $this->post("/drive/s/{$link->token}/unlock", ['password' => 'secret123'])
        ->assertRedirect(route('drive.shared.show', ['token' => $link->token]));
});
