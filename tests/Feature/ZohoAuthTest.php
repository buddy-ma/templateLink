<?php

use App\Models\User;
use App\Services\AppSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    app(AppSettingsService::class)->flush();

    config([
        'services.zoho.client_id' => 'test-client-id',
        'services.zoho.client_secret' => 'test-client-secret',
        'services.zoho.redirect' => 'http://localhost/auth/zoho/callback',
        'services.zoho.domain' => 'accounts.zoho.eu',
        'services.zoho.trusted_email_domains' => ['laprophan.com'],
    ]);
});

it('forbids zoho redirect when disabled', function () {
    app(AppSettingsService::class)->set('auth.zoho_enabled', false);

    $this->get(route('auth.zoho'))->assertForbidden();
});

it('redirects to zoho when enabled and configured', function () {
    app(AppSettingsService::class)->set('auth.zoho_enabled', true);

    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('redirectUrl')->once()->andReturnSelf();
    $provider->shouldReceive('scopes')->once()->andReturnSelf();
    $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.zoho.eu/oauth/v2/auth'));

    Socialite::shouldReceive('driver')->once()->with('zoho')->andReturn($provider);

    $this->get(route('auth.zoho'))
        ->assertRedirect('https://accounts.zoho.eu/oauth/v2/auth');
});

it('rejects callback when zoho user has no matching local account', function () {
    app(AppSettingsService::class)->set('auth.zoho_enabled', true);

    $socialUser = new SocialiteUser;
    $socialUser->id = 'zoho-123';
    $socialUser->email = 'unknown@laprophan.com';
    $socialUser->name = 'Unknown User';
    $socialUser->user = ['email_verified' => true];

    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($socialUser);
    Socialite::shouldReceive('driver')->once()->with('zoho')->andReturn($provider);

    $this->get(route('auth.zoho.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');
});

it('links existing user by trusted domain email and logs in', function () {
    app(AppSettingsService::class)->set('auth.zoho_enabled', true);

    $user = User::factory()->create([
        'email' => 'alice@laprophan.com',
        'zoho_id' => null,
    ]);

    $socialUser = new SocialiteUser;
    $socialUser->id = 'zoho-456';
    $socialUser->email = 'alice@laprophan.com';
    $socialUser->name = 'Alice Updated';
    $socialUser->avatar = 'https://example.com/avatar.png';
    $socialUser->user = [];

    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($socialUser);
    Socialite::shouldReceive('driver')->once()->with('zoho')->andReturn($provider);

    $this->get(route('auth.zoho.callback'))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user->fresh());
    expect($user->fresh()->zoho_id)->toBe('zoho-456');
    expect($user->fresh()->name)->toBe('Alice Updated');
});

it('logs in user matched by zoho_id without re-linking', function () {
    app(AppSettingsService::class)->set('auth.zoho_enabled', true);

    $user = User::factory()->create([
        'email' => 'bob@example.com',
        'zoho_id' => 'zoho-789',
    ]);

    $socialUser = new SocialiteUser;
    $socialUser->id = 'zoho-789';
    $socialUser->email = 'bob@example.com';
    $socialUser->name = $user->name;
    $socialUser->user = [];

    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($socialUser);
    Socialite::shouldReceive('driver')->once()->with('zoho')->andReturn($provider);

    $this->get(route('auth.zoho.callback'))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});
