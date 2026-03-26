<?php

use App\Models\User;
use App\Services\AppSettingsService;
use App\Support\LocaleMessageFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    app(AppSettingsService::class)->flush();
});

it('redirects guests from translations page', function () {
    $this->get('/admin/translations')->assertRedirect();
});

it('forbids non-admin from translations page', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get('/admin/translations')
        ->assertForbidden();
});

it('allows admin to view translations page', function () {
    $admin = User::factory()->admin()->create();
    $service = app(AppSettingsService::class);
    $service->set('localization.supported_locales', ['en', 'fr']);

    $this->withoutVite()
        ->actingAs($admin)
        ->get('/admin/translations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Translations')
            ->has('locales')
            ->has('activeLocale')
            ->has('flat'));
});

it('allows admin to save locale json file', function () {
    $admin = User::factory()->admin()->create();
    $service = app(AppSettingsService::class);
    $service->set('localization.supported_locales', ['en', 'fr']);

    $path = lang_path('en.json');
    $original = File::get($path);
    $data = json_decode($original, true, 512, JSON_THROW_ON_ERROR);
    $flat = LocaleMessageFile::flatten($data);
    $flat['auth.login'] = 'LOGIN-TEST-UNIQUE-STRING';

    try {
        $this->actingAs($admin)
            ->put(route('admin.translations.update', ['locale' => 'en']), [
                'flat' => $flat,
            ])
            ->assertRedirect(route('admin.translations.index', ['locale' => 'en']))
            ->assertSessionHas('success');

        $saved = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        expect($saved['auth']['login'])->toBe('LOGIN-TEST-UNIQUE-STRING');
    } finally {
        File::put($path, $original);
    }
});

it('returns 404 when updating unsupported locale', function () {
    $admin = User::factory()->admin()->create();
    $service = app(AppSettingsService::class);
    $service->set('localization.supported_locales', ['en']);

    $this->actingAs($admin)
        ->put(route('admin.translations.update', ['locale' => 'de']), [
            'flat' => ['auth.login' => 'x'],
        ])
        ->assertNotFound();
});

it('validates translation keys', function () {
    $admin = User::factory()->admin()->create();
    $service = app(AppSettingsService::class);
    $service->set('localization.supported_locales', ['en']);

    $this->actingAs($admin)
        ->put(route('admin.translations.update', ['locale' => 'en']), [
            'flat' => ['bad key!' => 'x'],
        ])
        ->assertSessionHasErrors('flat.bad key!');
});
