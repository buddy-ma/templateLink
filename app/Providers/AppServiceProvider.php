<?php

namespace App\Providers;

use App\Models\Demand;
use App\Models\Drive\DriveFile;
use App\Models\Drive\DriveFolder;
use App\Models\User;
use App\Policies\DemandPolicy;
use App\Policies\Drive\DriveFilePolicy;
use App\Policies\Drive\DriveFolderPolicy;
use App\Policies\UserPolicy;
use App\Services\AppSettingsService;
use App\Services\Drive\DriveAccessService;
use App\Services\Drive\DriveNotificationService;
use App\Services\Drive\DriveQuotaService;
use App\Services\Drive\DriveWorkflowService;
use App\Support\BrandingFont;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AppSettingsService::class);
        $this->app->singleton(DriveAccessService::class);
        $this->app->singleton(DriveQuotaService::class);
        $this->app->singleton(DriveNotificationService::class);
        $this->app->singleton(DriveWorkflowService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Demand::class, DemandPolicy::class);
        Gate::policy(DriveFolder::class, DriveFolderPolicy::class);
        Gate::policy(DriveFile::class, DriveFilePolicy::class);

        $this->configureDefaults();
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
            $view->with(
                'inertiaDocumentTitle',
                $settings->get('branding.app_name', config('app.name')),
            );
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
