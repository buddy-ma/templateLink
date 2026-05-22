<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AppSettingsService;
use App\Support\BrandingFont;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(private readonly AppSettingsService $settings) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => $this->settings->get('branding.app_name', config('app.name')),
            'auth' => [
                'user' => $user instanceof User ? $this->serializeUserForInertia($user) : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'appSettings' => $this->buildAppSettings(),
            'liveLocaleMessages' => $this->loadLiveLocaleMessages(),
            'impersonation' => [
                'active' => $request->session()->has('impersonate.original_user_id'),
                'originalUserId' => $request->session()->get('impersonate.original_user_id'),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Build the frontend-safe settings payload.
     *
     * @return array<string, mixed>
     */
    private function buildAppSettings(): array
    {
        $supportedLocales = $this->settings->get('localization.supported_locales', ['en']);

        if (is_string($supportedLocales)) {
            $supportedLocales = json_decode($supportedLocales, true) ?? ['en'];
        }

        $fontSource = $this->settings->get('branding.font_source');
        if ($fontSource === null || $fontSource === '') {
            $fontSource = 'preset';
        }

        return [
            'branding' => [
                'appName' => $this->settings->get('branding.app_name', config('app.name')),
                'logoUrl' => $this->settings->get('branding.logo_url'),
                'primaryColor' => $this->settings->get('branding.primary_color', '0 0% 9%'),
                'primaryForegroundColor' => $this->settings->get('branding.primary_foreground_color', '0 0% 98%'),
                'sidebarPrimaryColor' => $this->settings->get('branding.sidebar_primary_color', '0 0% 10%'),
                'fontSource' => (string) $fontSource,
                'fontPreset' => (string) ($this->settings->get('branding.font_preset') ?? $this->settings->get('branding.font_family', 'instrument-sans')),
                'googleFontFamily' => (string) $this->settings->get('branding.google_font_family', 'Poppins'),
                'fontUploadUrl' => BrandingFont::fontUploadPublicUrl($this->settings),
                'fontFaceName' => BrandingFont::fontFaceName($this->settings),
                'fontStack' => BrandingFont::fontStack($this->settings),
                'googleFontStylesheetUrl' => ((string) $fontSource) === 'google'
                    ? BrandingFont::googleStylesheetUrl((string) $this->settings->get('branding.google_font_family', 'Poppins'))
                    : null,
                'faviconUrl' => $this->settings->get('branding.favicon_url'),
            ],
            'localization' => [
                'defaultLocale' => $this->settings->get('localization.default_locale', 'en'),
                'supportedLocales' => $supportedLocales,
                'currentLocale' => app()->getLocale(),
                'timezone' => $this->settings->get('localization.timezone', 'UTC'),
            ],
            'theme' => [
                'defaultAppearance' => $this->settings->get('theme.default_appearance', 'system'),
                'forceAppearance' => $this->settings->get('theme.force_appearance'),
            ],
            'auth' => [
                'zohoEnabled' => (bool) $this->settings->get('auth.zoho_enabled', false),
                'passwordLoginEnabled' => (bool) $this->settings->get('auth.password_login_enabled', true),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUserForInertia(User $user): array
    {
        $data = $user->toArray();
        $data['permissions'] = $user->getAllPermissions()->pluck('name')->values()->all();
        $data['roles'] = $user->getRoleNames()->values()->all();
        $data['is_admin'] = $user->can('access_admin');

        return $data;
    }

    /**
     * Current locale JSON from disk so SPA picks up edits without a rebuild.
     *
     * @return array<string, mixed>
     */
    private function loadLiveLocaleMessages(): array
    {
        $locale = app()->getLocale();
        $path = lang_path("{$locale}.json");
        if (! File::exists($path) || ! File::isReadable($path)) {
            return [];
        }

        try {
            $decoded = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
