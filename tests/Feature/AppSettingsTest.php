<?php

use App\Models\AppSetting;
use App\Models\User;
use App\Services\AppSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    app(AppSettingsService::class)->flush();
});

it('reads a setting with a fallback when the table is empty', function () {
    $service = app(AppSettingsService::class);
    expect($service->get('branding.app_name', 'Fallback'))->toBe('Fallback');
});

it('stores and retrieves a setting', function () {
    $service = app(AppSettingsService::class);
    $service->set('branding.app_name', 'Test App');
    Cache::flush();
    expect($service->get('branding.app_name'))->toBe('Test App');
});

it('encodes and decodes arrays correctly', function () {
    $service = app(AppSettingsService::class);
    $locales = ['en', 'fr', 'es'];
    $service->set('localization.supported_locales', $locales);
    Cache::flush();
    expect($service->get('localization.supported_locales'))->toBe($locales);
});

it('groups settings by prefix', function () {
    $service = app(AppSettingsService::class);
    $service->setMany([
        'branding.app_name' => 'My App',
        'branding.logo_url' => null,
        'localization.default_locale' => 'fr',
    ]);
    Cache::flush();

    $branding = $service->group('branding');
    expect($branding)->toHaveKey('app_name');
    expect($branding)->not->toHaveKey('default_locale');
});

it('caches settings after first read', function () {
    $service = app(AppSettingsService::class);
    $service->set('branding.app_name', 'Cached App');

    // Read once to prime cache
    $service->get('branding.app_name');

    // Delete from DB directly (cache should still serve it)
    AppSetting::where('key', 'branding.app_name')->delete();

    expect($service->get('branding.app_name'))->toBe('Cached App');
});

it('invalidates cache on set', function () {
    $service = app(AppSettingsService::class);
    $service->set('branding.app_name', 'Old');
    $service->get('branding.app_name'); // prime

    $service->set('branding.app_name', 'New');
    expect($service->get('branding.app_name'))->toBe('New');
});

it('exposes appSettings in inertia shared props', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('appSettings')
            ->has('appSettings.branding')
            ->has('appSettings.localization')
            ->has('appSettings.theme')
            ->has('appSettings.auth')
        );
});

it('locale switch stores locale in session and redirects', function () {
    $service = app(AppSettingsService::class);
    $service->set('localization.supported_locales', ['en', 'fr']);

    $this->post('/locale/fr')
        ->assertRedirect();

    expect(session('locale'))->toBe('fr');
});

it('locale switch rejects unsupported locale with 422', function () {
    $service = app(AppSettingsService::class);
    $service->set('localization.supported_locales', ['en']);

    $this->post('/locale/xx')->assertStatus(422);
});

it('admin can view design guide page', function () {
    $admin = User::factory()->admin()->create();

    $this->withoutVite()
        ->actingAs($admin)
        ->get('/admin/design-guide')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/DesignGuide'));
});

it('shares fixed brand primary color #2C497F with inertia', function () {
    $admin = User::factory()->admin()->create();

    $this->withoutVite()
        ->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('appSettings.branding.primaryColor', '219 49% 34%')
            ->where('appSettings.branding.sidebarPrimaryColor', '219 49% 34%'));
});
