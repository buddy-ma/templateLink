<?php

namespace App\Http\Controllers;

use App\Services\AppSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __construct(private readonly AppSettingsService $settings) {}

    public function switch(Request $request, string $locale): RedirectResponse
    {
        $supportedLocales = $this->settings->get('localization.supported_locales', ['fr', 'en']);

        if (is_string($supportedLocales)) {
            $supportedLocales = json_decode($supportedLocales, true) ?? ['fr', 'en'];
        }

        if (! in_array($locale, (array) $supportedLocales, strict: true)) {
            abort(422, 'Unsupported locale.');
        }

        $request->session()->put('locale', $locale);

        return back()->withCookie(
            cookie()->forever('locale', $locale, secure: $request->secure(), sameSite: 'Lax')
        );
    }
}
