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
            'notifications' => $this->buildNotificationsPayload($user),
        ];
    }

    /**
     * @return array{unread_count: int, recent: array<int, array<string, mixed>>}
     */
    private function buildNotificationsPayload(?User $user): array
    {
        if (! $user instanceof User) {
            return [
                'unread_count' => 0,
                'recent' => [],
            ];
        }

        $recent = $user->notifications()
            ->latest()
            ->limit(8)
            ->get()
            ->map(static fn ($notification): array => [
                'id' => $notification->id,
                'type' => class_basename($notification->type),
                'data' => $notification->data,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'recent' => $recent,
        ];
    }

    /**
     * Build the frontend-safe settings payload.
     *
     * @return array<string, mixed>
     */
    private function buildAppSettings(): array
    {
        $supportedLocales = $this->settings->get('localization.supported_locales', ['fr', 'en']);

        if (is_string($supportedLocales)) {
            $supportedLocales = json_decode($supportedLocales, true) ?? ['fr', 'en'];
        }

        $fontSource = $this->settings->get('branding.font_source');
        if ($fontSource === null || $fontSource === '') {
            $fontSource = 'preset';
        }

        return [
            'branding' => [
                'appName' => $this->settings->get('branding.app_name', config('app.name')),
                'logoUrl' => $this->settings->get('branding.logo_url'),
                // Fixed brand primary #2C497F — not configurable via app settings.
                'primaryColor' => '219 49% 34%',
                'primaryForegroundColor' => '0 0% 100%',
                'sidebarPrimaryColor' => '219 49% 34%',
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
                'defaultLocale' => $this->settings->get('localization.default_locale', 'fr'),
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
        // Drop any eager-loaded relations so Spatie resolves roles/permissions fresh
        // (important after admin role edits in the same browser session).
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
            // Always plain string[] for the SPA (never Eloquent collections/models).
            'permissions' => array_values(array_map(
                static fn ($name): string => (string) $name,
                $user->getAllPermissions()->pluck('name')->all(),
            )),
            'roles' => array_values(array_map(
                static fn ($name): string => (string) $name,
                $user->getRoleNames()->all(),
            )),
            'is_admin' => $user->can('access_admin'),
        ];
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
