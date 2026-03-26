<?php

namespace App\Providers;

use App\Services\AppSettingsService;
use App\Support\BrandingFont;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Zoho\ZohoExtendSocialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AppSettingsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureSocialite();
        $this->configureRootViewFonts();
        $this->configureZohoFromSettings();
    }

    /**
     * Load the selected admin font in the root Blade layout (first paint).
     */
    protected function configureRootViewFonts(): void
    {
        View::composer('app', function ($view): void {
            $settings = app(AppSettingsService::class);
            $href = BrandingFont::stylesheetHref($settings);
            $view->with('fontStylesheetHref', $href);
            $view->with('faviconUrl', $settings->get('branding.favicon_url'));
        });
    }

    /**
     * Override Zoho OAuth credentials from app settings when configured (encrypted secret in DB).
     */
    protected function configureZohoFromSettings(): void
    {
        $settings = app(AppSettingsService::class);
        $id = $settings->get('auth.zoho_client_id');
        if (is_string($id) && $id !== '') {
            config(['services.zoho.client_id' => $id]);
        }
        $enc = $settings->get('auth.zoho_client_secret');
        if (is_string($enc) && $enc !== '') {
            try {
                config(['services.zoho.client_secret' => Crypt::decryptString($enc)]);
            } catch (\Throwable) {
                // Invalid ciphertext; keep env fallback
            }
        }
    }

    /**
     * Register the Zoho Socialite provider.
     */
    protected function configureSocialite(): void
    {
        Event::listen(SocialiteWasCalled::class, ZohoExtendSocialite::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
