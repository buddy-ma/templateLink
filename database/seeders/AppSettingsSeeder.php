<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Services\AppSettingsService;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var AppSettingsService $settings */
        $settings = app(AppSettingsService::class);

        $defaults = [
            // Branding
            'branding.app_name' => env('APP_NAME', 'My App'),
            'branding.logo_url' => null,
            'branding.favicon_url' => null,
            'branding.primary_color' => '219 49% 34%',
            'branding.primary_foreground_color' => '0 0% 100%',
            'branding.sidebar_primary_color' => '219 49% 34%',
            'branding.font_source' => 'preset',
            'branding.font_preset' => 'instrument-sans',
            'branding.google_font_family' => 'Poppins',

            // Localization
            'localization.default_locale' => 'fr',
            'localization.supported_locales' => ['fr', 'en', 'es', 'ar'],
            'localization.timezone' => 'UTC',

            // Theme
            'theme.default_appearance' => 'system',
            'theme.force_appearance' => null,

            // Authentication
            'auth.zoho_enabled' => false,
            'auth.password_login_enabled' => true,

            // Drive — global department quota (50 GB)
            'drive.quota_bytes' => 53_687_091_200,
        ];

        foreach ($defaults as $key => $value) {
            if (! AppSetting::where('key', $key)->exists()) {
                $settings->set($key, $value);
            }
        }

        // Keep French as the default, with French + English always available.
        $settings->set('localization.default_locale', 'fr');
        $settings->set('localization.supported_locales', ['fr', 'en']);
    }
}
