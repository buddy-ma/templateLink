<?php

namespace Database\Seeders;

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
            'branding.app_name'    => env('APP_NAME', 'My App'),
            'branding.logo_url'    => null,
            'branding.favicon_url' => null,
            'branding.primary_color'            => '0 0% 9%',
            'branding.primary_foreground_color' => '0 0% 98%',
            'branding.sidebar_primary_color'    => '0 0% 10%',
            'branding.font_source'              => 'preset',
            'branding.font_preset'              => 'instrument-sans',
            'branding.google_font_family'       => 'Poppins',

            // Localization
            'localization.default_locale'    => 'en',
            'localization.supported_locales' => ['en', 'fr', 'es', 'ar'],
            'localization.timezone'          => 'UTC',

            // Theme
            'theme.default_appearance' => 'system',
            'theme.force_appearance'   => null,

            // Authentication
            'auth.zoho_enabled'            => false,
            'auth.password_login_enabled'  => true,
        ];

        foreach ($defaults as $key => $value) {
            if (! \App\Models\AppSetting::where('key', $key)->exists()) {
                $settings->set($key, $value);
            }
        }
    }
}
