<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAppSettingsRequest;
use App\Services\AppSettingsService;
use App\Support\BrandingFont;
use App\Support\FontFamilies;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AppSettingsController extends Controller
{
    public function __construct(private readonly AppSettingsService $settings) {}

    public function index(): Response
    {
        $flat = $this->settings->all();
        unset($flat['auth.zoho_client_secret']);

        return Inertia::render('admin/settings/Index', [
            'settings' => $flat,
            'timezones' => DateTimeZone::listIdentifiers(),
            'fontPresetOptions' => FontFamilies::selectOptions(),
            'googleFontOptions' => BrandingFont::googleFontSelectOptions(),
            'zohoSecretConfigured' => filled($this->settings->get('auth.zoho_client_secret')),
        ]);
    }

    public function update(UpdateAppSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $plainSecret = $request->input('auth.zoho_client_secret');
        unset($validated['auth']['zoho_client_secret']);

        // Flatten nested validated data into dot-notation keys
        $flat = [];
        foreach ($validated as $group => $values) {
            if (is_array($values)) {
                foreach ($values as $key => $value) {
                    $flat["{$group}.{$key}"] = $value;
                }
            } else {
                $flat[$group] = $values;
            }
        }

        // At least one login method must remain enabled
        if (! ($flat['auth.zoho_enabled'] ?? false) && ! ($flat['auth.password_login_enabled'] ?? false)) {
            return back()->withErrors(['auth.password_login_enabled' => 'At least one login method must be enabled.']);
        }

        // Normalize empty force_appearance to null
        if (isset($flat['theme.force_appearance']) && $flat['theme.force_appearance'] === '') {
            $flat['theme.force_appearance'] = null;
        }

        if (is_string($plainSecret) && $plainSecret !== '') {
            $flat['auth.zoho_client_secret'] = Crypt::encryptString($plainSecret);
        }

        $this->settings->setMany($flat);

        if (isset($validated['localization']['default_locale'])) {
            $request->session()->put('locale', $validated['localization']['default_locale']);
        }

        return back()->with('success', 'Settings saved successfully.');
    }

    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,svg,webp'],
        ]);

        // Remove old logo if it was locally uploaded (must use public disk — default disk is app/private).
        $existing = $this->settings->get('branding.logo_url');
        if ($existing && str_starts_with((string) $existing, '/storage/')) {
            $relative = ltrim(str_replace('/storage/', '', (string) $existing), '/');
            Storage::disk('public')->delete($relative);
            // Legacy: uploads used default disk and landed under storage/app/private/public/...
            Storage::disk('local')->delete('public/'.$relative);
        }

        // Use Storage::disk('public') explicitly — the default disk is "local" (storage/app/private),
        // so $file->store(..., 'public') must not be relied on if mis-resolved; putFile always targets app/public.
        $path = Storage::disk('public')->putFile('branding', $request->file('logo'));
        // Store a root-relative URL so the logo works regardless of APP_URL / host.
        $url = '/storage/'.$path;

        $this->settings->set('branding.logo_url', $url);

        return back()->with('success', 'Logo uploaded successfully.');
    }

    public function uploadFavicon(Request $request): RedirectResponse
    {
        $request->validate([
            'favicon' => ['required', 'file', 'max:512', 'mimes:png,jpeg,jpg,svg,webp,ico,x-icon'],
        ]);

        $existing = $this->settings->get('branding.favicon_url');
        if ($existing && str_starts_with((string) $existing, '/storage/')) {
            $relative = ltrim(str_replace('/storage/', '', (string) $existing), '/');
            Storage::disk('public')->delete($relative);
            Storage::disk('local')->delete('public/'.$relative);
        }

        $path = Storage::disk('public')->putFile('branding/favicons', $request->file('favicon'));
        $url = '/storage/'.$path;

        $this->settings->set('branding.favicon_url', $url);

        return back()->with('success', 'Favicon uploaded successfully.');
    }

    public function uploadFont(Request $request): RedirectResponse
    {
        $request->validate([
            'font' => ['required', 'file', 'max:5120', 'mimes:woff2,woff,ttf,otf'],
        ]);

        $existing = $this->settings->get('branding.font_upload_url');
        if (is_string($existing) && str_starts_with($existing, '/storage/')) {
            $relative = ltrim(str_replace('/storage/', '', $existing), '/');
            Storage::disk('public')->delete($relative);
            Storage::disk('local')->delete('public/'.$relative);
        }

        $path = Storage::disk('public')->putFile('fonts', $request->file('font'));
        $url = '/storage/'.$path;

        $this->settings->setMany([
            'branding.font_source' => 'upload',
            'branding.font_upload_url' => $url,
        ]);

        return back()->with('success', 'Font uploaded successfully. Save settings if you changed other options.');
    }
}
